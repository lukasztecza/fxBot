<?php
namespace TinyApp\Model\Command;

use TinyApp\Model\Command\CommandResult;
use TinyApp\Model\Service\PriceService;
use TinyApp\Model\Service\IndicatorService;
use TinyApp\Model\Repository\MemoryStorageInterface;

class StoreInMemoryCommand implements CommandInterface
{
    private const START_DATE = '2010-03-01 00:00:00';
    private const END_DATE = '2011-03-01 00:00:00';
    private const SIMULATION_STEP = 'PT20M';

    private const LAST_PRICES_PERIOD = 'P7D';
    private const MAX_ITERATIONS_PER_STRATEGY = 4000000;

    private $priceInstruments;
    private $priceService;
    private $indicatorService;
    private $memoryStorage;

    public function __construct(
        array $priceInstruments,
        PriceService $priceService,
        IndicatorService $indicatorService,
        MemoryStorageInterface $memoryStorage
    ) {
        $this->priceInstruments = $priceInstruments;
        $this->priceService = $priceService;
        $this->indicatorService = $indicatorService;
        $this->memoryStorage = $memoryStorage;
    }

    public function execute() : CommandResult
    {
        $currentDate = self::START_DATE;
        $counter = 0;
        while ($counter < self::MAX_ITERATIONS_PER_STRATEGY && $currentDate < self::END_DATE) {
            $counter++;
            $currentDate = (new \DateTime($currentDate, new \DateTimeZone('UTC')));
            if ($currentDate->format('d H:i:s') === '01 00:00:00') {
                echo $currentDate->format('Y-m-d') . PHP_EOL;
            }
            $currentDate = $currentDate->add(new \DateInterval(self::SIMULATION_STEP))->format('Y-m-d H:i:s');

            $initial = $this->priceService->getInitialPrices($this->priceInstruments, $currentDate);
            $this->memoryStorage->set('initial_' . str_replace(' ', '_', $currentDate), json_encode($initial));
            foreach ($this->priceInstruments as $instrument) {
                $lastPrices = $this->priceService->getLastPricesByPeriod($instrument, self::LAST_PRICES_PERIOD, $currentDate);
                $this->memoryStorage->set('last_' . $instrument . '_' . str_replace(' ', '_', $currentDate), json_encode($lastPrices));
            }
        }

        return new CommandResult(true, 'inserted');
    }
}
