<?php
namespace TinyApp\Model\Strategy;

use TinyApp\Model\Strategy\StrategyAbstract;
use TinyApp\Model\Strategy\Order;

abstract class MinSpreadRigidStrategyAbstract extends RigidStrategyAbstract
{
    public function __construct(float $rigidStopLoss, float $takeProfitMultiplier)
    {
        $this->rigidStopLoss = $rigidStopLoss;
        $this->takeProfitMultiplier = $takeProfitMultiplier;
    }

    public function getOrder(array $prices, float $balance, string $currentDateTime = null) : ?Order
    {
        // select the lowest spread instrument
        $minSpread = 100;
        foreach ($prices as $instrument => $price) {
            $spread = $price['ask'] - $price['bid'];
            if ($spread < $minSpread) {
                $minSpread = $spread;
                $selectedInstrument = $instrument;
            }
        }
        $this->instrument = $selectedInstrument;

        return parent::getOrder($prices, $balance, $currentDateTime);
    }
}
