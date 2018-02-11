<?php
namespace TinyApp\Model\Strategy;

use TinyApp\Model\Strategy\MinSpreadRigidOneMultiOneRandomStrategy;

class MinSpreadRigidTwoMultiThreeRandomStrategy extends MinSpreadRigidOneMultiOneRandomStrategy
{
    protected const TAKE_PROFIT_MULTIPLIER = 3;
    protected const RIGID_STOP_LOSS_PIPS = 0.0020;
}
