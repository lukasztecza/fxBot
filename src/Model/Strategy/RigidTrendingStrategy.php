<?php declare(strict_types=1);
namespace FxBot\Model\Strategy;

use FxBot\Model\Strategy\RigidStrategyAbstract;
use FxBot\Model\Service\PriceService;
use FxBot\Model\Service\IndicatorService;

class RigidTrendingStrategy extends RigidStrategyAbstract
{
    protected $instrument;
    protected $priceService;
    protected $lastPricesPeriod;
    protected $followTrend;
    protected $extremumRange;
    protected $lossLockerFactor;

    public function __construct(array $priceInstruments, PriceService $priceService, IndicatorService $indicatorService, $params)
    {
        foreach ($this->getRequiredParams() as $requiredParam) {
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

    protected function getRequiredParams() : array
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

    protected function getDirection(string $currentDateTime = null) : int
    {
        $lastPrices = $this->priceService->getLastPricesByPeriod($this->getInstrument(), $this->lastPricesPeriod, $currentDateTime);
        $channelDirection = $this->getChannelDirection($lastPrices, $this->extremumRange);

        return $this->followTrend ? $channelDirection : -$channelDirection;
    }

    protected function getPriceModification(float $openPrice, float $currentStopLoss, float $currentTakeProfit, array $currentPrices) : ?float
    {
        if ($currentTakeProfit > $currentStopLoss && $currentPrices['bid'] > $openPrice + 0.0015) {
            return $openPrice;
        } elseif ($currentTakeProfit < $currentStopLoss && $currentPrices['ask'] < $openPrice - 0.0015) {
            return $openPrice;
        }

        return null;
    }

    public function getLossLockerFactor()
    {
        return $this->lossLockerFactor;
    }
}
