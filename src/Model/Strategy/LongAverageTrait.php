<?php
namespace TinyApp\Model\Strategy;

trait LongAverageTrait
{
    protected function getAverageMovement(array $lastPrices, int $longAverageFast, int $longAverageSlow) : int
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
                case $longAverageFast - $counter > 0:
                    continue 2;
                case $longAverageFast - $counter === 0:
                    $averages['fast'] = $sum / $counter;
                    break 1;
                case $longAverageSlow - $counter > 0:
                    continue 2;
                case $longAverageSlow - $counter === 0:
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
