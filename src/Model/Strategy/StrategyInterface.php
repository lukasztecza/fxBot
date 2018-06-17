<?php declare(strict_types=1);
namespace FxBot\Model\Strategy;

use FxBot\Model\Entity\Order;

interface StrategyInterface
{
    public function getOrder(array $price, float $balance) : ?Order;
}
