<?php

namespace App;

use RuntimeException;

class CommissionCalculator
{
    private ExchangeRateService $exchangeRateService;
    private BinLookupService $binLookupService;
    private array $euCountries = [
        'AT', 'BE', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 'ES', 'FI',
        'FR', 'GR', 'HR', 'HU', 'IE', 'IT', 'LT', 'LU', 'LV', 'MT',
        'NL', 'PO', 'PT', 'RO', 'SE', 'SI', 'SK'
    ];
    private array $rates = [];
    private array $errors = [];
    private array $commissions = [];

    public function __construct(ExchangeRateService $exchangeRateService, BinLookupService $binLookupService)
    {
        $this->exchangeRateService = $exchangeRateService;
        $this->binLookupService = $binLookupService;
    }

    public function getCommissions(): array
    {
        return $this->commissions;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function calculateCommissions(string $filePath): void
    {
        if (!$file = fopen($filePath, 'r')) {
            throw new RuntimeException('Error opening file');
        }

        $this->rates = $this->exchangeRateService->getExchangeRates();

        $this->commissions = [];
        $lineCount = 1;
        while (($line = fgets($file)) !== false) {
            // skip empty lines
            if (!$line = trim($line)) {
                continue;
            }

            try {
                $this->commissions[$lineCount] = $this->processLine($line);
            } catch (RuntimeException $e) {
                $this->errors[$lineCount] = $e->getMessage();
            }

            $lineCount++;
        }

        fclose($file);
    }

    public function processLine(string $line): float
    {
        [$binNumber, $amount, $currency] = $this->parseJSONLine($line);

        $commissionRate = $this->getCommissionRate($binNumber);

        $exchangeRate = $this->getExchangeRate($currency);

        $commission = $amount / $exchangeRate * $commissionRate;

        return ceil($commission * 100) / 100;
    }

    private function parseJSONLine(string $line): array
    {
        $transaction = json_decode($line, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Error decoding transaction data');
        }

        $binNumber = $transaction['bin'] ?? null;
        $amount = $transaction['amount'] ?? null;
        $currency = $transaction['currency'] ?? null;

        if (empty($binNumber) || empty($amount) || empty($currency)) {
            throw new RuntimeException('Missing required transaction data');
        }

        return [$binNumber, $amount, $currency];
    }

    private function getCommissionRate(int $binNumber): float
    {
        $cardData = $this->binLookupService->getCardData($binNumber);

        if (!$countryCode = $cardData['country']['alpha2'] ?? null) {
            throw new RuntimeException('Failed to fetch country code from BIN data');
        }

        return in_array($countryCode, $this->euCountries)
            ? 0.01
            : 0.02;
    }

    public function getExchangeRate(string $currency): float
    {
        if ($currency === 'EUR') {
            return 1;
        }

        if (!$rate = $this->rates[$currency] ?? null) {
            throw new RuntimeException("Exchange rate for current currency $currency not found");
        }

        return (float)$rate;
    }
}