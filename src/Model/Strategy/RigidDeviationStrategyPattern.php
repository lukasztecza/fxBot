<?php
namespace TinyApp\Model\Strategy;

use TinyApp\Model\Strategy\RigidStrategyAbstract;
use TinyApp\Model\Strategy\DeviationTrait;
use TinyApp\Model\Service\PriceService;

class RigidDeviationStrategyPattern extends RigidStrategyAbstract
{
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

        return $this->getDeviation($lastPrices);
    }
}
