<?php
namespace TinyApp\Model\Strategy;

use TinyApp\Model\Strategy\RigidStrategyAbstract;
use TinyApp\Model\Service\PriceService;
use TinyApp\Model\Service\IndicatorService;
use TinyApp\Model\Strategy\TrendingTrait;
use TinyApp\Model\Strategy\DeviationTrait;
use TinyApp\Model\Strategy\IndicatorTrait;

class RigidFundamentalTrendingDeviationStrategyPattern extends RigidStrategyAbstract
{
    use TrendingTrait;
    use DeviationTrait;
    use IndicatorTrait;

    private $instruments;
    private $priceInstruments;
    private $priceService;
    private $indicatorService;
    private $extremumRange;
    private $fastAveragePeriod;
    private $slowAveragePeriod;

    public function __construct(array $priceInstruments, PriceService $priceService, IndicatorService $indicatorService, array $params)
    {
        if (
            empty($params['rigidStopLoss']) ||
            empty($params['takeProfitMultiplier']) ||
            empty($params['instrument']) ||
            empty($params['extremumRange']) ||
            empty($params['fastAveragePeriod']) ||
            empty($params['slowAveragePeriod'])
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
        $this->fastAveragePeriod = $params['fastAveragePeriod'];
        $this->slowAveragePeriod = $params['slowAveragePeriod'];
        parent::__construct($params['rigidStopLoss'], $params['takeProfitMultiplier'], $params['instrument']);
    }

    protected function getDirection(string $currentDateTime = null, string $selectedInstrument = null) : int
    {
        $lastIndicators = $this->indicatorService->getLastIndicatorsByPeriod($this->instruments, 'P6M', $currentDateTime);
        $scores = $this->getInstrumentScores($lastIndicators, $this->instruments, ['unemployment' => ['relative' => 0, 'absolute' => 1, 'expectations' => 0]]);

        $worst = current(array_keys($scores, min($scores)));
        $best = current(array_keys($scores, max($scores)));
        if (in_array($worst . '_' . $best, $this->priceInstruments)) {
            $selectedInstrument = $worst . '_' . $best;
        } elseif (in_array($best . '_' . $worst, $this->priceInstruments)) {
            $selectedInstrument = $best . '_' . $worst;
        } else {
            //@TODO
            throw new \Exception('Could not select instrument');
        }

//        var_dump($selectedInstrument);exit;
//@TODO shift mapping outside and potentailly considered indicators too (maybe config)
        $lastPrices = $this->priceService->getLastPricesByPeriod($selectedInstrument, 'P7D', $currentDateTime);
        $trend = $this->getTrend($lastPrices, $this->extremumRange);
        $deviation = $this->getDeviation($lastPrices, $this->fastAveragePeriod, $this->slowAveragePeriod);

        switch (true) {
            case $trend === 1 && $deviation === 1:
                return 1;
            case $trend === -1 && $deviation === -1:
                return -1;
            default:
                return 0;
        }
    }

}
