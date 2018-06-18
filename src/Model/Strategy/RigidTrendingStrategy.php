<?php declare(strict_types=1);
namespace FxBot\Model\Strategy;

use FxBot\Model\Strategy\RigidStrategyAbstract;
use FxBot\Model\Service\PriceService;
use FxBot\Model\Service\IndicatorService;

class RigidTrendingStrategy extends RigidStrategyAbstract
{
    protected $instrument;
    private $priceService;
    private $lastPricesPeriod;
    private $followTrend;
    private $extremumRange;
    private $lossLockerFactor;

    public function __construct(array $priceInstruments, PriceService $priceService, IndicatorService $indicatorService, $params)
    {
        foreach ($this->requiredParams() as $requiredParam) {
            if (!array_key_exists($requiredParam, $params)) {
                throw new \Exception('Could not create strategy due to missing params');
            }
        }

        $this->priceService = $priceService;
        $this->lastPricesPeriod = $params['lastPricesPeriod'];
        $this->followTrend = $params['followTrend'];
        $this->extremumRange = $params['extremumRange'];
        $this->lossLockerFactor = $params['lossLockerFactor'];
        $this->instrument = $params['instrument'];

        parent::__construct(
            (string) $params['homeCurrency'],
            (float) $params['singleTransactionRisk'],
            (float) $params['rigidStopLoss'],
            (float) $params['takeProfitMultiplier']
        );
    }

    private function requiredParams() : array
    {
        return [
            'homeCurrency',
            'singleTransactionRisk',
            'rigidStopLoss',
            'takeProfitMultiplier',
            'lastPricesPeriod',
            'followTrend',
            'extremumRange',
            'lossLockerFactor',
            'instrument'
        ];
    }

    public function getStrategyParams() : array
    {
        $return['className'] = get_class($this);
        foreach ($this->requiredParams() as $requiredParam) {
            $return['params'][$requiredParam] = $this->$requiredParam;
        }

        return $return;
    }

    public function getLossLockerFactor() {
        return $this->lossLockerFactor;
    }

    protected function getDirection(string $currentDateTime = null) : int
    {
        $lastPrices = $this->priceService->getLastPricesByPeriod($this->getInstrument(), $this->lastPricesPeriod, $currentDateTime);
        $channelDirection = $this->getChannelDirection($lastPrices, $this->extremumRange);

        return $this->followTrend ? $channelDirection : -$channelDirection;
    }
}
