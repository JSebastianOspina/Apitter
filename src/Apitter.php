<?php

namespace Ospina\Apitter;

use Exception;
use JsonException;
use Ospina\CurlCobain\CurlCobain;

class Apitter
{
    private string $clientId;
    private string $clientSecret;
    private string $callbackUrl;

    /**
     * @param string $clientId
     * @param string $clientSecret
     * @param string $callbackUrl
     */
    public function __construct(string $clientId, string $clientSecret, string $callbackUrl)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->callbackUrl = $callbackUrl;
    }

    /**
     * @throws Exception
     */
    public function constructAuthorizeURL(array $scopes = []): array
    {
        $baseUrl = 'https://twitter.com/i/oauth2/authorize';

        //The scopes have to be passed as single string array elements.
        $scopesString = '';
        foreach ($scopes as $scope) {
            $scopesString .= $scope . '%20';
        }

        //These are the mandatory query params, if one of them change
        //the modification should be done directly on this array
        $codeChallenge = bin2hex(random_bytes(5));
        $mandatoryFields = [
            'response_type' => 'code',
            'state' => 'state',
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'plain',
            'client_id' => $this->clientId,
            'redirect_uri' => $this->callbackUrl,
            'scope' => $scopesString
        ];

        //Generate url using all the fields as query params
        $finalUrl = $baseUrl . '?';
        foreach ($mandatoryFields as $field => $value) {
            $finalUrl .= "$field=$value&";
        }
        return [
            'url' => $finalUrl,
            'code_challenge' => $codeChallenge
        ];
    }

    /**
     * @throws JsonException
     */
    public function getAccessToken(string $authorizationCode,string $codeChallenge)
    {
        $oauth2Url = 'https://api.twitter.com/2/oauth2/token';

        //Create curl object
        $curl = new CurlCobain($oauth2Url, 'POST');
        //prepare auth format
        $password = $this->clientId . ":" . $this->clientSecret;
        $auth = base64_encode($password);
        //set it
        $curl->setHeadersAsArray(
            [
                "Authorization" => "Basic $auth"
            ]
        );
        //required fields
        $fields = [
            'grant_type' => 'authorization_code',
            'code_verifier' => $codeChallenge,
            'redirect_uri' => $this->callbackUrl,
            'client_id' => $this->clientId,
            'code' => $authorizationCode,
        ];
        $curl->setDataAsFormUrlEncoded($fields);

        $response = $curl->makeRequest();
        return json_decode($response, false, 512, JSON_THROW_ON_ERROR);
    }

}