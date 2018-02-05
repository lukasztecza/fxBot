<?php
namespace TinyApp\Model\Strategy;

use TinyApp\Model\Strategy\MinSpreadRigidOneMultiOneRandomStrategy;

class MinSpreadRigidTwoMultiFiveRandomStrategy extends MinSpreadRigidOneMultiOneRandomStrategy
{
    protected const TAKE_PROFIT_MULTIPLIER = 5;
    protected const RIGID_STOP_LOSS_PIPS = 0.0020;
}
