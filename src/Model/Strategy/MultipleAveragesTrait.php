<?php
namespace TinyApp\Model\Strategy;

trait MultipleAveragesTrait
{
    protected function getAverageDirection(array $lastPrices, int $signalAverage, int $fastAverage, int $slowAverage) : int
    {
        $averages = [
            'current' => ($price['high'] + $price['low']) / 2,
            'signal' => null,
            'fast' => null,
            'slow' => null
        ];
        $sum = 0;
        $counter = 0;
        foreach ($lastPrices as $key => $price) {
            $sum += ($price['high'] + $price['low']) / 2;
            $counter++;
            switch (true) {
                case $signalAverage - $counter > 0:
                    continue 2;
                case $signalAverage - $counter === 0:
                    $averages['signal'] = $sum / $counter;
                    break 1;
                case $fastAverage - $counter > 0:
                    continue 2;
                case $fastAverage - $counter === 0:
                    $averages['fast'] = $sum / $counter;
                    break 1;
                case $slowAverage - $counter > 0:
                    continue 2;
                case $slowAverage - $counter === 0:
                    $averages['slow'] = $sum / $counter;
                    break 1;
            }
        }

        switch (true) {
            case $averages['current'] < $averages['signal'] && $averages['fast'] > $averages['slow']:
                return 1;
            case $averages['current'] > $averages['signal'] && $averages['fast'] < $averages['slow']:
                return -1;
            default:
                return 0;
        }
    }
}
