<?php
namespace TinyApp\Model\Strategy;

use TinyApp\Model\Strategy\StrategyInterface;
use TinyApp\Model\Strategy\StrategyAbstract;
use TinyApp\Model\Strategy\Order;

abstract class MinSpreadRigidOneMultiOneStrategyAbstract extends StrategyAbstract
{
    protected const TAKE_PROFIT_MULTIPLIER = 1;
    protected const RIGID_STOP_LOSS_PIPS = 0.0010;

    public function getOrder(array $prices, float $balance, string $currentDate = null) : Order
    {
        // select the lowest spread instrument
        $minSpread = 1000;
        foreach ($prices as $instrument => $price) {
            $spread = $price['ask'] - $price['bid'];
            if ($spread < $minSpread) {
                $minSpread = $spread;
                $selectedInstrument = $instrument;
            }
        }

        // set rigid sl
        $rigidStopLoss = static::RIGID_STOP_LOSS_PIPS;
        if (strpos($selectedInstrument, 'JPY') !== false) {
            $rigidStopLoss *= 100;
        }

        $direction = $this->getDirection($currentDate);
        if ($direction) {
            $tradePrice = $prices[$selectedInstrument]['ask'];
            $takeProfit = (string)($tradePrice + (static::TAKE_PROFIT_MULTIPLIER * $rigidStopLoss));
            $stopLoss = (string)($tradePrice - $rigidStopLoss);
        } else {
            $tradePrice = $prices[$selectedInstrument]['bid'];
            $takeProfit = (string)($tradePrice - (static::TAKE_PROFIT_MULTIPLIER * $rigidStopLoss));
            $stopLoss = (string)($tradePrice + $rigidStopLoss);
        }

        $units = $this->calculateUnits($balance, $prices, $selectedInstrument, $stopLoss);
        $units = $direction ? $units : -$units;

        return new Order($selectedInstrument, $units, $tradePrice, $takeProfit, $stopLoss);
    }

    abstract protected function getDirection(string $currentDate = null) : int;
}
