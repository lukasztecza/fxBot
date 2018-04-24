<?php
namespace TinyApp\Model\Strategy;

use TinyApp\Model\Strategy\StrategyInterface;
use TinyApp\Model\Strategy\Order;

abstract class StrategyAbstract implements StrategyInterface
{
    private const HOME_CURRENCY = 'CAD';
    private const SINGLE_TRANSACTION_RISK = 0.03;

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
    protected function calculateUnits(
        float $balance,
        array $currentPrices,
        string $tradeInstrument,
        float $closeTradeInstrumentRate
    ) : int {
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
            trigger_error('Could not find home instrument for trade instrument ' . var_export($tradeInstrument, true), E_USER_NOTICE);

            return 0;
        }

        $balanceRisk = $balance * self::SINGLE_TRANSACTION_RISK;

        $openTradeInstrumentRate = null;
        if ($closeTradeInstrumentRate > $currentPrices[$tradeInstrument]['ask']) {
            $openTradeInstrumentRate = $currentPrices[$tradeInstrument]['bid'];
        } elseif($closeTradeInstrumentRate < $currentPrices[$tradeInstrument]['bid']) {
            $openTradeInstrumentRate = $currentPrices[$tradeInstrument]['ask'];
        }

        if (empty($openTradeInstrumentRate)) {
            trigger_error(
                'Wrong close price of the trade instrument ' . var_export($closeTradeInstrumentRate, true) .
                ' for current prices ' . var_export($currentPrices[$tradeInstrument], true),
                E_USER_NOTICE
            );

            return 0;
        }

        return (int)($balanceRisk / (abs($closeTradeInstrumentRate - $openTradeInstrumentRate) * $homeInstrumentRate));
    }

    protected function getAveragesByPeriods(array $lastPrices, int $fast, int $slow) : array
    {
        $averages = [
            'fast' => null,
            'slow' => null
        ];
        $sum = 0;
        $counter = 0;
        foreach ($lastPrices as $key => $price) {
            $sum += ($price['high'] + $price['low']) / 2;
            $counter++;
            switch (true) {
                case $fast - $counter === 0:
                    $averages['fast'] = $sum / $counter;
                    break 1;
                case $slow - $counter === 0:
                    $averages['slow'] = $sum / $counter;
                    break 2;
            }
        }

        return $averages;
    }

    protected function getTrend(array $lastPrices, int $extremumRange) : int
    {
        $this->appendLocalExtremas($lastPrices, $extremumRange);
        $lastHighs = [];
        $lastLows = [];
        foreach ($lastPrices as $price) {
            if (count($lastHighs) > 1 && count($lastLows) > 1) {
                break;
            }
            if (isset($price['extremum'])) {
                if ($price['extremum'] === 'max') {
                    $lastHighs[] = $price['high'];
                } elseif ($price['extremum'] === 'min') {
                    $lastLows[] = $price['low'];
                }
            }
        }

        if (count($lastHighs) > 1 && count($lastLows) > 1) {
            switch (true) {
                case $lastLows[0] > $lastLows[1] && $lastHighs[0] > $lastHighs[1]:
                    return 1;
                case $lastLows[0] < $lastLows[1] && $lastHighs[0] < $lastHighs[1]:
                    return -1;
            }
        }

        return 0;
    }

    protected function getDeviationDirection(array $lastPrices, int $fast, int $slow) : int
    {
        if (!isset($lastPrices[0]['high']) || !isset($lastPrices[0]['low'])) {
            return 0;
        }
        $averages = $this->getAveragesByPeriods($lastPrices, $fast, $slow);
        $averages['current'] = ($lastPrices[0]['high'] + $lastPrices[0]['low']) / 2;

        switch (true) {
            case $averages['current'] < $averages['fast'] && $averages['current'] > $averages['slow']:
                return -1;
            case $averages['current'] > $averages['fast'] && $averages['current'] < $averages['slow']:
                return 1;
            default:
                return 0;
        }
    }

    private function appendLocalExtremas(array &$lastPrices, int $extremumRange) : void
    {
        foreach ($lastPrices as $key => $value) {
            $scoreMax = 0;
            $scoreMin = 0;
            for ($i = -$extremumRange; $i <= $extremumRange; $i++) {
                if (!isset($lastPrices[$key + $i])) {
                    continue 2;
                }
                if ($lastPrices[$key + $i]['high'] <= $value['high']) {
                    $scoreMax++;
                }
                if ($lastPrices[$key + $i]['low'] >= $value['low']) {
                    $scoreMin++;
                }
            }

            if ($scoreMax === 2 * $extremumRange + 1) {
                $lastPrices[$key]['extremum'] = 'max';
            }
            if ($scoreMin === 2 * $extremumRange + 1) {
                $lastPrices[$key]['extremum'] = 'min';
            }
        }
    }

    abstract public function getOrder(array $prices, float $balance) : ?Order;
}
