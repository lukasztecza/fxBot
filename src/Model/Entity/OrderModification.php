<?php declare(strict_types=1);
namespace FxBot\Model\Entity;

class OrderModification
{
    private const DEFAULT_TYPE = 'STOP_LOSS';
    private const DEFAULT_STOP_TIME_IN_FORCE = 'GTC';
    private const DEFAULT_TRIGGER_CONDITION = 'DEFAULT';

    private $tradeId;
    private $orderId;
    private $price;
    private $type;
    private $timeInForce;
    private $positionFill;

    public function __construct(
        string $tradeId,
        string $orderId,
        float $price,
        string $type = null,
        string $timeInForce = null,
        string $triggerCondition = null
    ) {
        $this->tradeId = $tradeId;
        $this->orderId = $orderId;
        $this->price = $price;
        $this->type = $type ?? self::DEFAULT_TYPE;
        $this->timeInForce = $timeInForce ?? self::DEFAULT_STOP_TIME_IN_FORCE;
        $this->triggerCondition = $triggerCondition ?? self::DEFAULT_TRIGGER_CONDITION;
    }

    public function getTradeId() : string
    {
        return $this->tradeId;
    }

    public function getOrderId() : string
    {
        return $this->orderId;
    }

    public function getPrice() : float
    {
        return $this->price;
    }

    public function getType() : string
    {
        return $this->type;
    }

    public function getTimeInForce() : string
    {
        return $this->timeInForce;
    }

    public function getTriggerCondition() : string
    {
        return $this->triggerCondition;
    }

    public function getFormatted() : array
    {
        return [
            'order' => [
                'tradeID' => $this->tradeId,
                'price'=> $this->price,
                'type' => $this->type,
                'timeInForce' => $this->timeInForce,
                'triggerCondition' => $this->triggerCondition
            ]
        ];
    }
}
