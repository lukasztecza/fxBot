<?php
namespace TinyApp\Model\Strategy;

trait AverageDeviationTrait
{
    protected function getAverageDirection(
        array $lastPrices,
        int $signalFastAverage,
        int $signalSlowAverage,
        int $fastAverage,
        int $slowAverage,
        int $highLowDifferenceFactor
    ) : int {
        $this->appendLocalExtremas($lastPrices, $extremumRange);
        $lastHighs = [];
        $lastLows = [];
        foreach ($lastPrices as $price) {
            if (count($lastHighs) > 0 && count($lastLows) > 0) {
                break;
            }

            if (isset($price['extremum'])) {
                if ($price['extremum'] === 'max') {
                    $lastHighs[] = $price['high'];
                } elseif ($price['extremum'] === 'min') {
                    $lastLows[] = $price['low'];
                }
            }
        }

        if (count($lastHighs) < 1 && count($lastLows) < 1) {
            return 0;
        }
        $lastLowHighDifference = $lastHighs[0] - $lastLows[0];
//@todo use it to trigger entry points
        $averages = [
            'current' => ($price['high'] + $price['low']) / 2,
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

        switch (true) {
            case
                $averages['fast'] > $averages['slow'] &&
                $averages['current'] < $averages['fast'] - $highLowDifferenceFactor * $lastLowHighDifference &&
                $averages['current'] > $averages['signalFast'] &&
                $averages['current'] < $averages['signalSlow']
            :
                return 1;
            case
                $averages['fast'] < $averages['slow'] &&
                $averages['current'] > $averages['fast'] + $highLowDifferenceFactor * $lastLowHighDifference &&
                $averages['current'] < $averages['signalFast'] &&
                $averages['current'] > $averages['signalSlow']
            :
                return -1;
            default:
                return 0;
        }
    }

    private function appendLocalExtremas(array &$values, int $extremumRange) : void
    {
        foreach ($values as $key => $value) {
            $scoreMax = 0;
            $scoreMin = 0;
            for ($i = -$extremumRange; $i <= $extremumRange; $i++) {
                // not enough adjoining data
                if (!isset($values[$key + $i])) {
                    continue 2;
                }
                // local max
                if ($values[$key + $i]['high'] <= $value['high']) {
                    $scoreMax++;
                }
                // local min
                if ($values[$key + $i]['low'] >= $value['low']) {
                    $scoreMin++;
                }
            }

            // mark edge values
            if ($scoreMax === 2 * $extremumRange + 1) {
                $values[$key]['extremum'] = 'max';
            }
            if ($scoreMin === 2 * $extremumRange + 1) {
                $values[$key]['extremum'] = 'min';
            }
        }
    }
}
