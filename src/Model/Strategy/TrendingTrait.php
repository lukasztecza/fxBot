<?php
namespace TinyApp\Model\Strategy;

trait TrendingTrait
{
    protected function getTrend(array $lastPrices, int $extremumRange) : int
    {
        $this->appendLocalExtremas($lastPrices, $extremumRange);
        $lastHighs = [];
        $lastLows = [];
        foreach ($lastPrices as $price) {
            if (count($lastHighs) > 1 && count($lastLows) > 1) {
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

        if (count($lastHighs) > 1 && count($lastLows) > 1) {
            switch (true) {
                case $lastLows[0] > $lastLows[1] && $lastHighs[0] > $lastHighs[1]:
                    return 1;
                case $lastLows[0] < $lastLows[1] && $lastHighs[0] < $lastHighs[1]:
                    return -1;
            }
        }

        return 0;
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
