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
        $namespaceLassClass = lcfirst(substr($class, strrpos($class, '\\') + 1));

        if (isset($this->strategies[$namespaceLassClass])) {
            return $this->strategies[$namespaceLassClass];
        }

        return new $class($this->priceService, $this->indicatorService);
    }
}
