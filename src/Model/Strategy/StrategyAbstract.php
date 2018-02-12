<?php
namespace TinyApp\Model\Strategy;

use TinyApp\Model\Strategy\StrategyInterface;
use TinyApp\Model\Service\PriceService;
use TinyApp\Model\Service\IndicatorService;
use TinyApp\Model\Strategy\Order;

abstract class StrategyAbstract implements StrategyInterface
{
    private const HOME_CURRENCY = 'CAD';
    private const SINGLE_TRANSACTION_RISK = 0.01;

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
    units = (max risk in home currency) / ((closing rate of currency instrument - opening rate of currency instrument) * quote/home rate)
    */
    public function calculateUnits(
        float $balance,
        array $currentPrices,
        string $tradeInstrument,
        float $closeTradeInstrumentRate
    ) {
        $currencies = explode('_', $tradeInstrument);
        $baseCurrency = $currencies[0];
        $quoteCurrency = $currencies[1];
        $homeInstrument = $homeInstrumentRate = null;

        switch (true) {
            case $baseCurrency === self::HOME_CURRENCY:
                $homeInstrument = $tradeInstrument;
                $homeInstrumentRate = 2 / ($currentPrices[$homeInstrument]['bid'] + $currentPrices[$homeInstrument]['ask']);
                break;
            case $quoteCurrency === self::HOME_CURRENCY:
                $homeInstrument = $tradeInstrument;
                $homeInstrumentRate = ($currentPrices[$homeInstrument]['bid'] + $currentPrices[$homeInstrument]['ask']) / 2;
                break;
            case isset($currentPrices[$quoteCurrency . '_' . self::HOME_CURRENCY]):
                $homeInstrument = $quoteCurrency . '_' . self::HOME_CURRENCY;
                $homeInstrumentRate = ($currentPrices[$homeInstrument]['bid'] + $currentPrices[$homeInstrument]['ask']) / 2;
                break;
            case isset($currentPrices[self::HOME_CURRENCY . '_' . $quoteCurrency]):
                $homeInstrument = self::HOME_CURRENCY . '_' . $quoteCurrency;
                $homeInstrumentRate = 2 / ($currentPrices[$homeInstrument]['bid'] + $currentPrices[$homeInstrument]['ask']);
                break;
        }
        if (empty($homeInstrument)) {
            throw new \Exception('Could not find home instrument for trade instrument ' . var_export($tradeInstrument, true));
        }

        $balanceRisk = $balance * self::SINGLE_TRANSACTION_RISK;

        $openTradeInstrumentRate = null;
        if ($closeTradeInstrumentRate > $currentPrices[$tradeInstrument]['ask']) {
            $openTradeInstrumentRate = $currentPrices[$tradeInstrument]['bid'];
        } elseif($closeTradeInstrumentRate < $currentPrices[$tradeInstrument]['bid']) {
            $openTradeInstrumentRate = $currentPrices[$tradeInstrument]['ask'];
        }
        if (empty($openTradeInstrumentRate)) {
            throw new \Exception(
                'Wrong close price of the trade instrument ' . var_export($closeTradeInstrumentRate, true) .
                ' for current prices ' . var_export($currentPrices[$tradeInstrument], true)
            );
        }

        return (int)($balanceRisk / (abs($closeTradeInstrumentRate - $openTradeInstrumentRate) * $homeInstrumentRate));
    }

    abstract function getOrder(array $prices, float $balance) : ?Order;
}
