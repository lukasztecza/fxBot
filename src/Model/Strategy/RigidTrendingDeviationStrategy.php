<?php
namespace TinyApp\Model\Strategy;

use TinyApp\Model\Strategy\RigidStrategyAbstract;
use TinyApp\Model\Service\PriceService;
use TinyApp\Model\Service\IndicatorService;

class RigidTrendingDeviationStrategy extends RigidStrategyAbstract
{
    private const RIGID_STOP_LOSS = 0.0025;
    private const TAKE_PROFIT_MULTIPLIER = 5;
    private const INSTRUMENT = 'USD_CAD';
    private const LAST_PRICES_PERIOD = 'P7D';
    private const EXTREMUM_RANGE = 12;
    private const SIGNAL_FAST_AVERAGE = 20;
    private const SIGNAL_SLOW_AVERAGE = 40;

    private $priceService;
    private $extremumRange;
    private $signalFastAverage;
    private $signalSlowAverage;
    private $useCached;

    public function __construct(array $instruments, PriceService $priceService, IndicatorService $indicatorService, $params)
    {
        $this->priceService = $priceService;
        $this->extremumRange = $params['extremumRange'] ?? self::EXTREMUM_RANGE;
        $this->signalFastAverage = $params['signalFastAverage'] ?? self::SIGNAL_FAST_AVERAGE;
        $this->signalSlowAverage = $params['signalSlowAverage'] ?? self::SIGNAL_SLOW_AVERAGE;
        $this->useCached = $params['useCached'] ?? false;

        parent::__construct(
            ($params['rigidStopLoss'] ?? self::RIGID_STOP_LOSS),
            ($params['takeProfitMultiplier'] ?? self::TAKE_PROFIT_MULTIPLIER),
            ($params['instrument'] ?? self::INSTRUMENT)
        );
    }

    protected function getDirection(string $currentDateTime = null, string $selectedInstrument = null) : int
    {
        $lastPrices = $this->priceService->getLastPricesByPeriod($selectedInstrument, self::LAST_PRICES_PERIOD, $currentDateTime, $this->useCached);
        $channelDirection = $this->getChannelDirection($lastPrices, $this->extremumRange);
        $deviationDirection = $this->getDeviationDirection($lastPrices, $this->signalFastAverage, $this->signalSlowAverage);

        switch (true) {
            case $channelDirection === 1 && $deviationDirection === 1:
                return 1;
            case $channelDirection === -1 && $deviationDirection === -1:
                return -1;
            default:
                return 0;
        }
    }
}
