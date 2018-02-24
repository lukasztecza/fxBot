<?php
namespace TinyApp\Model\Strategy;

use TinyApp\Model\Strategy\RigidStrategyAbstract;
use TinyApp\Model\Strategy\RandomTrait;

class RigidRandomStrategyPattern extends RigidStrategyAbstract
{
    use RandomTrait;
}
