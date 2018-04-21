<?php
namespace TinyApp\Model\Strategy;

trait AverageCrossingTrait
{
    protected function getAverageCrossingDirection(
        array $lastPrices,
        int $fast,
        int $slow
    ) : int {
        if (empty($lastPrices)) {
            return 0;
        }

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
                    break 1;
            }
        }

        switch (true) {
            case
                $averages['fast'] > $averages['slow'] &&
                $averages['current'] > $averages['slow'] &&
                $averages['current'] < $averages['fast']
            :
                return -1;
            case
                $averages['fast'] < $averages['slow'] &&
                $averages['current'] < $averages['slow'] &&
                $averages['current'] > $averages['fast']
            :
                return 1;
            default:
                return 0;
        }
    }
}
