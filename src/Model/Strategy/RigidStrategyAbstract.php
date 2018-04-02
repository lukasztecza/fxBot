<?php
namespace TinyApp\Model\Strategy;

use TinyApp\Model\Strategy\StrategyAbstract;
use TinyApp\Model\Strategy\Order;

abstract class RigidStrategyAbstract extends StrategyAbstract
{
    protected $rigidStopLoss;
    protected $takeProfitMultiplier;
    protected $instrument;

    public function __construct(float $rigidStopLoss, float $takeProfitMultiplier, string $instrument)
    {
        $this->rigidStopLoss = $rigidStopLoss;
        $this->takeProfitMultiplier = $takeProfitMultiplier;
        $this->instrument = $instrument;
    }

    public function getOrder(array $prices, float $balance, string $currentDateTime = null) : ?Order
    {
        $selectedInstrument = $this->getInstrument();

        $rigidStopLoss = $this->getRigidStopLoss();
        if (strpos($selectedInstrument, 'JPY') !== false) {
            $rigidStopLoss *= 100;
        }

        $direction = $this->getDirection($currentDateTime, $selectedInstrument);
        if ($direction === 1) {
            $tradePrice = $prices[$selectedInstrument]['ask'];
            $takeProfit = (string)($tradePrice + ($this->getTakeProfitMultiplier() * $rigidStopLoss));
            $stopLoss = (string)($tradePrice - $rigidStopLoss);
        } elseif ($direction === -1) {
            $tradePrice = $prices[$selectedInstrument]['bid'];
            $takeProfit = (string)($tradePrice - ($this->getTakeProfitMultiplier() * $rigidStopLoss));
            $stopLoss = (string)($tradePrice + $rigidStopLoss);
        } else {
            return null;
        }

        $units = $this->calculateUnits($balance, $prices, $selectedInstrument, $stopLoss);
        $units *= $direction;

        return new Order($selectedInstrument, $units, $tradePrice, $takeProfit, $stopLoss);
    }

    protected function getRigidStopLoss() : float
    {
        return $this->rigidStopLoss;
    }

    protected function getTakeProfitMultiplier() : float
    {
        return $this->takeProfitMultiplier;
    }

    protected function getInstrument() : string
    {
        return $this->instrument;
    }

    abstract protected function getDirection(string $currentDateTime = null, string $selectedInstrument = null) : int;
}
