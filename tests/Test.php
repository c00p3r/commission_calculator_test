<?php

namespace Tests;

use App\BinLookupService;
use App\CommissionCalculator;
use App\ExchangeRateService;
use PHPUnit\Framework\TestCase;

class Test extends TestCase
{
    public function testCalculateCommissions()
    {
        $filePath = __DIR__.'/../input.txt';
        $exchangeRateService = $this->createMock(ExchangeRateService::class);
        $binLookupService = $this->createMock(BinLookupService::class);

        $exchangeRateService->expects($this->once())
            ->method('getExchangeRates')
            ->willReturn([
                'USD' => 1.1,
                'GBP' => 0.9,
                'JPY' => 144,
            ]);

        $binLookupService->expects($this->exactly(5))
            ->method('getCardData')
            ->willReturnMap([
                [
                    45717360,
                    [
                        'country' => [
                            'alpha2' => 'DK'
                        ]
                    ]
                ],
                [
                    516793,
                    [
                        'country' => [
                            'alpha2' => 'LT'
                        ]
                    ]
                ],
                [
                    45417360,
                    [
                        'country' => [
                            'alpha2' => 'JP'
                        ]
                    ]
                ],
                [
                    41417360,
                    [
                        'country' => [
                            'alpha2' => 'US'
                        ]
                    ]
                ],
                [
                    4745030,
                    [
                        'country' => [
                            'alpha2' => 'GB'
                        ]
                    ]
                ],
            ]);

        $calculator = new CommissionCalculator($exchangeRateService, $binLookupService);

        $expectedCommissions = [
            1 => 1.0, // 100 / 1 * 0.01 = 1
            2 => 0.46, // 50 / 1.1 * 0.01 = 0.46
            3 => 1.39, // 10000 / 144 * 0.02 = 1.39
            4 => 2.37, // 130 / 1.1 * 0.02 = 2.37
            5 => 44.45, // 2000 / 0.9 * 0.02 = 44.45
        ];

        $calculator->calculateCommissions($filePath);

        $actualCommissions = $calculator->getCommissions();

        $this->assertEquals($expectedCommissions, $actualCommissions);
    }

    public function testInputHasErrors()
    {
        $filePath = 'input_test.txt';

        $input = " \n foobar";

        file_put_contents($filePath, $input);

        $exchangeRateService = $this->createMock(ExchangeRateService::class);
        $binLookupService = $this->createMock(BinLookupService::class);

        $calculator = new CommissionCalculator($exchangeRateService, $binLookupService);

        $calculator->calculateCommissions($filePath);

        $errors = $calculator->getErrors();

        $this->assertEquals([1 => 'Error decoding transaction data'], $errors);

        $input = json_encode([
            ['foo', 'bar']
        ]);

        file_put_contents($filePath, $input);

        $exchangeRateService = $this->createMock(ExchangeRateService::class);
        $binLookupService = $this->createMock(BinLookupService::class);

        $calculator = new CommissionCalculator($exchangeRateService, $binLookupService);

        $calculator->calculateCommissions($filePath);

        $errors = $calculator->getErrors();

        $this->assertEquals([1 => 'Missing required transaction data'], $errors);

        unlink($filePath);
    }
}