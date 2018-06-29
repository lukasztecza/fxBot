<?php declare(strict_types=1);
namespace FxBot\Model\Entity;

use FxBot\Model\Entity\OrderModification;

class Order
{
    private const DEFAULT_TYPE = 'MARKET';
    private const DEFAULT_STOP_TIME_IN_FORCE = 'GTC';
    private const DEFAULT_TIME_IN_FORCE = 'FOK';
    private const DEFAULT_POSITION_FILL = 'REDUCE_FIRST';

    private $instrument;
    private $units;
    private $price;
    private $takeProfit;
    private $stopLoss;
    private $type;
    private $timeInForce;
    private $positionFill;

    public function __construct(
        string $instrument,
        int $units,
        float $price,
        float $takeProfit,
        float $stopLoss,
        string $type = null,
        string $timeInForce = null,
        string $positionFill = null
    ) {
        $this->instrument = $instrument;
        $this->units = $units;
        $this->price = $price;
        $this->takeProfit = $takeProfit;
        $this->stopLoss = $stopLoss;
        $this->type = $type ?? self::DEFAULT_TYPE;
        $this->timeInForce = $timeInForce ?? self::DEFAULT_TIME_IN_FORCE;
        $this->positionFill = $positionFill ?? self::DEFAULT_POSITION_FILL;
    }

    public function getInstrument() : string
    {
        return $this->instrument;
    }

    public function getUnits() : int
    {
        return $this->units;
    }

    public function getPrice() : float
    {
        return $this->price;
    }

    public function getTakeProfit() : float
    {
        return $this->takeProfit;
    }

    public function getStopLoss() : float
    {
        return $this->stopLoss;
    }

    public function getType() : string
    {
        return $this->type;
    }

    public function getTimeInForce() : string
    {
        return $this->timeInForce;
    }

    public function getPositionFill() : string
    {
        return $this->positionFill;
    }

    public function applyOrderModification(OrderModification $orderModification) : void
    {
        if ($orderModification->getType() === $orderModification->getStopLossType()) {
            $this->stopLoss = $orderModification->getPrice();
        } elseif ($orderModification->getType() === $orderModification->getTakeProfitType()) {
            $this->takeProfit = $orderModification->getPrice();
        }
    }

    public function getFormatted() : array
    {
        return [
            'order' => [
                'units' => $this->units,
                'instrument' => $this->instrument,
                'timeInForce' => $this->timeInForce,
                'type' => $this->type,
                'positionFill' => $this->positionFill,
                'stopLossOnFill'=> [
                    'timeInForce'=> self::DEFAULT_STOP_TIME_IN_FORCE,
                    'price'=> (string) round($this->stopLoss, 5)
                ],
                'takeProfitOnFill' => [
                    'timeInForce'=> self::DEFAULT_STOP_TIME_IN_FORCE,
                    'price'=> (string) round($this->takeProfit, 5)
                ]
            ]
        ];
    }
}
