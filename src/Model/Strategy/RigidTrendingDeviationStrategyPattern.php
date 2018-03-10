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

    public function __construct(PriceService $priceService, array $params)
    {
        if (empty($params['rigidStopLoss']) || empty($params['takeProfitMultiplier']) || empty($params['instrument'])) {
            throw new \Exception('Got wrong params ' . var_export($params, true));
        }

        $this->priceService = $priceService;
        parent::__construct($params['rigidStopLoss'], $params['takeProfitMultiplier'], $params['instrument']);
    }

    protected function getDirection(string $currentDateTime = null, string $selectedInstrument = null) : int
    {
        $lastPrices = $this->priceService->getLastPricesByPeriod($selectedInstrument, 'P7D', $currentDateTime);

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
