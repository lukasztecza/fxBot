<?php
namespace TinyApp\Model\Strategy;

use TinyApp\Model\Strategy\StrategyInterface;
use TinyApp\Model\Strategy\MinSpreadRigidOneMultiOneStrategyAbstract;

class MinSpreadRigidOneMultiOneRandomStrategy extends MinSpreadRigidOneMultiOneStrategyAbstract
{
    protected function getDirection(string $currentDate = null) : int
    {
        return rand(0, 1);
    }
}
