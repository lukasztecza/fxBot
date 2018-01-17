<?php
namespace TinyApp\Model\Strategy;

class Order
{
    private const DEFAULT_TYPE = 'MARKET';
    private const DEFAULT_STOP_TIME_IN_FORCE = 'GTC';
    private const DEFAULT_TIME_IN_FORCE = 'FOK';
    private const DEFAULT_POSITION_FILL = 'REDUCE_FIRST';

    private $instrument;
    private $units;
    private $takeProfit;
    private $stopLoss;
    private $type;
    private $timeInForce;
    private $positionFill;

    public function __construct(
        string $instrument,
        int $units,
        string $takeProfit,
        string $stopLoss,
        string $type = null,
        string $timeInForce = null,
        string $positionFill = null
    ) {
        $this->instrument = $instrument;
        $this->units = $units;
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

    public function getType() : string
    {
        return $this->type;
    }

    public function getTimeInForce() : string
    {
        return $this->timeInfForce;
    }

    public function getUnits() : int
    {
        return $this->units;
    }

    public function getTakeProfit() : string
    {
        return $this->takeProfit;
    }

    public function getStopLoss() : string
    {
        return $this->stopLoss;
    }

    public function getPositionFill() : string
    {
        return $this->positionFill;
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
                    'price'=> $this->stopLoss
                ],
                'takeProfitOnFill' => [
                    'timeInForce'=> self::DEFAULT_STOP_TIME_IN_FORCE,
                    'price'=> $this->takeProfit
                ]
            ]
        ];
    }
}