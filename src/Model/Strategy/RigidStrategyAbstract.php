<?php declare(strict_types=1);
namespace FxBot\Model\Strategy;

use FxBot\Model\Strategy\StrategyAbstract;
use FxBot\Model\Entity\Order;

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
            $tradePrice = $prices[$this->getInstrument()]['ask'];
            $takeProfit = ($tradePrice + ($this->getTakeProfitMultiplier() * $rigidStopLoss));
            $stopLoss = ($tradePrice - $rigidStopLoss);
        } elseif ($direction === -1) {
            $tradePrice = $prices[$this->getInstrument()]['bid'];
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

/*    public function getOrderModification(
        string $instrument,

        float $currentStopLoss,
        float $currentTakeProfit,
        string $tradeId,
        string $currentDateTime = null
    ) : ?OrderModification {
        $price = $this->getPriceModification('blah todo finish it', $currentStopLoss, $currentTakeProfit);

        return !empty($price) ? new OrderModification($orderId, $tradeId, $price) : null;
    }
*/
    public function getRigidStopLoss() : float
    {
        return $this->rigidStopLoss;
    }

    public function getTakeProfitMultiplier() : float
    {
        return $this->takeProfitMultiplier;
    }

    abstract public function getStrategyParams() : array;

    abstract protected function getInstrument() : string;

    abstract protected function getDirection(string $currentDateTime = null) : int;

//    abstract protected function getPriceModification(string $currentDateTime = null, string $selectedInstrument = null) : float;
}
