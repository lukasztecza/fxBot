<?php
namespace TinyApp\Model\Strategy;

use TinyApp\Model\Strategy\RigidStrategyAbstract;
use TinyApp\Model\Service\PriceService;
use TinyApp\Model\Strategy\TrendingTrait;
use TinyApp\Model\Strategy\DeviationTrait;

class RigidTrendingDeviationStrategyPattern extends RigidStrategyAbstract
{
    use TrendingTrait;
    use DeviationTrait;

    private $priceService;
    private $fastAveragePeriod;
    private $slowAveragePeriod;

    public function __construct(PriceService $priceService, array $params)
    {
        if (
            empty($params['rigidStopLoss']) ||
            empty($params['takeProfitMultiplier']) ||
            empty($params['instrument']) ||
            empty($params['extremumRange']) ||
            empty($params['fastAveragePeriod']) ||
            empty($params['slowAveragePeriod'])
        ) {
            throw new \Exception('Got wrong params ' . var_export($params, true));
        }

        $this->priceService = $priceService;
        $this->extremumRange = $params['extremumRange'];
        $this->fastAveragePeriod = $params['fastAveragePeriod'];
        $this->slowAveragePeriod = $params['slowAveragePeriod'];
        parent::__construct($params['rigidStopLoss'], $params['takeProfitMultiplier'], $params['instrument']);
    }

    protected function getDirection(string $currentDateTime = null, string $selectedInstrument = null) : int
    {
        $lastPrices = $this->priceService->getLastPricesByPeriod($selectedInstrument, 'P7D', $currentDateTime);

        $trend = $this->getTrend($lastPrices, $this->extremumRange);
        $deviation = $this->getDeviation($lastPrices, $this->fastAveragePeriod, $this->slowAveragePeriod);

        switch (true) {
            case $trend === 1 && $deviation === 1:
                return 1;
            case $trend === -1 && $deviation === -1:
                return -1;
            default:
                return 0;
        }
    }
}
