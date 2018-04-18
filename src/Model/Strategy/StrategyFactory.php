<?php
namespace TinyApp\Model\Strategy;

use TinyApp\Model\Service\PriceService;
use TinyApp\Model\Service\IndicatorService;
use TinyApp\Model\Strategy\StrategyInterface;

class StrategyFactory
{
    private $priceIinstruments;
    private $priceService;
    private $indicatorService;
    private $strategies;

    public function __construct(array $priceInstruments, PriceService $priceService, IndicatorService $indicatorService)
    {
        $this->priceInstruments = $priceInstruments;
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
            case 'TinyApp\Model\Strategy\RigidFundamentalTrendingLongAveragesDeviationStrategyPattern':
            case 'TinyApp\Model\Strategy\RigidFundamentalTrendingDeviationStrategyPattern':
            case 'TinyApp\Model\Strategy\RigidFundamentalTrendingAverageDistanceStrategyPattern':
            case 'TinyApp\Model\Strategy\RigidFundamentalTrendingStrategyPattern':
                $strategy = new $class($this->priceInstruments, $this->priceService, $this->indicatorService, $params);
                break;
            case 'TinyApp\Model\Strategy\MinSpreadRigidTrendingStrategyPattern':
            case 'TinyApp\Model\Strategy\RigidTrendingDeviationStrategyPattern':
            case 'TinyApp\Model\Strategy\RigidTrendingAverageDistanceStrategyPattern':
            case 'TinyApp\Model\Strategy\RigidTrendingStrategyPattern':
            case 'TinyApp\Model\Strategy\RigidDeviationStrategyPattern':
            case 'TinyApp\Model\Strategy\RigidMultipleAveragesStrategyPattern':
                $strategy = new $class($this->priceService, $params);
                break;
            case 'TinyApp\Model\Strategy\MinSpreadRigidRandomStrategyPattern':
            case 'TinyApp\Model\Strategy\RigidRandomStrategyPattern':
                $strategy = new $class($params);
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
