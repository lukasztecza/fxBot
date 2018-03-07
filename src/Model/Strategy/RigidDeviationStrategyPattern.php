<?php
namespace TinyApp\Model\Strategy;

use TinyApp\Model\Strategy\RigidStrategyAbstract;
use TinyApp\Model\Service\PriceService;
use TinyApp\Model\Strategy\DeviationTrait;

class RigidDeviationStrategyPattern extends RigidStrategyAbstract
{
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

        return $this->getDeviation($lastPrices);
    }
}
