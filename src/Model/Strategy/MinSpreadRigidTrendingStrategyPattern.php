<?php
namespace TinyApp\Model\Strategy;

use TinyApp\Model\Strategy\MinSpreadRigidStrategyAbstract;
use TinyApp\Model\Strategy\TrendingTrait;
use TinyApp\Model\Service\PriceService;

class MinSpreadRigidTrendingStrategyPattern extends MinSpreadRigidStrategyAbstract
{
    use TrendingTrait;

    private $priceService;

    public function __construct(PriceService $priceService, float $rigidStopLoss, float $takeProfitMultiplier)
    {
        $this->priceService = $priceService;
        parent::__construct($rigidStopLoss, $takeProfitMultiplier);
    }

    protected function getDirection(string $currentDateTime = null, string $selectedInstrument = null) : int
    {
        $lastPrices = $this->priceService->getLastPricesByPeriod($selectedInstrument, 'P7D', $currentDateTime);

        return $this->getTrend($lastPrices);
    }
}
