<?php
namespace TinyApp\Model\Strategy;

use TinyApp\Model\Strategy\RigidStrategyAbstract;
use TinyApp\Model\Strategy\TrendingTrait;
use TinyApp\Model\Strategy\LongAverageTrait;
use TinyApp\Model\Strategy\DeviationTrait;
use TinyApp\Model\Service\PriceService;

class RigidTrendingLongAverageDeviationStrategyPattern extends RigidStrategyAbstract
{
    use TrendingTrait;
    use LongAverageTrait;
    use DeviationTrait;

    private $priceService;
    private $extremumRange;
    private $signalFast;
    private $signalSlow;
    private $fastAverage;
    private $slowAverage;

    public function __construct(PriceService $priceService, array $params)
    {
        if (
            empty($params['rigidStopLoss']) ||
            empty($params['takeProfitMultiplier']) ||
            empty($params['instrument']) ||
            empty($params['extremumRange']) ||
            empty($params['signalFast']) ||
            empty($params['signalSlow']) ||
            empty($params['fastAverage']) ||
            empty($params['slowAverage'])
        ) {
            throw new \Exception('Got wrong params ' . var_export($params, true));
        }

        $this->priceService = $priceService;
        $this->extremumRange = $params['extremumRange'];
        $this->signalFast = $params['signalFast'];
        $this->signalSlow = $params['signalSlow'];
        $this->fastAverage = $params['fastAverage'];
        $this->slowAverage = $params['slowAverage'];
        parent::__construct($params['rigidStopLoss'], $params['takeProfitMultiplier'], $params['instrument']);
    }

    protected function getDirection(string $currentDateTime = null, string $selectedInstrument = null) : int
    {
        $lastPrices = $this->priceService->getLastPricesByPeriod($selectedInstrument, 'P10D', $currentDateTime);
        $longAverageDirection = $this->getLongAverageDirection($lastPrices, $this->fastAverage, $this->slowAverage);
        $trendingDirection = $this->getTrend($lastPrices, $this->extremumRange);
        $deviationDirection = $this->getDeviationDirection($lastPrices, $this->signalFast, $this->signalSlow);

        switch (true) {
            case $longAverageDirection === 1 && $trendingDirection === 1 && $deviationDirection === 1:
                return 1;
            case $longAverageDirection === -1 && $trendingDirection === -1 && $deviationDirection === -1:
                return -1;
            default:
                return 0;
        }
    }
}
