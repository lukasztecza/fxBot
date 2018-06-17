<?php declare(strict_types=1);
namespace FxBot\Model\Strategy;

use FxBot\Model\Entity\Order;
use FxBot\Model\Entity\OrderModification;

interface StrategyInterface
{
    public function getOrder(array $price, float $balance) : ?Order;

//    public function getOrderModification(string $tradeId, float $price) : ?OrderModification;
}
