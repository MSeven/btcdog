<?php
/**
 *
 */
use Graze\GuzzleHttp\JsonRpc\Client as RpcClient;
use GuzzleHttp\Exception\TransferException;
use League\StatsD\Client as Statsd;

require('vendor/autoload.php');
require('config.php');


/**
 * Class btcDog
 */
class btcDog
{
    /**
     *
     */
    const ETH_CACHE_FILE = 'eth_transfers.cache';

    /**
     * @var Statsd
     */
    private $dd;
    /**
     * @var int
     */
    private $requestId;

    /**
     * btcDog constructor.
     */
    public function __construct()
    {
        /**
         * Init StatsD Client.
         */
        $this->dd = new Statsd();
        $this->requestId = 0;
    }

    /**
     * @param $urlParts
     * @return string
     */
    function unparseUrl($urlParts)
    {
        $scheme = isset($urlParts['scheme']) ? $urlParts['scheme'] . '://' : '';
        $host = isset($urlParts['host']) ? $urlParts['host'] : '';
        $port = isset($urlParts['port']) ? ':' . $urlParts['port'] : '';
        $user = isset($urlParts['user']) ? $urlParts['user'] : '';
        $pass = isset($urlParts['pass']) ? ':' . $urlParts['pass'] : '';
        $pass = ($user || $pass) ? "$pass@" : '';
        $path = isset($urlParts['path']) ? $urlParts['path'] : '';

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
        $url = $this->unparseUrl($conf);
        $client = RpcClient::factory($url);
        try {
            return $client->send($client->request($this->requestId, $method, $params))->getRpcResult();
        } catch (TransferException $e) {
            return [];
        }
    }

    /**
     * Helper to only export a Gauge when the array key is present in the data.
     *
     * @param        $name
     * @param        $key
     * @param array $response
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

    /**
     * @param $method
     * @param $conf
     * @param array $params
     */
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
    public function mainEth($conf)
    {

        $this->ethGaugeRequest('net_peerCount', $conf);
        $this->ethGaugeRequest('eth_gasPrice', $conf);

        $response = $this->doRequest('eth_getBlockByNumber', $conf, array('latest', false));

        if (array_key_exists('number', $response)) {
            $this->dd->gauge('eth.latestblock', hexdec($response['number']));
        }
        if (array_key_exists('difficulty', $response)) {
            $this->dd->gauge('eth.difficulty', hexdec($response['difficulty']));
        }
        if (array_key_exists('totalDifficulty', $response)) {
            $this->dd->gauge('eth.totalDifficulty', hexdec($response['totalDifficulty']));
        }
        if (array_key_exists('gasLimit', $response)) {
            $this->dd->gauge('eth.gasLimit', hexdec($response['gasLimit']));
        }
        if (array_key_exists('transactions', $response)) {
            $this->dd->gauge('eth.transactionCount', count($response['transactions']));
        }
        if (array_key_exists('uncles', $response)) {
            $this->dd->gauge('eth.uncleCount', count($response['uncles']));
        }
    }
}

$x = new btcDog();
$x->mainEth($confEth);
