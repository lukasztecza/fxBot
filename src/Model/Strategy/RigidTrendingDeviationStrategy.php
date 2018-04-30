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
    private $lastPricesPeriod;

    public function __construct(array $instruments, PriceService $priceService, IndicatorService $indicatorService, $params)
    {
        if (
            !isset($params['extremumRange']) ||
            !isset($params['signalFastAverage']) ||
            !isset($params['signalSlowAverage']) ||
            !isset($params['useCached']) ||
            !isset($params['lastPricesPeriod']) ||
            !isset($params['rigidStopLoss']) ||
            !isset($params['takeProfitMultiplier']) ||
            !isset($params['instrument'])
        ) {
            throw new \Exception('Could not create strategy due to missing params');
        }

        $this->priceService = $priceService;
        $this->extremumRange = $params['extremumRange'];
        $this->signalFastAverage = $params['signalFastAverage'];
        $this->signalSlowAverage = $params['signalSlowAverage'];
        $this->useCached = $params['useCached'] ?? false;
        $this->lastPricesPeriod = $params['lastPricesPeriod'];

        parent::__construct($params['rigidStopLoss'], $params['takeProfitMultiplier'], $params['instrument']);
    }

    protected function getDirection(string $currentDateTime = null, string $selectedInstrument = null) : int
    {
        $lastPrices = $this->priceService->getLastPricesByPeriod($selectedInstrument, $this->lastPricesPeriod, $currentDateTime, $this->useCached);
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
