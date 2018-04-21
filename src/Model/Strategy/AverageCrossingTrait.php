<?php
namespace TinyApp\Model\Strategy;

trait AverageCrossingTrait
{
    protected function getAverageDirection(
        array $lastPrices,
        int $fast,
        int $slow
    ) : int {
        $averages = [
            'current' => ($price['high'] + $price['low']) / 2,
            'fast' => null,
            'slow' => null
        ];
        $sum = 0;
        $counter = 0;
        foreach ($lastPrices as $key => $price) {
            $sum += ($price['high'] + $price['low']) / 2;
            $counter++;
            switch (true) {
                case $fastAverage - $counter === 0:
                    $averages['fast'] = $sum / $counter;
                    break 1;
                case $slowAverage - $counter === 0:
                    $averages['slow'] = $sum / $counter;
                    break 1;
            }
        }

        switch (true) {
            case
                $averages['fast'] > $averages['slow'] &&
                $averages['current'] > $averages['slow'] &&
                $averages['current'] < $averages['fast'] &&
                $averages['current'] > $averages['signalFast'] &&
                $averages['current'] < $averages['signalSlow']
            :
                return 1;
            case
                $averages['fast'] < $averages['slow'] &&
                $averages['current'] < $averages['slow'] &&
                $averages['current'] > $averages['fast'] &&
                $averages['current'] < $averages['signalFast'] &&
                $averages['current'] > $averages['signalSlow']
            :
                return -1;
            default:
                return 0;
        }
    }
}
