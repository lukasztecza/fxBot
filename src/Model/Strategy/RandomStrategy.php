<?php
namespace TinyApp\Model\Strategy;

use TinyApp\Model\Strategy\StrategyInterface;
use TinyApp\Model\Strategy\Order;

class RandomStrategy implements StrategyInterface
{
    const HOME_CURRENCY = 'CAD';
    const TAKE_PROFIT_MULTIPLIER = 3;
    const RIGID_STOP_LOSS_PIPS = 0.0010;
    const SINGLE_TRANSACTION_RISK = 0.01;

    /*
    This calculation follows the following formula:

    (Closing Rate - Opening Rate) * (Closing {quote}/{home currency}) * Units
    For example, suppose:

    Home: CAD
    Currency Pair: GBP/CHF
    Base: GBP; Quote: CHF
    Quote / Home = CHF/CAD = 1.1025
    Opening Rate = 2.1443
    Closing Rate = 2.1452
    Units = 1000

    Then:
    Profit = (2.1452 - 2.1443) * (1.1025) * 1000
    Profit = 0.99225 CAD

    To calculate units use:
    units = (max risk in home currency) / ((closing rate of currency pair - opening rate of currency pair) * quote/home rate)
    */

    public function getOrder(array $prices, float $balance) : Order
    {
        // select the lowest spread pair
        $minSpread = 1000;
        foreach ($prices as $instrument => $price) {
            $spread = $price['ask'] - $price['bid'];
            if ($spread < $minSpread) {
                $minSpread = $spread;
                $selectedInstrument = $instrument;
            }
        }
//@TODO ensure it works
//$selectedInstrument = 'EUR_USD';
        // get home rate
        $quoteCurrency = explode('_', $selectedInstrument)[1];
        if (isset($prices[$quoteCurrency . '_' . self::HOME_CURRENCY])) {
            $homeInstrument = $quoteCurrency . '_' . self::HOME_CURRENCY;
            $homeRate = ($prices[$homeInstrument]['bid'] + $prices[$homeInstrument]['ask']) / 2;
        } elseif(isset($prices[self::HOME_CURRENCY . '_' . $quoteCurrency])) {
            $homeInstrument = self::HOME_CURRENCY . '_' . $quoteCurrency;
            $homeRate = 2 / ($prices[$homeInstrument]['bid'] + $prices[$homeInstrument]['ask']);
        }

        if (empty($homeInstrument)) {
            throw new \Exception('Could not find home instrument for selected instrument ' . var_export($selectedInstrument, true));
        }


        // get stop loss in pips
        $balanceRisk = $balance * self::SINGLE_TRANSACTION_RISK;
        //@TODO take into account JPY multiply by 100
        $stopPips = self::RIGID_STOP_LOSS_PIPS;

        $direction = 0 &&  rand(0,1);

        if ($direction) {
            $price = (string)$prices[$instrument]['ask'];
            $takeProfit = (string)($price + (self::TAKE_PROFIT_MULTIPLIER * $stopPips));
            $stopLoss = (string)($price - $stopPips);
        } else {
            $price = (string)$prices[$instrument]['bid'];
            $takeProfit = (string)($price - (self::TAKE_PROFIT_MULTIPLIER * $stopPips));
            $stopLoss = (string)($price + $stopPips);
        }

        $units = (int)($balanceRisk / (abs($stopLoss - $price) * $homeRate));
        $units = $direction ? $units : -$units;

        return new Order($selectedInstrument, $units, $takeProfit, $stopLoss);
    }
}
