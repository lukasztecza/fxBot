<?php
namespace FxBot\Model\Command;

use LightApp\Model\Command\CommandInterface;
use FxBot\Model\Service\PriceService;
use LightApp\Model\Command\CommandResult;

class PopulateRandomCommand implements CommandInterface
{
    private const DEFAULT_INSTRUMENT = 'USD_CAD';

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
        $price = 1000; // work on ints instead of floats
        $itertions = 500;
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
                'instrument' => self::DEFAULT_INSTRUMENT,
                'datetime' => $dateString,
                'open' => $open,
                'high' => $high,
                'low' => $low,
                'close' => $close,
            ];
            $price = $close;
        }

        return $prices;
    }
}
