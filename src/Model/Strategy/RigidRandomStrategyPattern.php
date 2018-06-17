<?php declare(strict_types=1);
namespace FxBot\Model\Strategy;

use FxBot\Model\Strategy\RigidStrategyAbstract;
use FxBot\Model\Service\PriceService;
use FxBot\Model\Service\IndicatorService;

class RigidRandomStrategyPattern extends RigidStrategyAbstract
{
    public function __construct(array $priceInstruments, PriceService $priceService, IndicatorService $indicatorService, array $params)
    {
        parent::__construct($params['rigidStopLoss'], $params['takeProfitMultiplier'], $params['instrument']);
    }

    protected function getDirection(string $currentDateTime = null, string $selectedInstrument = null) : int
    {
        $direction = rand(0, 1);
        return $direction === 1 ? 1 : -1;
    }
}
