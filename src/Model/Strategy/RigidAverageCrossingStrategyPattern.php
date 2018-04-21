<?php
namespace TinyApp\Model\Strategy;

use TinyApp\Model\Strategy\RigidStrategyAbstract;
use TinyApp\Model\Strategy\AverageCrossingTrait;
use TinyApp\Model\Service\PriceService;

class RigidAverageCrossingStrategyPattern extends RigidStrategyAbstract
{
    use AverageCrossingTrait;

    private $priceService;
    private $signalAverage;
    private $fastAverage;
    private $slowAverage;

    public function __construct(PriceService $priceService, array $params)
    {
        if (
            empty($params['rigidStopLoss']) ||
            empty($params['takeProfitMultiplier']) ||
            empty($params['instrument']) ||
            empty($params['fast']) ||
            empty($params['slow'])
        ) {
            throw new \Exception('Got wrong params ' . var_export($params, true));
        }

        $this->priceService = $priceService;
        $this->fast = $params['fast'];
        $this->slow = $params['slow'];
        parent::__construct($params['rigidStopLoss'], $params['takeProfitMultiplier'], $params['instrument']);
    }

    protected function getDirection(string $currentDateTime = null, string $selectedInstrument = null) : int
    {
        $lastPrices = $this->priceService->getLastPricesByPeriod($selectedInstrument, 'P7D', $currentDateTime);

        return $this->getAverageCrossingDirection($lastPrices, $this->fast, $this->slow);
    }
}
