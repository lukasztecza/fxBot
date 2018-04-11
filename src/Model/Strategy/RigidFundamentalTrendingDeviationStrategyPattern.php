<?php
namespace TinyApp\Model\Strategy;

use TinyApp\Model\Strategy\RigidStrategyAbstract;
use TinyApp\Model\Service\PriceService;
use TinyApp\Model\Service\IndicatorService;
use TinyApp\Model\Strategy\TrendingTrait;
use TinyApp\Model\Strategy\DeviationTrait;

class RigidFundamentalTrendingDeviationStrategyPattern extends RigidStrategyAbstract
{
    use TrendingTrait;
    use DeviationTrait;

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
        $instrumentsValues = [];
        $index = count($lastIndicators);
        foreach ($lastIndicators as $index => $values) {
            if (
                !empty($values['type']) &&
                in_array($values['instrument'], $this->instruments) &&
                !isset($instrumentsValues[$values['instrument']][$values['type']][1])
            ) {
                $instrumentsValues[$values['instrument']][$values['type']][] = $values['actual'];
            }
        }

        $maxBank = $lowestUnemployment = $bestTradeChange = [];
        foreach ($instrumentsValues as $instrument => $values) {
            if (!isset($maxBank['instrument']) || $maxBank['value'] < $values['bank'][0]) {
                $maxBank['value'] = $values['bank'][0];
                $maxBank['instrument'] = $instrument;
            }

            if (!isset($lowestUnemployment['instrument']) || $lowestUnemployment['value'] > $values['unemployment'][0]) {
                $lowestUnemployment['value'] = $values['unemployment'][0];
                $lowestUnemployment['instrument'] = $instrument;
            }



            // @TODO relative value should be last
            if (!$values['trade'][1]) {
                continue;
            }
            $change = $values['trade'][1] - $values['trade'][0] / $values['trade'][1];
            if (!isset($bestTradeChange['instrument']) || $bestTradeChange['value'] < $change) {
                $bestTradeChange['value'] = $change;
                $bestTradeChange['instrument'] = $instrument;
            }
        }

//@TODO loop and see which interest rate is highest and which inflation is highest
//back loop and set values in pointsMap for all keys if not filled yet for currency and type
//next loop will over indicatorsMap will assign points and select the best pair to play and now figure out how to find a pair
//can check for XXX_YYY and YYY_XXX and if one exists then use it and change fundamental value accordingly
        var_dump('TODO rigidfundamental strategy');exit;

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
