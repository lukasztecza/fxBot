<?php
namespace FxBot\Model\Strategy;

use FxBot\Model\Strategy\Order;

interface StrategyInterface
{
    public function getOrder(array $price, float $balance) : ?Order;
}
