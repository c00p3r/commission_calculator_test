<?php

use App\BinLookupService;
use App\CommissionCalculator;
use App\ExchangeRateService;

$loader = require __DIR__.'/vendor/autoload.php';

$filePath = $argv[1] ?? null;
$apiKey = $argv[2] ?? null;

if (!$filePath) {
    die('Please provide the filepath as first argument');
}

if (!$apiKey) {
    die('Please provide APILayer API key as second argument. https://apilayer.com/marketplace/exchangerates_data-api');
}

$calc = new CommissionCalculator(new ExchangeRateService($apiKey), new BinLookupService());

/*
 * Calculate commissions for valid non-empty rows.
 * Stores errors for invalid rows.
 * Skips empty lines.
 * */
$calc->calculateCommissions($filePath);

foreach ($calc->getCommissions() as $commission) {
    echo $commission . PHP_EOL;
}

foreach ($calc->getErrors() as $line => $error) {
    echo "Error (line $line): $error \n";
}