<?php
namespace TinyApp\Model\Strategy;

use TinyApp\Model\Strategy\StrategyInterface;
use TinyApp\Model\Strategy\Order;

class RandomStrategy implements StrategyInterface
{
    public function getOrderForPrice(array $prices) : Order
    {
        $instrument = 'EUR_USD';
        $takeProfitMultiplier = 3;
        $units = 100;

        if (strpos('JPY', $instrument) !== false) {
            $stopValue = 0.1000;
        } else {
            $stopValue = 0.0010;
        }

        if (rand(0,1)) {
            $price = $prices[$instrument]['ask'];
            $takeProfit = $price + ($takeProfitMultiplier * $stopValue);
            $stopLoss = $price - $stopValue;
        } else {
            $price = $prices[$instrument]['bid'];
            $units = - $units;
            $takeProfit = $price - ($takeProfitMultiplier * $stopValue);
            $stopLoss = $price + $stopValue;
        }

        return new Order($instrument, $units, $takeProfit, $stopLoss);
    }
}
