<?php

namespace App\Traits;

use Illuminate\Support\Facades\Http;
use  Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

trait Api66ConTrait
{
    private $client;

    public function setNewInstance()
    {
        $baseUrl = "https://api.mym.com.pe:8886/appwsmym/api/v1/";
        //$baseUrl = "https://192.168.1.66:8886/appwsmym/api/v1/";
        $this->client = new Client([
            'max'             => 60,
            'strict'          => true,
            'referer'         => true,
            'protocols'       => ['https'],
            'track_redirects' => true,
            'base_uri'  => $baseUrl,
            'allow_redirects' => false,
            'timeout' => 0,
            'read_timeout' => 0,
            'connect_timeout' => 0,
            'expect'    => true,
            'headers'   => [
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ],
            'verify' => false
        ]);
    }

    public function requestGet($url, $variables = '')
    {
        try {
            $this->setNewInstance();
            $data       = ($variables != '') ? '?' . $variables : '';
            $response   = $this->client->get($url . $data);

            return $response->getBody();
        } catch (ClientException $e) {
            $error = $e->getResponse()->getBody()->getContents();
            return json_decode($error);
        }
    }

    public function dataPost($url, $data)
    {
        try {
            $this->setNewInstance();
            $response = $this->client->post($url, [
                'body' => json_encode($data)
            ]);
            $response = json_decode($response->getBody());
            return $response;
        } catch (ClientException $e) {
            $error = $e->getResponse()->getBody()->getContents();
            return json_decode($error);
        }
    }
}
