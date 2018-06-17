<?php declare(strict_types=1);
namespace FxBot\Model\Strategy;

use FxBot\Model\Strategy\StrategyAbstract;
use FxBot\Model\Entity\Order;

abstract class RigidStrategyAbstract extends StrategyAbstract
{
    protected $rigidStopLoss;
    protected $takeProfitMultiplier;
    protected $lossLockerFactor;
    protected $instrument;

    public function __construct(float $rigidStopLoss, float $takeProfitMultiplier, float $lossLockerFactor, string $instrument)
    {
        $this->rigidStopLoss = $rigidStopLoss;
        $this->takeProfitMultiplier = $takeProfitMultiplier;
        $this->lossLockerFactor = $lossLockerFactor;
        $this->instrument = $instrument;
    }

    public function getOrder(array $prices, float $balance, string $currentDateTime = null) : ?Order
    {
        $direction = $this->getDirection($currentDateTime, $this->getInstrument());

        $rigidStopLoss = $this->getRigidStopLoss();
        if (strpos($this->getInstrument(), 'JPY') !== false) {
            $rigidStopLoss *= 100;
        }

        if ($direction === 1) {
            $tradePrice = $prices[$this->getInstrument()]['ask'];
            $takeProfit = (string)($tradePrice + ($this->getTakeProfitMultiplier() * $rigidStopLoss));
            $stopLoss = (string)($tradePrice - $rigidStopLoss);
        } elseif ($direction === -1) {
            $tradePrice = $prices[$this->getInstrument()]['bid'];
            $takeProfit = (string)($tradePrice - ($this->getTakeProfitMultiplier() * $rigidStopLoss));
            $stopLoss = (string)($tradePrice + $rigidStopLoss);
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

    public function getRigidStopLoss() : float
    {
        return $this->rigidStopLoss;
    }

    public function getTakeProfitMultiplier() : float
    {
        return $this->takeProfitMultiplier;
    }

    public function getLossLockerFactor() : float
    {
        return $this->lossLockerFactor;
    }

    public function getInstrument() : string
    {
        return $this->instrument;
    }

    abstract protected function getDirection(string $currentDateTime = null, string $selectedInstrument = null) : int;
}
