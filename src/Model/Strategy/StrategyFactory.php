<?php
namespace TinyApp\Model\Strategy;

use TinyApp\Model\Service\PriceService;
use TinyApp\Model\Service\IndicatorService;
use TinyApp\Model\Strategy\StrategyInterface;

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

    public function getStrategy(string $class, array $params = []) : StrategyInterface
    {
        if (!in_array(StrategyInterface::class, class_implements($class))) {
            throw new \Exception('Wrong class exception, ' . $class . ' has to implement ' . StrategyInterface::class);
        }
        $namespaceLessClass = lcfirst(substr($class, strrpos($class, '\\') + 1));

        if (isset($this->strategies[$namespaceLessClass])) {
            return $this->strategies[$namespaceLessClass];
        }

        switch ($class) {
            case 'TinyApp\Model\Strategy\MinSpreadRigidTrendingStrategyPattern':
            case 'TinyApp\Model\Strategy\RigidTrendingStrategyPattern':
                $strategy = new $class($this->priceService, ...$params);
                break;
            case 'TinyApp\Model\Strategy\MinSpreadRigidStrategyPattern':
            case 'TinyApp\Model\Strategy\RigidStrategyPattern':
                $strategy = new $class(...$params);
                break;
            //default strategies are not patterns so there is no need to create new object every time it is called
            default:
                $strategy = new $class();
                $this->strategies[$namespaceLessClass] = $strategy;
                break;
        }

        return $strategy;
    }
}
