<?php
namespace TinyApp\Model\Strategy;

use TinyApp\Model\Strategy\RigidStrategyAbstract;
use TinyApp\Model\Strategy\TrendingTrait;
use TinyApp\Model\Strategy\LongAverageTrait;
use TinyApp\Model\Strategy\DeviationTrait;
use TinyApp\Model\Strategy\IndicatorTrait;
use TinyApp\Model\Service\PriceService;
use TinyApp\Model\Service\IndicatorService;

class RigidFundamentalTrendingLongAverageDeviationStrategyPattern extends RigidStrategyAbstract
{
    use TrendingTrait;
    use LongAverageTrait;
    use DeviationTrait;
    use IndicatorTrait;

    private $priceService;
    private $extremumRange;
    private $signalFast;
    private $signalSlow;
    private $fastAverage;
    private $slowAverage;
    private $bankFactor;
    private $inflationFactor;
    private $tradeFactor;
    private $companiesFactor;
    private $salesFactor;
    private $unemploymentFactor;
    private $bankRelativeFactor;
    private $instruments;

    public function __construct(array $priceInstruments, PriceService $priceService, IndicatorService $indicatorService, array $params)
    {
        if (
            empty($params['rigidStopLoss']) ||
            empty($params['takeProfitMultiplier']) ||
            empty($params['instrument']) ||
            empty($params['extremumRange']) ||
            empty($params['signalFast']) ||
            empty($params['signalSlow']) ||
            empty($params['fastAverage']) ||
            empty($params['slowAverage']) ||
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
        $this->extremumRange = $params['extremumRange'];
        $this->bankFactor = $params['bankFactor'];
        $this->inflationFactor = $params['inflationFactor'];
        $this->tradeFactor = $params['tradeFactor'];
        $this->companiesFactor = $params['companiesFactor'];
        $this->salesFactor = $params['salesFactor'];
        $this->unemploymentFactor = $params['unemploymentFactor'];
        $this->bankRelativeFactor = $params['bankRelativeFactor'];
        $this->signalFast = $params['signalFast'];
        $this->signalSlow = $params['signalSlow'];
        $this->fastAverage = $params['fastAverage'];
        $this->slowAverage = $params['slowAverage'];
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

        $lastPrices = $this->priceService->getLastPricesByPeriod($selectedInstrument, 'P7D', $currentDateTime);
        $longAverageDirection = $this->getLongAverageDirection($lastPrices, $this->fastAverage, $this->slowAverage);
        $trendingDirection = $this->getTrend($lastPrices, $this->extremumRange);
            $deviationDirection = $this->getDeviationDirection($lastPrices, $this->signalFast, $this->signalSlow);

        switch (true) {
            case $longAverageDirection === 1 && $trendingDirection === 1 && $deviationDirection === 1 && $fundamental === 1:
                return 1;
            case $longAverageDirection === -1 && $trendingDirection === -1 && $deviationDirection === -1 && $fundamental === -1:
                return -1;
            default:
                return 0;
        }
    }
}
