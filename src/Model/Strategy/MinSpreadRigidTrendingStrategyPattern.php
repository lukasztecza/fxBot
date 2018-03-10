<?php
namespace TinyApp\Model\Strategy;

use TinyApp\Model\Strategy\MinSpreadRigidStrategyAbstract;
use TinyApp\Model\Strategy\TrendingTrait;
use TinyApp\Model\Service\PriceService;

class MinSpreadRigidTrendingStrategyPattern extends MinSpreadRigidStrategyAbstract
{
    use TrendingTrait;

    private $priceService;

    public function __construct(PriceService $priceService, array $params)
    {
        if (empty($params['rigidStopLoss']) || empty($params['takeProfitMultiplier'])) {
            throw new \Exception('Got wrong params ' . var_export($params, true));
        }

        $this->priceService = $priceService;
        parent::__construct($params['rigidStopLoss'], $params['takeProfitMultiplier']);
    }

    protected function getDirection(string $currentDateTime = null, string $selectedInstrument = null) : int
    {
        $lastPrices = $this->priceService->getLastPricesByPeriod($selectedInstrument, 'P7D', $currentDateTime);

        return $this->getTrend($lastPrices);
    }
}
