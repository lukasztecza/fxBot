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

    public function __construct(PriceService $priceService, float $rigidStopLoss, float $takeProfitMultiplier, string $instrument)
    {
        $this->priceService = $priceService;
        parent::__construct($rigidStopLoss, $takeProfitMultiplier, $instrument);
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
