<?php
namespace TinyApp\Model\Strategy;

trait DeviationTrait
{
    protected function getDeviation(array $lastPrices) : int
    {
        $fastAveragePeriod = 5;
        $slowAveragePeriod = 20;

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
                case $fastAveragePeriod - $counter > 0:
                    continue 2;
                case $fastAveragePeriod - $counter === 0:
                    $averages['fast'] = $sum / $counter;
                    break 1;
                case $slowAveragePeriod - $counter > 0:
                    continue 2;
                case $slowAveragePeriod - $counter === 0:
                    $averages['slow'] = $sum / $counter;
                    break 2;
            }
        }

        /* todo
         * if last block average price is between fast and slow averages that means local price is about to move in opposite direction to the previos local trend should be combined with long trend strategy
         */
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
