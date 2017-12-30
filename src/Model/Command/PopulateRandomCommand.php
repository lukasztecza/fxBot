<?php
namespace TinyApp\Model\Command;

use TinyApp\Model\Command\CommandResult;
use TinyApp\Model\Service\PriceService;

class PopulateRandomCommand implements CommandInterface
{
    private const DEFAULT_INSTRUMENT = 'EUR_USD';

    private $priceService;

    public function __construct(PriceService $priceService)
    {
        $this->priceService = $priceService;
    }

    public function execute() : CommandResult
    {
        $prices = $this->getRandomPrices();
        $result = $this->priceService->savePrices($prices);

        return new CommandResult(true, 'inserted');
    }

    private function getRandomPrices() : array
    {
        $pack = 'test_' . md5(time() . rand(1,1000000));
        $price = 100000; // work on ints instead of floats
        $itertions = 5000;
        $priceChangeFactor = 200; // 20 pips
        $priceFluctuation = 50; // 5 pips
        $dateString = '2017-01-01 00:00:00';
        $values = [];

        for ($i = 0; $i < $itertions; $i++) {
            $dateString = date('Y-m-d H:i:s', strtotime($dateString . ' +15min'));
            $open = $price;
            $close = $price + rand(-$priceChangeFactor, $priceChangeFactor);
            $high = $open < $close ? $close + rand(0, $priceFluctuation) : $open + rand(0, $priceFluctuation);
            $low = $open < $close ? $open - rand(0, $priceFluctuation) : $close - rand(0, $priceFluctuation);
            $prices[] = [
                'pack' => $pack,
                'instrument' => self::DEFAULT_INSTRUMENT,
                'datetime' => $dateString,
                'open' => $open,
                'high' => $high,
                'low' => $low,
                'average' => round(($high + $low) / 2),
                'close' => $close,
                'extrema' => null
            ];
            $price = $close;
        }

        return $prices;
    }
}
