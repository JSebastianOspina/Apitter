<?php
require 'vendor/autoload.php';
session_start();

$clientId = 'ak52ZEJhRHR1N3BQcHdtekN6dGI6MTpjaQ';
$clientSecret = '4EbJAHGMR9qleEKP-9wFMSYl16uydSvLw9Z74vS1W8sXtNbJEQ';
$redirectUrl = 'http://localhost/apitter/callback.php';
$scopes = ['tweet.read', 'users.read', 'tweet.write', 'offline.access'];
$apitter = new \Ospina\Apitter\Apitter($clientId, $clientSecret, $redirectUrl);


$url = $apitter->constructAuthorizeURL($scopes);
$_SESSION['code_challenge'] = $url['code_challenge'];
print($url['url']);
