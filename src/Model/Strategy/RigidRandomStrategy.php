<?php declare(strict_types=1);
namespace FxBot\Model\Strategy;

use FxBot\Model\Strategy\RigidStrategyAbstract;
use FxBot\Model\Service\PriceService;
use FxBot\Model\Service\IndicatorService;

class RigidRandomStrategy extends RigidStrategyAbstract
{
    protected $instrument;
    protected $lossLockerFactor;

    public function __construct(array $priceInstruments, PriceService $priceService, IndicatorService $indicatorService, array $params)
    {
        foreach ($this->getRequiredParams() as $requiredParam) {
            if (!array_key_exists($requiredParam, $params)) {
                throw new \Exception('Could not create strategy due to missing params');
            }
        }

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
            'lossLockerFactor',
            'instrument'
        ];
    }

    protected function getDirection(string $currentDateTime = null) : int
    {
        $direction = rand(0, 1);
        return $direction === 1 ? 1 : -1;
    }

    protected function getPriceModification(float $openPrice, float $currentStopLoss, float $currentTakeProfit, array $currentPrices) : ?float
    {
        if (round($openPrice, 4) === round($currentStopLoss, 4)) {
            return null;
        }

        $difference = $this->lossLockerFactor * abs($currentTakeProfit - $openPrice) / $this->takeProfitMultiplier;
        if ($currentTakeProfit > $currentStopLoss && $currentPrices['bid'] > $openPrice + $difference) {
            return $openPrice;
        } elseif ($currentTakeProfit < $currentStopLoss && $currentPrices['ask'] < $openPrice - $difference) {
            return $openPrice;
        }

        return null;
    }
}
