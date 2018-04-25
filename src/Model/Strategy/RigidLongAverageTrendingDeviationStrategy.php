<?php
namespace TinyApp\Model\Strategy;

use TinyApp\Model\Strategy\RigidStrategyAbstract;
use TinyApp\Model\Service\PriceService;
use TinyApp\Model\Service\IndicatorService;

class RigidLongAverageTrendingDeviationStrategy extends RigidStrategyAbstract
{
    private const RIGID_STOP_LOSS = 0.0025;
    private const TAKE_PROFIT_MULTIPLIER = 5;
    private const INSTRUMENT = 'USD_CAD';
    private const LAST_PRICES_PERIOD = 'P7D';
    private const LONG_FAST_AVERAGE = 200;
    private const LONG_SLOW_AVERAGE = 400;
    private const EXTREMUM_RANGE = 12;
    private const SIGNAL_FAST_AVERAGE = 20;
    private const SIGNAL_SLOW_AVERAGE = 40;

    private $priceService;
    private $longFastAverage;
    private $longSlowAverage;
    private $extremumRange;
    private $signalFastAverage;
    private $signalSlowAverage;

    public function __construct(array $instruments, PriceService $priceService, IndicatorService $indicatorService, $params)
    {
        $this->priceService = $priceService;
        $this->longFastAverage = $params['longFastAverage'] ?? self::LONG_FAST_AVERAGE;
        $this->longSlowAverage = $params['longSlowAverage'] ?? self::LONG_SLOW_AVERAGE;
        $this->extremumRange = $params['extremumRange'] ?? self::EXTREMUM_RANGE;
        $this->signalFastAverage = $params['signalFastAverage'] ?? self::SIGNAL_FAST_AVERAGE;
        $this->signalSlowAverage = $params['signalSlowAverage'] ?? self::SIGNAL_SLOW_AVERAGE;

        parent::__construct(
            ($params['rigidStopLoss'] ?? self::RIGID_STOP_LOSS),
            ($params['takeProfitMultiplier'] ?? self::TAKE_PROFIT_MULTIPLIER),
            ($params['instrument'] ?? self::INSTRUMENT)
        );
    }

    protected function getDirection(string $currentDateTime = null, string $selectedInstrument = null) : int
    {
        $lastPrices = $this->priceService->getLastPricesByPeriod($selectedInstrument, self::LAST_PRICES_PERIOD, $currentDateTime);
        $longAverageDirection = $this->getLongAverageDirection($lastPrices, $this->longFastAverage, $this->longSlowAverage, false);
        $channelDirection = $this->getChannelDirection($lastPrices, $this->extremumRange);
        $deviationDirection = $this->getDeviationDirection($lastPrices, $this->signalFastAverage, $this->signalSlowAverage);

        switch (true) {
            case $longAverageDirection === 1 && $channelDirection === 1 && $deviationDirection === 1:
                return 1;
            case $longAverageDirection === -1 && $channelDirection === -1 && $deviationDirection === -1:
                return -1;
            default:
                return 0;
        }
    }

    private function getLongAverageDirection(array $lastPrices, int $fast, int $slow, bool $followTrend) : int
    {
        $averages = $this->getAveragesByPeriods($lastPrices, ['fast' => $fast, 'slow' => $slow]);
        switch (true) {
            case $averages['fast'] > $averages['slow']:
                return $followTrend ? 1 : -1;
            case $averages['fast'] < $averages['slow']:
                return $followTrend ? -1 : 1;
            default:
                return 0;
        }
    }
}
