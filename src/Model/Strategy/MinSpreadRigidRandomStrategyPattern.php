<?php
namespace TinyApp\Model\Strategy;

use TinyApp\Model\Strategy\MinSpreadRigidStrategyAbstract;
use TinyApp\Model\Strategy\RandomTrait;

class MinSpreadRigidRandomStrategyPattern extends MinSpreadRigidStrategyAbstract
{
    use RandomTrait;
}
