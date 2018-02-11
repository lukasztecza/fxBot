<?php
namespace TinyApp\Model\Strategy;

use TinyApp\Model\Strategy\StrategyInterface;
use TinyApp\Model\Strategy\MinSpreadRigidOneMultiOneStrategyAbstract;

class MinSpreadRigidTwoMultiOneRandomStrategy extends MinSpreadRigidOneMultiOneRandomStrategy
{
    protected const TAKE_PROFIT_MULTIPLIER = 1;
    protected const RIGID_STOP_LOSS_PIPS = 0.0020;
}
