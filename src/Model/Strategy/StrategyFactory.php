<?php
namespace TinyApp\Model\Strategy;

use TinyApp\Model\Service\PriceService;
use TinyApp\Model\Service\IndicatorService;

class StrategyFactory
{
    private $priceService;
    private $indicatorService;
    private $strategies;

    public function __construct(PriceService $priceService, IndicatorService $indicatorService)
    {
        $this->priceService = $priceService;
        $this->indicatorService = $indicatorService;
    }

    public function getStrategy(string $class) : StrategyInterface
    {
        if (!in_array(StrategyInterface::class, class_implements($class))) {
            throw new \Exception('Wrong class exception, ' . $class . ' has to implement ' . StrategyInterface::class);
        }
        $namespaceLessClass = lcfirst(substr($class, strrpos($class, '\\') + 1));

        if (isset($this->strategies[$namespaceLessClass])) {
            return $this->strategies[$namespaceLessClass];
        }

        switch ($class) {
            case 'TinyApp\Model\Strategy\MinSpreadRigidOneMultiOneTrendFindStrategy':
                $strategy = new $class($this->priceService);
                break;
            default:
                $strategy = new $class();
                break;
        }

        $this->strategies[$namespaceLessClass] = $strategy;
        return $this->strategies[$namespaceLessClass];
    }
}
