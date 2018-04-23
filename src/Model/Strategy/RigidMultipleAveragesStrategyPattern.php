<?php
namespace TinyApp\Model\Strategy;

use TinyApp\Model\Strategy\RigidStrategyAbstract;
use TinyApp\Model\Strategy\MultipleAveragesTrait;
use TinyApp\Model\Service\PriceService;

class RigidMultipleAveragesStrategyPattern extends RigidStrategyAbstract
{
    use MultipleAveragesTrait;

    private $priceService;
    private $signalFast;
    private $signalSlow;
    private $fastAverage;
    private $slowAverage;

    public function __construct(PriceService $priceService, array $params)
    {
        if (
            empty($params['rigidStopLoss']) ||
            empty($params['takeProfitMultiplier']) ||
            empty($params['instrument']) ||
            empty($params['signalFast']) ||
            empty($params['signalSlow']) ||
            empty($params['fastAverage']) ||
            empty($params['slowAverage'])
        ) {
            throw new \Exception('Got wrong params ' . var_export($params, true));
        }

        $this->priceService = $priceService;
        $this->signalFast = $params['signalFast'];
        $this->signalSlow = $params['signalSlow'];
        $this->fastAverage = $params['fastAverage'];
        $this->slowAverage = $params['slowAverage'];
        parent::__construct($params['rigidStopLoss'], $params['takeProfitMultiplier'], $params['instrument']);
    }

    protected function getDirection(string $currentDateTime = null, string $selectedInstrument = null) : int
    {
        $lastPrices = $this->priceService->getLastPricesByPeriod($selectedInstrument, 'P7D', $currentDateTime);

        return $this->getAverageDirection($lastPrices, $this->signalFast, $this->slowAverage, $this->fastAverage, $this->slowAverage);
    }
}
