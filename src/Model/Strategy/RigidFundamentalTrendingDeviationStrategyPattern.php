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

    private $indicatorService;
    private $priceService;
    private $extremumRange;
    private $fastAveragePeriod;
    private $slowAveragePeriod;

    public function __construct(PriceService $priceService, IndicatorService $indicatorService, array $params)
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

        $this->priceService = $priceService;
        $this->indicatorService = $indicatorService;
        $this->extremumRange = $params['extremumRange'];
        $this->fastAveragePeriod = $params['fastAveragePeriod'];
        $this->slowAveragePeriod = $params['slowAveragePeriod'];
        parent::__construct($params['rigidStopLoss'], $params['takeProfitMultiplier'], $params['instrument']);
    }

    protected function getDirection(string $currentDateTime = null, string $selectedInstrument = null) : int
    {
    //TODO get instruments from injected list
        $lastIndicators = $this->indicatorService->getLastIndicatorsByPeriod($instruments, 'P3M', $currentDateTime);
        $indicatorsMap = [];
        foreach (array_keys($lastIndicators) as $instrument) {
            $indicatorsMap[$instrument] = 0;
        }

        $points = ['bank' => 0, 'inflation' => 0, 'companies' => 0, 'trade' => 0, 'unemployment' => 0, 'sales' => 0];
        foreach ($lastIndicators as $block) {
             //@TODO loop and see which interest rate is highest and which inflation is highest
//back loop and set values in pointsMap for all keys if not filled yet for currency and type
//next loop will over indicatorsMap will assign points and select the best pair to play and now figure out how to find a pair
//can check for XXX_YYY and YYY_XXX and if one exists then use it and change fundamental value accordingly


            var_dump($lastIndicators);exit;
        }

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
