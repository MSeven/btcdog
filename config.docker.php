<?php

/** @var Configuration $conf Uses parse_url() array keys */
$conf = array(
    'scheme' => 'http',
    'host'   => '127.0.0.1',
    'port'   => '8332',
    'user'   => 'username',
    'pass'   => 'password',
    'path'   => '/'
);


foreach ($conf as $key => $value) {
    $envval = getenv(strtoupper('BD_' . $key));
    if (!empty($envval)) {
        $conf[$key] = $envval;
    }
}