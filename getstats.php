<?php
/**
 *
 */
use League\StatsD\Client as Statsd;

require('vendor/autoload.php');
require('config.php');


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
    var_dump($requestId);
    $url      = unparseUrl($conf);
    $response = (new JsonRpcCurl())
        ->setUrl($url)// server url with gateway path
        ->setId($requestId)// request ID (important for batch/async)
        ->setMethod($method)// requested service
        ->send();
    var_dump($response);
    return $response;
}

$response = doRequest('getinfo', $conf);

$dd = new Statsd();
if (is_array($response)) {
    $dd->gauge('btc.connections', $response['connections']);
    $dd->gauge('btc.difficulty', $response['difficulty']);
    $dd->gauge('btc.blockcount', $response['blocks']);
}

$response = doRequest('getnettotals', $conf);

if (is_array($response)) {
    $dd->gauge('btc.net.in', $response['totalbytesrecv']);
    $dd->gauge('btc.net.out', $response['totalbytessent']);
}