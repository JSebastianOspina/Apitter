<?php

$request = new CurlCobain('https://api-m.sandbox.paypal.com/v1/oauth2/token');
$request->setHeaders('Accept','application/json');
$request->setHeaders('Accept-Language','en_US');
$data = 'grant_type=client_credentials';
$request->setUserAndPassword('client_id','secret');
var_dump($request->post($data));
