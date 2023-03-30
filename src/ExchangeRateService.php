<?php

namespace App;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use RuntimeException;

class ExchangeRateService
{
    private string $apiKey;
    private string $baseUrl;

    public function __construct(string $apiKey, string $baseUrl = 'https://api.apilayer.com/exchangerates_data/latest')
    {
        $this->apiKey = $apiKey;
        $this->baseUrl = $baseUrl;
    }

    public function getExchangeRates(string $baseCurrency = 'EUR'): array
    {
        $client = new Client();

        try {
            $response = $client->request('GET', $this->baseUrl, [
                'query' => [
                    'base' => $baseCurrency
                ],
                'headers' => [
                    'apikey' => $this->apiKey
                ]
            ]);
        } catch (GuzzleException $e) {
            throw new RuntimeException('Failed to fetch exchange rates data: ' . $e->getMessage());
        }

        $decodedResponse = json_decode($response->getBody()->getContents(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Failed to decode exchange rates data');
        }

        if (!$decodedResponse['success']) {
            throw new RuntimeException('Failed to fetch exchange rates data: ' . $decodedResponse['error']['info']);
        }

        return $decodedResponse['rates'];
    }
}