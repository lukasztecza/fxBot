<?php declare(strict_types=1);
namespace FxBot\Model\Strategy;

use FxBot\Model\Strategy\RigidStrategyAbstract;
use FxBot\Model\Service\PriceService;
use FxBot\Model\Service\IndicatorService;

class RigidAverageTrendingStrategy extends RigidStrategyAbstract
{
    private $priceService;
    private $extremumRange;
    private $longSlowAverage;
    private $followTrend;
    private $lastPricesPeriod;

    public function __construct(array $instruments, PriceService $priceService, IndicatorService $indicatorService, $params)
    {
        if (
            !isset($params['extremumRange']) ||
            !isset($params['longSlowAverage']) ||
            !isset($params['followTrend']) ||
            !isset($params['lastPricesPeriod']) ||
            !isset($params['rigidStopLoss']) ||
            !isset($params['takeProfitMultiplier']) ||
            !isset($params['instrument'])
        ) {
            throw new \Exception('Could not create strategy due to missing params');
        }

        $this->priceService = $priceService;
        $this->extremumRange = $params['extremumRange'];
        $this->longSlowAverage = $params['longSlowAverage'];
        $this->followTrend = $params['followTrend'];
        $this->lastPricesPeriod = $params['lastPricesPeriod'];

        parent::__construct($params['rigidStopLoss'], $params['takeProfitMultiplier'], $params['instrument']);
    }

    protected function getDirection(string $currentDateTime = null, string $selectedInstrument = null) : int
    {
        $lastPrices = $this->priceService->getLastPricesByPeriod($selectedInstrument, $this->lastPricesPeriod, $currentDateTime);
        $average = $this->getAveragesByPeriods($lastPrices, ['current' => 1, 'long' => $this->longSlowAverage]);
        $channelDirection = $this->getChannelDirection($lastPrices, $this->extremumRange);

        switch (true) {
            case !isset($average['long']) || !isset($average['current']):
                return 0;
            case $this->followTrend === 0 &&  $average['current'] > $average['long'] && $channelDirection === -1:
                return -1;
            case $this->followTrend === 0 && $average['current'] < $average['long'] && $channelDirection === 1:
                return 1;
            case $this->followTrend === 1 &&  $average['current'] > $average['long'] && $channelDirection === 1:
                return 1;
            case $this->followTrend === 1 && $average['current'] < $average['long'] && $channelDirection === -1:
                return -1;
            default:
                return 0;
        }
    }
}
