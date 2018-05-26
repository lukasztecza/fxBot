<?php
namespace TinyApp\Model\Strategy;

use TinyApp\Model\Strategy\RigidStrategyAbstract;
use TinyApp\Model\Service\PriceService;
use TinyApp\Model\Service\IndicatorService;

class RigidAverageDistanceDeviationStrategy extends RigidStrategyAbstract
{
    private $priceService;
    private $longFastAverage;
    private $longSlowAverage;
    private $signalFastAverage;
    private $signalSlowAverage;
    private $followTrend;
    private $lastPricesPeriod;

    public function __construct(array $instruments, PriceService $priceService, IndicatorService $indicatorService, $params)
    {
        if (
            !isset($params['longFastAverage']) ||
            !isset($params['longSlowAverage']) ||
            !isset($params['signalFastAverage']) ||
            !isset($params['signalSlowAverage']) ||
            !isset($params['followTrend']) ||
            !isset($params['lastPricesPeriod']) ||
            !isset($params['rigidStopLoss']) ||
            !isset($params['takeProfitMultiplier']) ||
            !isset($params['lossLockerFactor']) ||
            !isset($params['instrument'])
        ) {
            throw new \Exception('Could not create strategy due to missing params');
        }

        $this->priceService = $priceService;
        $this->longFastAverage = $params['longFastAverage'];
        $this->longSlowAverage = $params['longSlowAverage'];
        $this->signalFastAverage = $params['signalFastAverage'];
        $this->signalSlowAverage = $params['signalSlowAverage'];
        $this->followTrend = $params['followTrend'];
        $this->lastPricesPeriod = $params['lastPricesPeriod'];

        parent::__construct($params['rigidStopLoss'], $params['takeProfitMultiplier'], $params['lossLockerFactor'], $params['instrument']);
    }

    protected function getDirection(string $currentDateTime = null, string $selectedInstrument = null) : int
    {
        $lastPrices = $this->priceService->getLastPricesByPeriod($selectedInstrument, $this->lastPricesPeriod, $currentDateTime);
        $longAverageDirection = $this->getLongAverageDirection($lastPrices, $this->longFastAverage, $this->longSlowAverage, $this->followTrend);
        $deviationDirection = $this->getDeviationDirection($lastPrices, $this->signalFastAverage, $this->signalSlowAverage);

        switch (true) {
            case $longAverageDirection === 1 && $deviationDirection === 1:
                return 1;
            case $longAverageDirection === -1 && $deviationDirection === -1:
                return -1;
            default:
                return 0;
        }
    }

    private function getLongAverageDirection(array $lastPrices, int $fast, int $slow, bool $followTrend) : int
    {
        $averages = $this->getAveragesByPeriods($lastPrices, ['fast' => $fast, 'slow' => $slow]);
        switch (true) {
            case !isset($averages['fast']) || !isset($averages['slow']):
                return 0;
            case $averages['fast'] > $averages['slow']:
                return $this->followTrend ? 1 : -1;
            case $averages['fast'] < $averages['slow']:
                return $this->followTrend ? -1 : 1;
            default:
                return 0;
        }
    }
}
