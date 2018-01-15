<?php
namespace TinyApp\Model\Strategy;

use TinyApp\Model\Strategy\Order;

interface StrategyInterface
{
    public function getOrderForPrice(float $price) : Order;
}
