<?php declare(strict_types=1);
namespace FxBot\Model\Strategy;

use FxBot\Model\Service\PriceService;
use FxBot\Model\Service\IndicatorService;
use FxBot\Model\Strategy\StrategyInterface;

class StrategyFactory
{
    private $priceIinstruments;
    private $priceService;
    private $indicatorService;

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

        return new $class($this->priceInstruments, $this->priceService, $this->indicatorService, $params);
    }
}
