<?php
namespace TinyApp\Model\Strategy;

trait TrendingTrait
{
    protected function getTrend(array $lastPrices) : int
    {
        $this->appendLocalExtremas($lastPrices);
        $lastHighs = [];
        $lastLows = [];
        foreach ($lastPrices as $price) {
            if (count($lastHighs) > 1 && count($lastLows) > 1) {
                break;
            }

            if (isset($price['extrema'])) {
                if ($price['extrema'] === 'max') {
                    $lastHighs[] = $price['high'];
                } elseif ($price['extrema'] === 'min') {
                    $lastLows[] = $price['low'];
                }
            }
        }

        if (count($lastHighs) > 1 && count($lastLows) > 1) {
            $lowsDiff = $lastLows[0] - $lastLows[1];
            $highesDiff = $lastHighs[0] - $lastHighs[1];

            if ($lastLows[0] > $lastLows[1] && $lastHighs[0] < $lastHighs[1]) {
                return $lowsDiff > abs($highesDiff) ? 1 : -1;
            } elseif ($lastLows[0] > $lastLows[1]) {
                return 1;
            } elseif ($lastHighs[0] < $lastHighs[1]) {
                return -1;
            }
        }

        return 0;
    }

    private function appendLocalExtremas(array &$values) : void
    {
        $range = 10;
        foreach ($values as $key => $value) {
            $scoreMax = 0;
            $scoreMin = 0;
            for ($i = -$range; $i <= $range; $i++) {
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
            if ($scoreMax === 2 * $range + 1) {
                $values[$key]['extrema'] = 'max';
            }
            if ($scoreMin === 2 * $range + 1) {
                $values[$key]['extrema'] = 'min';
            }
        }
    }
}
