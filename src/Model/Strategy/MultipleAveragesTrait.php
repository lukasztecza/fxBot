<?php
namespace TinyApp\Model\Strategy;

trait MultipleAveragesTrait
{
    protected function getAverageDirection(
        array $lastPrices,
        int $signalFastAverage,
        int $signalSlowAverage,
        int $fastAverage,
        int $slowAverage
    ) : int {
        $averages = [
            'current' => ($lastPrices[0]['high'] + $lastPrices[0]['low']) / 2,
            'signalFast' => null,
            'signalSlow' => null,
            'fast' => null,
            'slow' => null
        ];
        $sum = 0;
        $counter = 0;
        foreach ($lastPrices as $key => $price) {
            $sum += ($price['high'] + $price['low']) / 2;
            $counter++;
            switch (true) {
                case $signalFastAverage - $counter === 0:
                    $averages['signalFast'] = $sum / $counter;
                    break 1;
                case $signalSlowAverage - $counter === 0:
                    $averages['signalSlow'] = $sum / $counter;
                    break 1;
                case $fastAverage - $counter === 0:
                    $averages['fast'] = $sum / $counter;
                    break 1;
                case $slowAverage - $counter === 0:
                    $averages['slow'] = $sum / $counter;
                    break 1;
            }
        }
//@TODO update this thing
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
