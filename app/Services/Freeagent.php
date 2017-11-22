<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7;

use Carbon\Carbon;

use App\Models\OauthKey;

class Freeagent {

    public $provider;

    public $expires;

    public $accessToken;

    public $refreshToken;

    public function __construct()
    {
        $this->provider = new \CloudManaged\OAuth2\Client\Provider\FreeAgent([
            'sandbox' => config('freeagent.sandbox'),
            'clientId' => config('freeagent.client_id'),
            'clientSecret' => config('freeagent.client_secret'),
            'responseType' => config('freeagent.response_type'),
            'redirectUri' => config('freeagent.redirect_uri')
        ]);

        //get from database && refresh
        if ( $this->getTokensFromDatabase() ) {
            $this->getRefreshedToken();
        }

        $this->settings = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->accessToken,
                //'Content-Type' => 'application/json'
            ]
        ];

        $this->client = new Client([
            'base_uri' => $this->provider->baseURL,
        ]);
    }

    public function getCompany()
    {   
        $query = [];

        $response = $this->client->request('GET', "company", array_merge($this->settings,$query));

        return json_decode($response->getBody());
    }

    public function updateBankStatement($bankAccount, $csv)
    {   
        $query = [
            'bank_account' => $bankAccount
        ];

        $form_params = [
            'statement' => $csv
        ];

        $settings = array_merge($this->settings, [ 'query' => $query ], [ 'form_params' => $form_params ] );
    
        $response = $this->client->request('POST', "bank_transactions/statement", $settings);
            
        return json_decode($response->getBody());   
    }

    public function getTokensFromDatabase() {
        $tokens = OauthKey::where('service', 'freeagent')->first();

        if ( $tokens ) {
            $this->expires = $tokens->expires;
            $this->accessToken = $tokens->access_token;
            $this->refreshToken = $tokens->refresh_token;

            return true;
        }

        return false;
    }

    public function setTokensToDatabase($token) {
        $current = OauthKey::where('service', 'freeagent')->first();

        $fill = [
            'service' => 'freeagent',
            'uid' => ''
        ];

        if ( !empty( $token->accessToken ) ) {
             $fill['access_token'] = $token->accessToken;
        }

        if ( !empty( $token->expires ) ) {
             $fill['expires'] = Carbon::createFromTimestamp($token->expires);
        }

        if ( !empty( $token->refreshToken ) ) {
             $fill['refresh_token'] = $token->refreshToken;
        }

        if ( $current ) {
            $current->fill($fill)->save();
        } else {
            $current = OauthKey::create($fill);
        } 

        $this->expires = $current->expire;
        $this->accessToken = $current->access_token;
        $this->refreshToken = $current->refesh_token;
    }

    public function getAuthorizationUrl() {
        return $this->provider->getAuthorizationUrl();
     }

    public function getTokenFromCode($code)
    {
        $token = $this->provider->getAccessToken("authorization_code", [
            'code' => $code
        ]);

        $this->setTokensToDatabase($token);
    }

    public function getRefreshedToken()
    {   
        $token = $this->provider->getAccessToken('refresh_token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $this->refreshToken
        ]);
        $this->setTokensToDatabase($token);
    }
}