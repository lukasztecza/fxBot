<?php declare(strict_types=1);
namespace FxBot\Model\Strategy;

use FxBot\Model\Strategy\StrategyAbstract;
use FxBot\Model\Entity\Order;
use FxBot\Model\Entity\OrderModification;

abstract class RigidStrategyAbstract extends StrategyAbstract
{
    private const SPECIAL_INSTRUMENTS = [
        'JPY' => 100
    ];

    protected $rigidStopLoss;
    protected $takeProfitMultiplier;

    public function __construct(
        string $homeCurrency,
        float $singleTransactionRisk,
        float $rigidStopLoss,
        float $takeProfitMultiplier
    ) {
        $this->rigidStopLoss = $rigidStopLoss;
        $this->takeProfitMultiplier = $takeProfitMultiplier;
        parent::__construct($homeCurrency, $singleTransactionRisk);
    }

    public function getOrder(array $prices, float $balance, string $currentDateTime = null) : ?Order
    {
        $direction = $this->getDirection($currentDateTime);

        $rigidStopLoss = $this->getRigidStopLoss();
        foreach (self::SPECIAL_INSTRUMENTS as $key => $modificationFactor) {
            if (strpos($this->getInstrument(), $key) !== false) {
                $rigidStopLoss *= $modificationFactor;
                break;
            }
        }

        if ($direction === 1) {
            $tradePrice = (float) $prices[$this->getInstrument()]['ask'];
            $takeProfit = ($tradePrice + ($this->getTakeProfitMultiplier() * $rigidStopLoss));
            $stopLoss = ($tradePrice - $rigidStopLoss);
        } elseif ($direction === -1) {
            $tradePrice = (float) $prices[$this->getInstrument()]['bid'];
            $takeProfit = ($tradePrice - ($this->getTakeProfitMultiplier() * $rigidStopLoss));
            $stopLoss = ($tradePrice + $rigidStopLoss);
        } else {
            return null;
        }

        $units = $this->calculateUnits($balance, $prices, $this->getInstrument(), $stopLoss);
        if (empty($units)) {
            trigger_error(
                'Could not calculate units for variables' . var_export([$balance, $prices, $this->getInstrument(), $stopLoss], true),
                E_USER_NOTICE
            );

            return null;
        }
        $units *= $direction;

        return new Order($this->getInstrument(), $units, $tradePrice, $takeProfit, $stopLoss);
    }
//TODO instead of lossLockerFactor there should be orderModification update that
    public function getOrderModification(
        string $orderId,
        string $tradeId,
        float $openPrice,
        float $currentStopLoss,
        float $currentTakeProfit,
        array $currentPrices
    ) : ?OrderModification {
        $price = $this->getPriceModification($openPrice, $currentStopLoss, $currentTakeProfit, $currentPrices);

        return !empty($price) ? new OrderModification($orderId, $tradeId, $price) : null;
    }

    public function getRigidStopLoss() : float
    {
        return $this->rigidStopLoss;
    }

    public function getTakeProfitMultiplier() : float
    {
        return $this->takeProfitMultiplier;
    }

    public function getStrategyParams() : array
    {
        $return['className'] = get_class($this);
        foreach ($this->getRequiredParams() as $requiredParam) {
            $return['params'][$requiredParam] = $this->$requiredParam;
        }

        return $return;
    }

    protected function getInstrument() : string
    {
        if (!isset($this->instrument)) {
            throw new \Exception('Instrument not set for strategy nor selected by strategy');
        }

        return $this->instrument;
    }

    abstract protected function getDirection(string $currentDateTime = null) : int;

    abstract protected function getPriceModification(float $openPrice, float $currentStopLoss, float $currentTakeProfit, array $currentPrices) : ?float;

    abstract protected function getRequiredParams() : array;
}
