<?php declare(strict_types=1);
namespace FxBot\Model\Strategy;

use FxBot\Model\Strategy\RigidStrategyAbstract;
use FxBot\Model\Service\PriceService;
use FxBot\Model\Service\IndicatorService;

class RigidFundamentalStrategy extends RigidStrategyAbstract
{
    protected $instrument;
    protected $instruments;
    protected $priceInstruments;
    protected $indicatorService;
    protected $lastIndicatorsPeriod;
    protected $bankFactor;
    protected $inflationFactor;
    protected $tradeFactor;
    protected $companiesFactor;
    protected $salesFactor;
    protected $unemploymentFactor;
    protected $bankRelativeFactor;
    protected $lossLockerFactor;

    public function __construct(array $priceInstruments, PriceService $priceService, IndicatorService $indicatorService, array $params)
    {
        foreach ($this->getRequiredParams() as $requiredParam) {
            if (!array_key_exists($requiredParam, $params)) {
                throw new \Exception('Could not create strategy due to missing params');
            }
        }

        $this->instruments = [];
        foreach ($priceInstruments as $priceInstrument) {
            $instruments = explode('_', $priceInstrument);
            foreach ($instruments as $instrument) {
                $this->instruments[$instrument] = true;
            }
        }
        $this->instruments = array_keys($this->instruments);
        $this->priceInstruments = $priceInstruments;
        $this->indicatorService = $indicatorService;
        $this->lastIndicatorsPeriod = $params['lastIndicatorsPeriod'];
        $this->bankFactor = $params['bankFactor'];
        $this->inflationFactor = $params['inflationFactor'];
        $this->tradeFactor = $params['tradeFactor'];
        $this->companiesFactor = $params['companiesFactor'];
        $this->salesFactor = $params['salesFactor'];
        $this->unemploymentFactor = $params['unemploymentFactor'];
        $this->bankRelativeFactor = $params['bankRelativeFactor'];
        $this->lossLockerFactor = $params['lossLockerFactor'];

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
            'lastIndicatorsPeriod',
            'bankFactor',
            'inflationFactor',
            'tradeFactor',
            'companiesFactor',
            'salesFactor',
            'unemploymentFactor',
            'bankRelativeFactor',
            'lossLockerFactor'
        ];
    }

    protected function getDirection(string $currentDateTime = null) : int
    {
        $lastIndicators = $this->indicatorService->getLastIndicatorsByPeriod($this->instruments, $this->lastIndicatorsPeriod, $currentDateTime);
        $scores = $this->getInstrumentScores(
            $lastIndicators,
            $this->instruments,
            $this->bankFactor,
            $this->inflationFactor,
            $this->tradeFactor,
            $this->companiesFactor,
            $this->salesFactor,
            $this->unemploymentFactor,
            $this->bankRelativeFactor
        );
        reset($scores);
        $worst = key($scores);
        end($scores);
        $best = key($scores);
        if (in_array($worst . '_' . $best, $this->priceInstruments)) {
            $selectedInstrument = $worst . '_' . $best;
            $fundamental = -1;
        } elseif (in_array($best . '_' . $worst, $this->priceInstruments)) {
            $selectedInstrument = $best . '_' . $worst;
            $fundamental = 1;
        } else {
            throw new \Exception('Failed to select instrument');
        }
        $this->instrument = $selectedInstrument;

        return $fundamental;
    }

    protected function getInstrumentScores(
        array $lastIndicators,
        array $instruments,
        float $bankFactor,
        float $inflationFactor,
        float $tradeFactor,
        float $companiesFactor,
        float $salesFactor,
        float $unemploymentFactor,
        float $bankRelativeFactor
    ) : array {
        $typeValues = [];
        $instrumentScores = [];
        foreach ($lastIndicators as $index => $values) {
            if (
                !empty($values['type']) &&
                in_array($values['instrument'], $instruments) &&
                !isset($typeValues[$values['type']][$values['instrument']]['actual'][1])
            ) {
                $instrumentScores[$values['instrument']] = 0;
                $typeValues[$values['type']][$values['instrument']]['actual'][] = $values['actual'];
                $typeValues[$values['type']][$values['instrument']]['forecast'][] = $values['forecast'];
            }
        }

        $bankRates = [];
        foreach ($typeValues as $type => $instrumentValues) {
            foreach ($instrumentValues as $instrument => $values) {
                if (!isset($values['actual'][0]) || !isset($values['actual'][1])) {
                    continue;
                }

                if (
                    ($type === 'unemployment' && $values['actual'][0] < $values['actual'][1]) ||
                    ($type !== 'unemployment' && $values['actual'][0] > $values['actual'][1])
                ) {
                    $factorName = $type . 'Factor';
                    if (isset($instrumentScores[$instrument])) {
                        $instrumentScores[$instrument] = $instrumentScores[$instrument] + $$factorName;
                    } else {
                        $instrumentScores[$instrument] = $$factorName;
                    }
                }
            }
        }

        asort($bankRates);
        $counter = 0;
        foreach ($bankRates as $instrument => $value) {
            $instrumentScores[$instrument] = isset($instrumentScores[$instrument]) ? $instrumentScores[$instrument] + $counter : $counter;
            $counter = $counter + $bankRelativeFactor;
        }
        asort($instrumentScores);

        return $instrumentScores;
    }

    public function getStrategyParams() : array
    {
        $return['className'] = get_class($this);
        foreach ($this->getRequiredParams() as $requiredParam) {
            $return['params'][$requiredParam] = $this->$requiredParam;
        }
        $return['params']['instrument'] = 'VARIED';

        return $return;
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
}
