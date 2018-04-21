<?php
namespace TinyApp\Model\Strategy;

trait AverageDeviationTrait
{
    protected function getAverageDeviationDirection(
        array $lastPrices,
        int $fast,
        int $slow,
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
        $averages = [
            'current' => ($lastPrices[0]['high'] + $lastPricesp[0]['low']) / 2,
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
                $averages['current'] < $averages['fast'] - $highLowDifferenceFactor * $lastLowHighDifference
            :
                return 1;
            case
                $averages['fast'] < $averages['slow'] &&
                $averages['current'] > $averages['fast'] + $highLowDifferenceFactor * $lastLowHighDifference
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
