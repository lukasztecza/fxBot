<?php
namespace TinyApp\Model\Strategy;

trait LongAverageTrait
{
    protected function getLongAverageDirection(array $lastPrices, int $fast, int $slow) : int
    {
        $averages = [
            'fast' => null,
            'slow' => null
        ];
        $sum = 0;
        $counter = 0;
        foreach ($lastPrices as $key => $price) {
            $sum += ($price['high'] + $price['low']) / 2;
            $counter++;
            switch (true) {
                case $fast - $counter === 0:
                    $averages['fast'] = $sum / $counter;
                    break 1;
                case $slow - $counter === 0:
                    $averages['slow'] = $sum / $counter;
                    break 2;
            }
        }

        switch (true) {
            case $averages['fast'] > $averages['slow']:
                return 1;
            case $averages['fast'] < $averages['slow']:
                return -1;
            default:
                return 0;
        }
    }
}
