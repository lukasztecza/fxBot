<?php declare(strict_types=1);
namespace FxBot\Model\Strategy;

use FxBot\Model\Strategy\RigidStrategyAbstract;
use FxBot\Model\Service\PriceService;
use FxBot\Model\Service\IndicatorService;

class RigidTrendingStrategy extends RigidStrategyAbstract
{
    private $priceService;
    private $extremumRange;
    private $lastPricesPeriod;

    public function __construct(array $instruments, PriceService $priceService, IndicatorService $indicatorService, $params)
    {
        if (
            !isset($params['extremumRange']) ||
            !isset($params['lastPricesPeriod']) ||
            !isset($params['rigidStopLoss']) ||
            !isset($params['takeProfitMultiplier']) ||
            !isset($params['instrument'])
        ) {
            throw new \Exception('Could not create strategy due to missing params');
        }

        $this->priceService = $priceService;
        $this->extremumRange = $params['extremumRange'];
        $this->lastPricesPeriod = $params['lastPricesPeriod'];

        parent::__construct($params['rigidStopLoss'], $params['takeProfitMultiplier'], $params['instrument']);
    }

    protected function getDirection(string $currentDateTime = null, string $selectedInstrument = null) : int
    {
        $lastPrices = $this->priceService->getLastPricesByPeriod($selectedInstrument, $this->lastPricesPeriod, $currentDateTime);
        return $this->getChannelDirection($lastPrices, $this->extremumRange);
    }
}
