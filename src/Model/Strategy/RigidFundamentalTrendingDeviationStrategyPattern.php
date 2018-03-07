<?php
namespace TinyApp\Model\Strategy;

use TinyApp\Model\Strategy\RigidStrategyAbstract;
use TinyApp\Model\Service\PriceService;
use TinyApp\Model\Service\IndicatorService;
use TinyApp\Model\Strategy\IndicatorTrait;
use TinyApp\Model\Strategy\TrendingTrait;
use TinyApp\Model\Strategy\DeviationTrait;

class RigidFundamentalTrendingDeviationStrategyPattern extends RigidStrategyAbstract
{
    use FundamentalTrait;
    use TrendingTrait;
    use DeviationTrait;

    private $indicatorService;
    private $priceService;

    public function __construct(PriceService $priceService, IndicatorService $indicatorService, array $params)
    {
        if (empty($params['rigidStopLoss']) || empty($params['takeProfitMultiplier']) || empty($params['instrument'])) {
            throw new \Exception('Got wrong params ' . var_export($params, true));
        }

        $this->priceService = $priceService;
        $this->indicatorService = $indicatorService;
        parent::__construct($params['rigidStopLoss'], $params['takeProfitMultiplier'], $params['instrument']);
    }

    protected function getDirection(string $currentDateTime = null, string $selectedInstrument = null) : int
    {
        $lastPrices = $this->priceService->getLastPricesByPeriod($selectedInstrument, 'P7D', $currentDateTime);

        $fundamental = $this->getFundamental($selectedInstrument);
        $trend = $this->getTrend($lastPrices);
        $deviation = $this->getDeviation($lastPrices);

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
