<?php
/**
 *
 */
use League\StatsD\Client as Statsd;

require('vendor/autoload.php');
require('config.php');

define('CACHE_FILE', 'transfers.cache');


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
function doRequest($method, $conf)
{
    static $requestId = 0;
    $requestId++;
    $url      = unparseUrl($conf);
    $response = (new JsonRpcCurl())
        ->setUrl($url)// server url with gateway path
        ->setId($requestId)// request ID (important for batch/async)
        ->setMethod($method)// requested service
        ->send();
    return $response;
}

/**
 * Helper to only export a Gauge when the array key is present in the data.
 *
 * @param        $name
 * @param        $key
 * @param array  $response
 * @param Statsd $dd
 */
function writeGauge($name, $key, array $response, Statsd $dd)
{
    if (array_key_exists($key, $response)) {
        $dd->gauge($name, $response[$key]);
        return true;
    } else {
        echo "Key " . $key . " not found in result\n";
    }
}

/**
 * Init StatsD Client.
 */
$dd = new Statsd();

$response = doRequest('getinfo', $conf);

if (is_array($response)) {
    writeGauge('btc.connections', 'connections', $response, $dd);
    writeGauge('btc.difficulty', 'difficulty', $response, $dd);
    writeGauge('btc.blockcount', 'blockcount', $response, $dd);
}

$response = doRequest('getnettotals', $conf);

if (is_array($response)) {
    writeGauge('btc.net.in', 'totalbytesrecv', $response, $dd);
    writeGauge('btc.net.out', 'totalbytessent', $response, $dd);

    if (file_exists(CACHE_FILE)) {

        $lastData = unserialize(file_get_contents(CACHE_FILE));

        // divide by 1000 to get seconds instead of milliseconds.
        $timeDiff = ($response['timemillis'] - $lastData['timemillis']) / 1000;
        $inDiff   = $response['totalbytesrecv'] - $lastData['totalbytesrecv'];
        $outDiff  = $response['totalbytessent'] - $lastData['totalbytessent'];

        $dd->gauge('btc.net.in_sec', $inDiff / $timeDiff);
        $dd->gauge('btc.net.out_sec', $outDiff / $timeDiff);
    }

    file_put_contents(CACHE_FILE, serialize($response));

}