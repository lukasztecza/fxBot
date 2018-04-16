<?php
namespace TinyApp\Model\Strategy;

use TinyApp\Model\Strategy\RigidStrategyAbstract;
use TinyApp\Model\Service\PriceService;
use TinyApp\Model\Strategy\TrendingTrait;
use TinyApp\Model\Strategy\AverageDistanceTrait;

class RigidTrendingAverageDistanceStrategyPattern extends RigidStrategyAbstract
{
    use TrendingTrait;
    use AverageDistanceTrait;

    private $priceService;
    private $averageDistancePeriod;
    private $averageDistanceFactor;

    public function __construct(PriceService $priceService, array $params)
    {
        if (
            empty($params['rigidStopLoss']) ||
            empty($params['takeProfitMultiplier']) ||
            empty($params['instrument']) ||
            empty($params['extremumRange']) ||
            empty($params['averageDistancePeriod']) ||
            empty($params['averageDistanceFactor'])
        ) {
            throw new \Exception('Got wrong params ' . var_export($params, true));
        }

        $this->priceService = $priceService;
        $this->extremumRange = $params['extremumRange'];
        $this->averageDistancePeriod = $params['averageDistancePeriod'];
        $this->averageDistanceFactor = $params['averageDistanceFactor'];
        parent::__construct($params['rigidStopLoss'], $params['takeProfitMultiplier'], $params['instrument']);
    }

    protected function getDirection(string $currentDateTime = null, string $selectedInstrument = null) : int
    {
        $lastPrices = $this->priceService->getLastPricesByPeriod($selectedInstrument, 'P7D', $currentDateTime);

        $trend = $this->getTrend($lastPrices, $this->extremumRange);
        $distance = $this->getAverageDistance($lastPrices, $this->averageDistancePeriod, $this->averageDistanceFactor);

        switch (true) {
            case $trend === 1 && $distance === 1:
                return 1;
            case $trend === -1 && $distance === -1:
                return -1;
            default:
                return 0;
        }
    }
}
