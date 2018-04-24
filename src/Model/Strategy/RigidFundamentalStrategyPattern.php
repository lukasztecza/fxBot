<?php
namespace TinyApp\Model\Strategy;

use TinyApp\Model\Strategy\RigidStrategyAbstract;
use TinyApp\Model\Service\PriceService;
use TinyApp\Model\Service\IndicatorService;
use TinyApp\Model\Strategy\IndicatorTrait;

class RigidFundamentalStrategyPattern extends RigidStrategyAbstract
{
    use IndicatorTrait;

    private $instruments;
    private $priceInstruments;
    private $priceService;
    private $indicatorService;
    private $bankFactor;
    private $inflationFactor;
    private $tradeFactor;
    private $companiesFactor;
    private $salesFactor;
    private $unemploymentFactor;
    private $bankRelativeFactor;

    public function __construct(array $priceInstruments, PriceService $priceService, IndicatorService $indicatorService, array $params)
    {
        if (
            !isset($params['rigidStopLoss']) ||
            !isset($params['takeProfitMultiplier']) ||
            !isset($params['instrument']) ||
            !isset($params['bankFactor']) ||
            !isset($params['inflationFactor']) ||
            !isset($params['tradeFactor']) ||
            !isset($params['companiesFactor']) ||
            !isset($params['salesFactor']) ||
            !isset($params['unemploymentFactor']) ||
            !isset($params['bankRelativeFactor'])
        ) {
            throw new \Exception('Got wrong params ' . var_export($params, true));
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
        $this->priceService = $priceService;
        $this->indicatorService = $indicatorService;
        $this->bankFactor = $params['bankFactor'];
        $this->inflationFactor = $params['inflationFactor'];
        $this->tradeFactor = $params['tradeFactor'];
        $this->companiesFactor = $params['companiesFactor'];
        $this->salesFactor = $params['salesFactor'];
        $this->unemploymentFactor = $params['unemploymentFactor'];
        $this->bankRelativeFactor = $params['bankRelativeFactor'];

        parent::__construct($params['rigidStopLoss'], $params['takeProfitMultiplier'], $params['instrument']);
    }

    protected function getDirection(string $currentDateTime = null, string $selectedInstrument = null) : int
    {
        $lastIndicators = $this->indicatorService->getLastIndicatorsByPeriod($this->instruments, 'P12M', $currentDateTime);
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
}