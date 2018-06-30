<?php

use FxBot\Model\Strategy\StrategyFactory;
use Codeception\Stub;
use FxBot\Model\Service\PriceService;
use FxBot\Model\Service\IndicatorService;
use FxBot\Model\Strategy\RigidRandomStrategy;


class StrategyCest
{
    public $strategyFactory;

    public function before()
    {
        $instruments = [];
        $priceServiceMock = Stub::makeEmpty(PriceService::class, [
        ]);
        $indicatorServiceMock = Stub::makeEmpty(IndicatorService::class, [
        ]);
        $this->strategyFactory = new StrategyFactory($instruments, $priceServiceMock, $indicatorServiceMock);
    }

    public function getStrategyTest(UnitTester$I)
    {
        $sampleStrategy = $this->strategyFactory->getStrategy(RigidRandomStrategy::class, [
            'rigidStopLoss' => 0.002,
            'takeProfitMultiplier' => 5,
            'homeCurrency' => 'CAD',
            'lossLockerFactor' => 1,
            'rigidStopLoss' => 0.001,
            'instrument' => 'EUR_USD',
            'singleTransactionRisk' => 0.01
        ]);

        $I->assertTrue($sampleStrategy instanceof RigidRandomStrategy);
    }

    public function getAveragesByPeriodsTest(UnitTester$I)
    {
        $sampleStrategy = $this->strategyFactory->getStrategy(RigidRandomStrategy::class, [
            'rigidStopLoss' => 0.002,
            'takeProfitMultiplier' => 5,
            'homeCurrency' => 'CAD',
            'lossLockerFactor' => 1,
            'rigidStopLoss' => 0.001,
            'instrument' => 'EUR_USD',
            'singleTransactionRisk' => 0.01
        ]);

        $prices = [
             ['high' => 11, 'low' => 9],
             ['high' => 11, 'low' => 9],
             ['high' => 21, 'low' => 19],
             ['high' => 21, 'low' => 19],
        ];

        $averages = $I->callNonPublic(
            $sampleStrategy,
            'getAveragesByPeriods',
            [$prices, ['fast' => 2, 'slow' => 4]]
        );
        $I->assertEquals($averages['fast'], 10);
        $I->assertEquals($averages['slow'], 15);
    }
}
