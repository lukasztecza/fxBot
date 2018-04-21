<?php
namespace TinyApp\Model\Strategy;

trait DeviationTrait
{
    protected function getDeviationDirection(array $lastPrices, int $fast, int $slow) : int
    {
        $averages = [
            'current' => ($lastPrices[0]['high'] + $lastPrices[0]['low']) / 2,
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
            case $averages['current'] < $averages['fast'] && $averages['current'] > $averages['slow']:
                return -1;
            case $averages['current'] > $averages['fast'] && $averages['current'] < $averages['slow']:
                return 1;
            default:
                return 0;
        }
    }
}
