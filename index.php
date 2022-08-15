<?php
require 'vendor/autoload.php';
session_start();

$clientId = 'YOUR CLIENT ID';
$clientSecret = 'YOUR SECRET';
$redirectUrl = 'http://localhost/apitter/callback.php';
$scopes = ['tweet.read', 'users.read', 'tweet.write', 'offline.access'];
$apitter = new \Ospina\Apitter\Apitter($clientId, $clientSecret, $redirectUrl);


$url = $apitter->constructAuthorizeURL($scopes);
$_SESSION['code_challenge'] = $url['code_challenge'];
print($url['url']);
