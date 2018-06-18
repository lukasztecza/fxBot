<?php declare(strict_types=1);
namespace FxBot\Model\Strategy;

use FxBot\Model\Strategy\RigidStrategyAbstract;
use FxBot\Model\Service\PriceService;
use FxBot\Model\Service\IndicatorService;

class RigidAverageStrategy extends RigidStrategyAbstract
{
    protected $instrument;
    private $priceService;
    private $lastPricesPeriod;
    private $followTrend;
    private $longFastAverage;
    private $longSlowAverage;
    private $signalFastAverage;
    private $signalSlowAverage;
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
        $this->longFastAverage = $params['longFastAverage'];
        $this->longSlowAverage = $params['longSlowAverage'];
        $this->signalFastAverage = $params['signalFastAverage'];
        $this->signalSlowAverage = $params['signalSlowAverage'];
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
            'longFastAverage',
            'longSlowAverage',
            'signalFastAverage',
            'signalSlowAverage',
            'lossLockerFactor',
            'instrument'
        ];
    }

    protected function getDirection(string $currentDateTime = null) : int
    {
        $lastPrices = $this->priceService->getLastPricesByPeriod($this->getInstrument(), $this->lastPricesPeriod, $currentDateTime);
        $averages = $this->getAveragesByPeriods($lastPrices, [
            'current' => 1,
            'signalFast' => $this->signalFastAverage,
            'signalSlow' => $this->signalSlowAverage,
            'longFast' => $this->longFastAverage,
            'longSlow' => $this->longSlowAverage
        ]);
        switch (true) {
            case (
                !isset($averages['current']) ||
                !isset($averages['signalFast']) ||
                !isset($averages['signalSlow']) ||
                !isset($averages['longFast']) ||
                !isset($averages['longSlow'])
            ):
                return 0;
            case (
                $averages['longFast'] > $averages['longSlow'] &&
                $averages['current'] < $averages['signalFast'] &&
                $averages['current'] > $averages['signalSlow']
            ):
                return $this->followTrend ? 1 : -1;
            case (
                $averages['longFast'] < $averages['longSlow'] &&
                $averages['current'] > $averages['signalFast'] &&
                $averages['current'] < $averages['signalSlow']
            ):
                return $this->followTrend ? -1 : 1;
            default:
                return 0;
        }
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
