<?php
namespace TinyApp\Model\Command;

use TinyApp\Model\Command\CommandResult;
use TinyApp\Model\Service\MarketService;

class PopulateRandomCommand implements CommandInterface
{
    private $marketService;

    public function __construct(MarketService $marketService)
    {
        $this->marketService = $marketService;
    }

    public function execute() : CommandResult
    {
        $values = $this->getRandomValues();
        $result = $this->marketService->saveValues($values);

        return new CommandResult(true, 'inserted');
    }

    private function getRandomValues() : array
    {
        $pack = 'test_' . md5(time() . rand(1,1000000));
        $price = 100000; // work on ints instead of float
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
            $values[] = [
                'datetime' => $dateString,
                'open' => $open,
                'high' => $high,
                'low' => $low,
                'average' => round(($high + $low) / 2),
                'close' => $close,
                'extrema' => null,
                'pack' => $pack
            ];
            $price = $close;
        }

        return $values;
    }
}
