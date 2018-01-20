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

    protected $priceService;
    protected $indicatorService;

    public function __construct(PriceService $priceService, IndicatorService $indicatorService)
    {
        $this->priceService = $priceService;
        $this->indicatorService = $indicatorService;
    }

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
    public function calculateUnits(
        float $balance,
        array $currentPrices,
        string $tradePair,
        float $closeTradePairRate
    ) {
        $quoteCurrency = explode('_', $tradePair)[1];
        $homePair = $homePairRate = null;
        if (isset($currentPrices[$quoteCurrency . '_' . self::HOME_CURRENCY])) {
            $homePair = $quoteCurrency . '_' . self::HOME_CURRENCY;
            $homePairRate = ($currentPrices[$homePair]['bid'] + $currentPrices[$homePair]['ask']) / 2;
        } elseif(isset($currentPrices[self::HOME_CURRENCY . '_' . $quoteCurrency])) {
            $homePair = self::HOME_CURRENCY . '_' . $quoteCurrency;
            $homePairRate = 2 / ($currentPrices[$homePair]['bid'] + $currentPrices[$homePair]['ask']);
        }
        if (empty($homePair)) {
            throw new \Exception('Could not find home pair for trade pair ' . var_export($tradePair, true));
        }

        $balanceRisk = $balance * self::SINGLE_TRANSACTION_RISK;

        $openTradePairRate = null;
        if ($closeTradePairRate > $currentPrices[$tradePair]['ask']) {
            $openTradePairRate = $currentPrices[$tradePair]['bid'];
        } elseif($closeTradePairRate < $currentPrices[$tradePair]['bid']) {
            $openTradePairRate = $currentPrices[$tradePair]['ask'];
        }
        if (empty($openTradePairRate)) {
            throw new \Exception(
                'Wrong close price of the trade pair ' . var_export($closeTradePairRate, true) .
                ' for current prices ' . var_export($currentPrices[$tradePair], true)
            );
        }

        return (int)($balanceRisk / (abs($closeTradePairRate - $openTradePairRate) * $homePairRate));
    }

    abstract function getOrder(array $prices, float $balance) : Order;
}
