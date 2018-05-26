<?php
namespace TinyApp\Model\Strategy;

use TinyApp\Model\Strategy\RigidStrategyAbstract;
use TinyApp\Model\Service\PriceService;
use TinyApp\Model\Service\IndicatorService;

class RigidLongDeviationAverageDistanceStrategy extends RigidStrategyAbstract
{
    private $priceService;
    private $longFastAverage;
    private $longSlowAverage;
    private $signalFastAverage;

    public function __construct(array $instruments, PriceService $priceService, IndicatorService $indicatorService, $params)
    {
        if (
            !isset($params['longFastAverage']) ||
            !isset($params['longSlowAverage']) ||
            !isset($params['signalFastAverage']) ||
            !isset($params['lastPricesPeriod']) ||
            !isset($params['rigidStopLoss']) ||
            !isset($params['takeProfitMultiplier']) ||
            !isset($params['instrument'])
        ) {
            throw new \Exception('Could not create strategy due to missing params');
        }

        $this->priceService = $priceService;
        $this->longFastAverage = $params['longFastAverage'];
        $this->longSlowAverage = $params['longSlowAverage'];
        $this->signalFastAverage = $params['signalFastAverage'];
        $this->lastPricesPeriod = $params['lastPricesPeriod'];

        parent::__construct($params['rigidStopLoss'], $params['takeProfitMultiplier'], $params['instrument']);
    }

    protected function getDirection(string $currentDateTime = null, string $selectedInstrument = null) : int
    {
        $lastPrices = $this->priceService->getLastPricesByPeriod($selectedInstrument, $this->lastPricesPeriod, $currentDateTime);
        $averages = $this->getAveragesByPeriods($lastPrices, [
            'current' => 1,
            'signal' => $this->signalFastAverage,
            'fast' => $this->longFastAverage,
            'slow' => $this->longSlowAverage
        ]);

        switch (true) {
            case !isset($averages['current']) || !isset($averages['signal']) || !isset($averages['fast']) || !isset($averages['slow']):
                return 0;
            case (
                ($averages['fast'] > $averages['slow']) &&
                ($averages['signal'] < $averages['fast']) &&
                ($averages['signal'] > $averages['slow']) &&
                ($averages['current'] > $averages['signal'])
            ):
                return -1;
            case (
                ($averages['fast'] < $averages['slow']) &&
                ($averages['signal'] > $averages['fast']) &&
                ($averages['signal'] < $averages['slow']) &&
                ($averages['current'] < $averages['signal'])
            ):
                return 1;
            default:
                return 0;
        }
    }
}
