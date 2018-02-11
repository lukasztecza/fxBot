<?php
namespace TinyApp\Model\Strategy;

use TinyApp\Model\Strategy\StrategyInterface;
use TinyApp\Model\Strategy\MinSpreadRigidOneMultiOneStrategyAbstract;
use TinyApp\Model\Service\PriceService;
use TinyApp\Model\Service\IndicatorService;

class MinSpreadRigidOneMultiOneTrendFindStrategy extends MinSpreadRigidOneMultiOneStrategyAbstract
{
    protected $priceService;

    public function __construct(PriceService $priceService)
    {
        $this->priceService = $priceService;
    }

    protected function getDirection(string $currentDate = null) : int
    {
        var_dump($currentDate);exit;
        //@TODO fetch week of prices and try to find recent extremas



        return 1;
    }
}
