<?php
require 'vendor/autoload.php';
session_start();

$code = $_GET['code'];
$codeChallenge = $_SESSION['code_challenge'];

$clientId = 'YOUR CLIENT ID';
$clientSecret = 'YOUR SECRET';
$redirectUrl = 'http://localhost/apitter/callback.php';
$apitter = new \Ospina\Apitter\Apitter($clientId, $clientSecret, $redirectUrl);

$tokens = $apitter->getAccessToken($code, $codeChallenge);

//Set token required for endpoint
$apitter->setBearerToken($tokens->access_token);
$info = $apitter->me();

$rt = $apitter->unRT($info->id, '1558126768204177412');
print_r($rt);
print_r($info);
print_r($tokens);
