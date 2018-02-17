<?php
namespace TinyApp\Model\Strategy;

use TinyApp\Model\Strategy\Order;

interface StrategyInterface
{
    public function getOrder(array $price, float $balance) : ?Order;
}
