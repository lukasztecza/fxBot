<?php declare(strict_types=1);
namespace FxBot\Model\Strategy;

use FxBot\Model\Strategy\StrategyInterface;
use FxBot\Model\Entity\Order;
use FxBot\Model\Entity\OrderModification;

abstract class StrategyAbstract implements StrategyInterface
{
    protected $homeCurrency;
    protected $singleTransactionRisk;

    public function __construct(string $homeCurrency, float $singleTransactionRisk)
    {
        $this->homeCurrency = $homeCurrency;
        $this->singleTransactionRisk = $singleTransactionRisk;
    }

    /*
    This calculation follows the following formula:

    (Trade closing rate - Trade opening rate) * ({Quote currency}/{Home currency} rate) * Units

    For example, suppose:

    Trade currency pair: GBP/CHF
    Trade opening rate (for GBP/CHF) = 2.1443
    Trade closing rate (for GBP/CHF) = 2.1452
    Base currency: GBP
    Quote currency: CHF

    Home currency: CAD
    {Quote  currency}/{Home currency} rate (for CHF/CAD) = 1.1025
    Units = 1000

    Then:

    Profit = (2.1452 - 2.1443) * (1.1025) * 1000
    Profit = 0.99225 CAD

    To calculate units use:
    units = (max risk in home currency) / ((Trade closing rate - Trade opening rate) * ({Quote currency}/{Home currency} rate))
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
            case $baseCurrency === $this->homeCurrency:
                $homeInstrument = $tradeInstrument;
                $homeInstrumentRate = 2 / ($currentPrices[$homeInstrument]['bid'] + $currentPrices[$homeInstrument]['ask']);
                break;
            case $quoteCurrency === $this->homeCurrency:
                $homeInstrument = $tradeInstrument;
                $homeInstrumentRate = ($currentPrices[$homeInstrument]['bid'] + $currentPrices[$homeInstrument]['ask']) / 2;
                break;
            case isset($currentPrices[$quoteCurrency . '_' . $this->homeCurrency]):
                $homeInstrument = $quoteCurrency . '_' . $this->homeCurrency;
                $homeInstrumentRate = ($currentPrices[$homeInstrument]['bid'] + $currentPrices[$homeInstrument]['ask']) / 2;
                break;
            case isset($currentPrices[$this->homeCurrency . '_' . $quoteCurrency]):
                $homeInstrument = $this->homeCurrency . '_' . $quoteCurrency;
                $homeInstrumentRate = 2 / ($currentPrices[$homeInstrument]['bid'] + $currentPrices[$homeInstrument]['ask']);
                break;
        }
        if (empty($homeInstrument)) {
            trigger_error('Could not find home instrument for trade instrument ' . var_export($tradeInstrument, true), E_USER_NOTICE);

            return 0;
        }

        $balanceRisk = $balance * $this->singleTransactionRisk;

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

    protected function getAveragesByPeriods(array $lastPrices, array $averages) : array
    {
        $sum = 0;
        $counter = 0;
        $return = [];
        end($averages);
        $lastName = key($averages);
        reset($averages);
        foreach ($lastPrices as $key => $price) {
            $sum += ($price['high'] + $price['low']) / 2;
            $counter++;
            foreach ($averages as $name => $period) {
                if ((int)($period - $counter) === 0) {
                    $return[$name] = $sum / $counter;
                    if ($lastName === $name) {
                        break 2;
                    }
                }
            }
        }

        foreach ($averages as $name => $period) {
            if (empty($return[$name])) {
                trigger_error('Could not create proper averages array', E_USER_NOTICE);
            }
        }

        return $return;
    }

    protected function getChannelDirection(array $lastPrices, int $extremumRange) : int
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
        $averages = $this->getAveragesByPeriods($lastPrices, ['fast' => $fast, 'slow' => $slow]);
        $averages['current'] = ($lastPrices[0]['high'] + $lastPrices[0]['low']) / 2;

        switch (true) {
            case !isset($averages['current']) || !isset($averages['fast']) || !isset($averages['slow']):
                return 0;
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

//    abstract public function getOrderModification(string $tradeId, float $price) : ?OrderModification;
}
