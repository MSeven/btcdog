<?php
/**
 *
 */
use Graze\GuzzleHttp\JsonRpc\Client as RpcClient;
use League\StatsD\Client as Statsd;

require('vendor/autoload.php');
require('config.php');


class btcDog
{
    const BTC_CACHE_FILE = 'btc_transfers.cache';
    const ETH_CACHE_FILE = 'eth_transfers.cache';

    private $dd;
    private $requestId;

    public function __construct()
    {
        /**
         * Init StatsD Client.
         */
        $this->dd        = new Statsd();
        $this->requestId = 0;
    }

    function unparseUrl($urlParts)
    {
        $scheme = isset($urlParts['scheme']) ? $urlParts['scheme'] . '://' : '';
        $host   = isset($urlParts['host']) ? $urlParts['host'] : '';
        $port   = isset($urlParts['port']) ? ':' . $urlParts['port'] : '';
        $user   = isset($urlParts['user']) ? $urlParts['user'] : '';
        $pass   = isset($urlParts['pass']) ? ':' . $urlParts['pass'] : '';
        $pass   = ($user || $pass) ? "$pass@" : '';
        $path   = isset($urlParts['path']) ? $urlParts['path'] : '';

        return $scheme . $user . $pass . $host . $port . $path;
    }

    /**
     * Generic Request Wrapper.
     *
     * @param $method
     * @param $conf
     *
     * @return mixed
     */
    function doRequest($method, $conf, $params = array())
    {
        $this->requestId++;
        $url    = $this->unparseUrl($conf);
        $client = RpcClient::factory($url);
        return $client->send($client->request($this->requestId, $method, $params))->getRpcResult();
    }

    /**
     * Helper to only export a Gauge when the array key is present in the data.
     *
     * @param        $name
     * @param        $key
     * @param array  $response
     *
     * @return bool
     */
    function writeGauge($name, $key, array $response)
    {
        if (array_key_exists($key, $response)) {
            $this->dd->gauge($name, $response[$key]);
            return true;
        } else {
            echo "Key " . $key . " not found in result\n";
        }
    }

    function ethGaugeRequest($method, $conf, $params = array())
    {
        $response = $this->doRequest($method, $conf, $params);
        if (!empty($response)) {
            $this->dd->gauge('eth.' . $method, hexdec($response));
        }
    }

    /**
     * @param $conf
     */
    public function mainBtc($conf)
    {
        $response = $this->doRequest('getinfo', $conf);

        if (is_array($response)) {
            $this->writeGauge('btc.connections', 'connections', $response);
            $this->writeGauge('btc.difficulty', 'difficulty', $response);
            $this->writeGauge('btc.blockcount', 'blocks', $response);
        }

        $response = $this->doRequest('getnettotals', $conf);

        if (is_array($response)) {
            $this->writeGauge('btc.net.in', 'totalbytesrecv', $response);
            $this->writeGauge('btc.net.out', 'totalbytessent', $response);

            if (file_exists(self::BTC_CACHE_FILE)) {

                $lastData = unserialize(file_get_contents(self::BTC_CACHE_FILE));

                // divide by 1000 to get seconds instead of milliseconds.
                $timeDiff = ($response['timemillis'] - $lastData['timemillis']) / 1000;
                $inDiff   = $response['totalbytesrecv'] - $lastData['totalbytesrecv'];
                $outDiff  = $response['totalbytessent'] - $lastData['totalbytessent'];

                $this->dd->gauge('btc.net.in_sec', $inDiff / $timeDiff);
                $this->dd->gauge('btc.net.out_sec', $outDiff / $timeDiff);
            }

            file_put_contents(self::BTC_CACHE_FILE, serialize($response));

        }
    }

    public function mainEth($conf)
    {

        $this->ethGaugeRequest('net_peerCount', $conf);
        $this->ethGaugeRequest('eth_gasPrice', $conf);

        $response = $this->doRequest('eth_getBlockByNumber',$conf,array('latest',false));

        $this->dd->gauge('eth.latestblock' , hexdec($response['number']));
        $this->dd->gauge('eth.difficulty' , hexdec($response['difficulty']));
        $this->dd->gauge('eth.totalDifficulty' , hexdec($response['totalDifficulty']));
        $this->dd->gauge('eth.gasLimit' , hexdec($response['gasLimit']));
        $this->dd->gauge('eth.transactionCount' , count($response['transactions']));
        $this->dd->gauge('eth.uncleCount' , count($response['uncles']));
    }
}

$x = new btcDog();
$x->mainBtc($confBtc);
$x->mainEth($confEth);