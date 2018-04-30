<?php
namespace TinyApp\Model\Strategy;

use TinyApp\Model\Strategy\RigidStrategyAbstract;
use TinyApp\Model\Service\PriceService;
use TinyApp\Model\Service\IndicatorService;

class RigidTrendingStrategy extends RigidStrategyAbstract
{
    private const RIGID_STOP_LOSS = 0.0025;
    private const TAKE_PROFIT_MULTIPLIER = 5;
    private const INSTRUMENT = 'USD_CAD';
    private const LAST_PRICES_PERIOD = 'P7D';
    private const EXTREMUM_RANGE = 12;

    private $priceService;
    private $extremumRange;
    private $useCached;

    public function __construct(array $instruments, PriceService $priceService, IndicatorService $indicatorService, $params)
    {
        $this->priceService = $priceService;
        $this->extremumRange = $params['extremumRange'] ?? self::EXTREMUM_RANGE;
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
        return $this->getChannelDirection($lastPrices, $this->extremumRange);
    }
}