<?php
namespace FxBot\Model\Strategy;

use FxBot\Model\Strategy\RigidStrategyAbstract;
use FxBot\Model\Service\PriceService;
use FxBot\Model\Service\IndicatorService;

class RigidAverageTrendLongAverageDeviationStrategy extends RigidStrategyAbstract
{
    private $priceService;
    private $averageTrend;
    private $longFastAverage;
    private $longSlowAverage;
    private $signalFastAverage;
    private $signalSlowAverage;
    private $lastPricesPeriod;

    public function __construct(array $instruments, PriceService $priceService, IndicatorService $indicatorService, $params)
    {
        if (
            !isset($params['averageTrend']) ||
            !isset($params['longFastAverage']) ||
            !isset($params['longSlowAverage']) ||
            !isset($params['signalFastAverage']) ||
            !isset($params['signalSlowAverage']) ||
            !isset($params['lastPricesPeriod']) ||
            !isset($params['rigidStopLoss']) ||
            !isset($params['takeProfitMultiplier']) ||
            !isset($params['instrument'])
        ) {
            throw new \Exception('Could not create strategy due to missing params');
        }

        $this->priceService = $priceService;
        $this->averageTrend = $params['averageTrend'];
        $this->longFastAverage = $params['longFastAverage'];
        $this->longSlowAverage = $params['longSlowAverage'];
        $this->signalFastAverage = $params['signalFastAverage'];
        $this->signalSlowAverage = $params['signalSlowAverage'];
        $this->lastPricesPeriod = $params['lastPricesPeriod'];

        parent::__construct($params['rigidStopLoss'], $params['takeProfitMultiplier'], $params['instrument']);
    }

    protected function getDirection(string $currentDateTime = null, string $selectedInstrument = null) : int
    {
        $lastPrices = $this->priceService->getLastPricesByPeriod($selectedInstrument, $this->lastPricesPeriod, $currentDateTime);
        $longAverageDirection = $this->getLongAverageDirection(
            $lastPrices, $this->longFastAverage, $this->longSlowAverage, $this->averageTrend
        );
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

    private function getLongAverageDirection(array $lastPrices, int $fast, int $slow, int $trend) : int
    {
        $averages = $this->getAveragesByPeriods($lastPrices, ['fast' => $fast, 'slow' => $slow, 'trend' => $trend]);
        $fastSlowAverage = ($averages['fast'] + $averages['slow']) / 2;
        switch (true) {
            case !isset($averages['trend']) || !isset($averages['fast']) || !isset($averages['slow']):
                return 0;
            case $fastSlowAverage > $averages['trend'] && $averages['fast'] < $averages['slow']:
                return 1;
            case $fastSlowAverage < $averages['trend'] && $averages['fast'] > $averages['slow']:
                return -1;
            default:
                return 0;
        }
    }
}
