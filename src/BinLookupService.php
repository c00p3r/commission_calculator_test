<?php

namespace App;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use RuntimeException;

class BinLookupService
{
    private string $baseUrl;

    public function __construct(string $baseUrl = 'https://lookup.binlist.net/')
    {
        $this->baseUrl = $baseUrl;
    }

    public function getCardData(int $binNumber): array
    {

        $client = new Client();

        try {
            $response = $client->request('GET', $this->baseUrl . $binNumber);
        } catch (GuzzleException $e) {
            throw new RuntimeException('Failed to fetch BIN data: ' . $e->getMessage());
        }

        $binData = json_decode($response->getBody()->getContents(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Error decoding BIN data');
        }

        return $binData;
    }
}