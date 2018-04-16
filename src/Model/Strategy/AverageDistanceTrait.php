<?php
namespace TinyApp\Model\Strategy;

trait AverageDistanceTrait
{
    protected function getAverageDistance(array $lastPrices, int $averageDistancePeriod, float $averageDistanceFactor) : int
    {
        $values = [
            'current' => ($lastPrices[0]['high'] + $lastPrices[0]['low']) / 2,
            'average' => null,
            'highLow' => null
        ];
        $sum = 0;
        $highLows = 0;
        $counter = 0;
        foreach ($lastPrices as $key => $price) {
            $sum += ($price['high'] + $price['low']) / 2;
            $highLows += abs(($price['high'] - $price['low']));
            $counter++;
            switch (true) {
                case $averageDistancePeriod - $counter > 0:
                    continue 2;
                case $averageDistancePeriod - $counter === 0:
                    $values['average'] = $sum / $counter;
                    $values['highLow'] = $highLows / $counter;
                    break 2;
            }
        }

        switch (true) {
            case $values['current'] < $values['average'] - ($values['highLow'] * $averageDistanceFactor):
                return 1;
            case $values['current'] > $values['average'] + ($values['highLow'] * $averageDistanceFactor):
                return -1;
            default:
                return 0;
        }
    }
}
