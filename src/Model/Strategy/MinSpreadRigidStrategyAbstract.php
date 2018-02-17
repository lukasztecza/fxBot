<?php
namespace TinyApp\Model\Strategy;

use TinyApp\Model\Strategy\StrategyInterface;
use TinyApp\Model\Strategy\StrategyAbstract;
use TinyApp\Model\Strategy\Order;

abstract class MinSpreadRigidStrategyAbstract extends StrategyAbstract
{
    private $rigidStopLoss;
    private $takeProfitMultiplier;

    public function __construct(float $rigidStopLoss, float $takeProfitMultiplier)
    {
        $this->rigidStopLoss = $rigidStopLoss;
        $this->takeProfitMultiplier = $takeProfitMultiplier;
    }

    public function getOrder(array $prices, float $balance, string $currentDateTime = null) : ?Order
    {
        // select the lowest spread instrument
        $minSpread = 100;
        foreach ($prices as $instrument => $price) {
            $spread = $price['ask'] - $price['bid'];
            if ($spread < $minSpread) {
                $minSpread = $spread;
                $selectedInstrument = $instrument;
            }
        }

        // set rigid stop loss
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

    abstract protected function getDirection(string $currentDateTime = null, string $selectedInstrument = null) : int;
}
