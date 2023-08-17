<?php

namespace Ospina\Apitter;

use Exception;
use JsonException;
use Ospina\CurlCobain\CurlCobain;

/**
 *
 */
class Apitter
{
    /**
     * @var string
     */
    private string $clientId;
    /**
     * @var string
     */
    private string $clientSecret;
    /**
     * @var string
     */
    private string $callbackUrl;
    /**
     * @var string
     */
    private string $bearerToken;
    /**
     * @var string
     */
    private string $baseUrl = 'https://api.twitter.com/2';

    /**
     * @param string $clientId
     * @param string $clientSecret
     * @param string $callbackUrl
     */
    public function __construct(string $clientId, string $clientSecret, string $callbackUrl = '')
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
     * @throws TwitterException
     */
    public function getAccessToken(string $authorizationCode, string $codeChallenge)
    {
        $oauth2Url = 'https://api.twitter.com/2/oauth2/token';
        $fields = [
            'grant_type' => 'authorization_code',
            'code_verifier' => $codeChallenge,
            'redirect_uri' => $this->callbackUrl,
            'client_id' => $this->clientId,
            'code' => $authorizationCode,
        ];
        return $this->makeBasicAuthRequest($oauth2Url, 'POST', $fields);
    }

    /**
     * @param $endpoint
     * @param $method
     * @param $params
     * @return mixed
     * @throws JsonException
     * @throws TwitterException
     */
    private function makeBasicAuthRequest($endpoint, $method, $params = null)
    {
        $curl = new CurlCobain($endpoint, $method);
        $curl->setHeadersAsArray($this->getBasicAuthHeaders());
        if ($params !== null) {
            $curl->setDataAsFormUrlEncoded($params);
        }

        $response = $curl->makeRequest();

        $responseObject = json_decode($response, false, 512, JSON_THROW_ON_ERROR);
        //if it has error throw exception
        $this->throwException($responseObject, $curl->getStatusCode());

        return $responseObject;
    }

    /**
     * @return string[]
     */
    private function getBasicAuthHeaders()
    {
        $password = $this->clientId . ":" . $this->clientSecret;
        $auth = base64_encode($password);
        return [
            "Authorization" => "Basic $auth"
        ];
    }

    public function throwException($responseObject, int $status): void
    {
        if ($status === 401) {
            throw new UnauthenticatedTwitterException($responseObject, $status);
        } elseif ($status !== 200) {
            throw new TwitterException($responseObject, $status);
        }
    }

    /**
     * @param string $bearer
     * @return void
     */
    public function setBearerToken(string $bearer): void
    {
        $this->bearerToken = $bearer;
    }

    /**
     * @throws JsonException
     */
    public function userLookupMe()
    {
        return $this->me();
    }

    /**
     * @throws JsonException
     */
    public function me()
    {
        $endpoint = 'users/me';
        return $this->makeAuthorizedRequest($endpoint, 'GET')->data;
    }

    /**
     * @throws JsonException
     * @throws TwitterException
     */
    public function makeAuthorizedRequest($endpoint, $method, $params = null, $query = null)
    {
        if ($this->bearerToken === '') {
            throw new \RuntimeException('You must provide a valid bearer token');
        }
        $curl = new CurlCobain("$this->baseUrl/$endpoint", $method);
        if ($params !== null) {
            $curl->setDataAsJson($params);
        }
        if ($query !== null) {
            $curl->setQueryParamsAsArray($query);
        }
        $curl->setHeader('Authorization', 'Bearer ' . $this->bearerToken);

        $response = $curl->makeRequest();

        $responseObject = json_decode($response, false, 512, JSON_THROW_ON_ERROR);
        //if it has error throw exception
        if ($curl->getStatusCode() === 401) {
            throw new UnauthenticatedTwitterException($responseObject, $curl->getStatusCode());
        }

        if ($curl->getStatusCode() === 429) {
            throw new TooManyRequestException($responseObject, $curl->getStatusCode());
        }

        if ($curl->getStatusCode() !== 200) {
            throw new TwitterException($responseObject, $curl->getStatusCode());
        }

        return $responseObject;

    }

    /**
     * @throws JsonException
     * @throws TwitterException
     */
    public function RT($userId, $tweetId)
    {
        $endpoint = "users/$userId/retweets";
        $data = [
            'tweet_id' => $tweetId
        ];
        return $this->makeAuthorizedRequest($endpoint, 'POST', $data)->data;
    }

    /**
     * @param string $refreshToken
     * @return mixed |null
     * @throws JsonException
     * @throws TwitterException
     */
    public function extendBearerToken(string $refreshToken)
    {
        $endpoint = "https://api.twitter.com/2/oauth2/token";
        $data = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken
        ];
        $request = $this->makeBasicAuthRequest($endpoint, 'POST', $data);
        return $request;
    }

    /**
     * @throws JsonException
     * @throws TwitterException
     */
    public function unRT($userId, $tweetId)
    {
        $endpoint = "users/$userId/retweets/$tweetId";
        return $this->makeAuthorizedRequest($endpoint, 'DELETE');
    }


}
