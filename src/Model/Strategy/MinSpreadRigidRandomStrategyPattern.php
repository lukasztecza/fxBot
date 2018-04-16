<?php
namespace TinyApp\Model\Strategy;

use TinyApp\Model\Strategy\MinSpreadRigidStrategyAbstract;
use TinyApp\Model\Strategy\RandomTrait;

class MinSpreadRigidRandomStrategyPattern extends MinSpreadRigidStrategyAbstract
{
    use RandomTrait;

    public function __construct(array $params)
    {
        if (
            empty($params['rigidStopLoss']) ||
            empty($params['takeProfitMultiplier'])
        ) {
            throw new \Exception('Got wrong params ' . var_export($params, true));
        }

        parent::__construct($params);
    }
}
