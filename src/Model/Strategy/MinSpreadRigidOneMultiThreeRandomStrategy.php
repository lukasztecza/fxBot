<?php
namespace TinyApp\Model\Strategy;

use TinyApp\Model\Strategy\MinSpreadRigidOneMultiOneRandomStrategy;

class MinSpreadRigidOneMultiThreeRandomStrategy extends MinSpreadRigidOneMultiOneRandomStrategy
{
    protected const TAKE_PROFIT_MULTIPLIER = 3;
    protected const RIGID_STOP_LOSS_PIPS = 0.0010;
}
