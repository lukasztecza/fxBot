<?php declare(strict_types=1);
namespace FxBot\Model\Strategy;

use FxBot\Model\Strategy\RigidStrategyAbstract;
use FxBot\Model\Service\PriceService;
use FxBot\Model\Service\IndicatorService;

class RigidRandomStrategy extends RigidStrategyAbstract
{
    protected $instrument;
    private $lossLockerFactor;

    public function __construct(array $priceInstruments, PriceService $priceService, IndicatorService $indicatorService, array $params)
    {
        foreach ($this->requiredParams() as $requiredParam) {
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

    private function requiredParams() : array
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
}
