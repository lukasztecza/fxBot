<?php
namespace TinyApp\Model\Strategy;

use TinyApp\Model\Strategy\StrategyInterface;
use TinyApp\Model\Strategy\Order;

class RandomStrategy extends StrategyAbstract
{
    private const TAKE_PROFIT_MULTIPLIER = 3;
    private const RIGID_STOP_LOSS_PIPS = 0.0010;

    public function getOrder(array $prices, float $balance) : Order
    {
        // select the lowest spread pair
        $minSpread = 1000;
        foreach ($prices as $pair => $price) {
            $spread = $price['ask'] - $price['bid'];
            if ($spread < $minSpread) {
                $minSpread = $spread;
                $selectedPair = $pair;
            }
        }

        $rigidStopLoss = self::RIGID_STOP_LOSS_PIPS;
        if (strpos($selectedPair, 'JPY') !== false) {
            $rigidStopLoss *= 100;
        }

        $direction = 1 || rand(0,1);
        if ($direction) {
            $takeProfit = (string)($prices[$selectedPair]['ask'] + (self::TAKE_PROFIT_MULTIPLIER * $rigidStopLoss));
            $stopLoss = (string)($prices[$selectedPair]['ask'] - $rigidStopLoss);
        } else {
            $takeProfit = (string)($prices[$selectedPair]['bid'] - (self::TAKE_PROFIT_MULTIPLIER * $rigidStopLoss));
            $stopLoss = (string)($prices[$selectedPair]['bid'] + $rigidStopLoss);
        }

        $units = $this->calculateUnits($balance, $prices, $selectedPair, $stopLoss);
        $units = $direction ? $units : -$units;

        return new Order($selectedPair, $units, $takeProfit, $stopLoss);
    }
}
