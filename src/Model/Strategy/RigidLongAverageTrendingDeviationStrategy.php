<?php
namespace TinyApp\Model\Strategy;

use TinyApp\Model\Strategy\RigidStrategyAbstract;
use TinyApp\Model\Service\PriceService;

class RigidLongAverageTrendingDeviationStrategy extends RigidStrategyAbstract
{
    private const RIGID_STOP_LOSS = 0.0025;
    private const TAKE_PROFIT_MULTIPLIER = 5;
    private const INSTRUMENT = 'USD_CAD';
    //private const INSTRUMENT = 'EUR_CAD'; //also fine
    private const LONG_FAST_AVERAGE = 200;
    private const LONG_SLOW_AVERAGE = 400;
    private const EXTREMUM_RANGE = 12;
    private const SIGNAL_FAST_AVERAGE = 20;
    private const SIGNAL_SLOW_AVERAGE = 40;

    private $priceService;

    public function __construct(PriceService $priceService)
    {
        $this->priceService = $priceService;
        parent::__construct(self::RIGID_STOP_LOSS, self::TAKE_PROFIT_MULTIPLIER, self::INSTRUMENT);
    }

    protected function getDirection(string $currentDateTime = null, string $selectedInstrument = null) : int
    {
        $lastPrices = $this->priceService->getLastPricesByPeriod($selectedInstrument, 'P7D', $currentDateTime);
        $longAverageDirection = $this->getLongAverageDirection($lastPrices, self::LONG_FAST_AVERAGE, self::LONG_SLOW_AVERAGE, false);
        $trendingDirection = $this->getTrend($lastPrices, self::EXTREMUM_RANGE);
        $deviationDirection = $this->getDeviationDirection($lastPrices, self::SIGNAL_FAST_AVERAGE, self::SIGNAL_SLOW_AVERAGE);

        switch (true) {
            case $longAverageDirection === 1 && $trendingDirection === 1 && $deviationDirection === 1:
                return 1;
            case $longAverageDirection === -1 && $trendingDirection === -1 && $deviationDirection === -1:
                return -1;
            default:
                return 0;
        }
    }

    private function getLongAverageDirection(array $lastPrices, int $fast, int $slow, bool $followTrend) : int
    {
        $averages = $this->getAveragesByPeriods($lastPrices, $fast, $slow);
        switch (true) {
            case $averages['fast'] > $averages['slow']:
                return $followTrend ? 1 : -1;
            case $averages['fast'] < $averages['slow']:
                return $followTrend ? -1 : 1;
            default:
                return 0;
        }
    }

    private function getAveragesByPeriods(array $lastPrices, int $fast, int $slow) : array
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
                case $fast - $counter === 0:
                    $averages['fast'] = $sum / $counter;
                    break 1;
                case $slow - $counter === 0:
                    $averages['slow'] = $sum / $counter;
                    break 2;
            }
        }

        return $averages;
    }

    private function getTrend(array $lastPrices, int $extremumRange) : int
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

    private function appendLocalExtremas(array &$lastPrices, int $extremumRange) : void
    {
        foreach ($lastPrices as $key => $value) {
            $scoreMax = 0;
            $scoreMin = 0;
            for ($i = -$extremumRange; $i <= $extremumRange; $i++) {
                if (!isset($lastPrices[$key + $i])) {
                    continue 2;
                }
                if ($lastPrices[$key + $i]['high'] <= $value['high']) {
                    $scoreMax++;
                }
                if ($lastPrices[$key + $i]['low'] >= $value['low']) {
                    $scoreMin++;
                }
            }

            if ($scoreMax === 2 * $extremumRange + 1) {
                $lastPrices[$key]['extremum'] = 'max';
            }
            if ($scoreMin === 2 * $extremumRange + 1) {
                $lastPrices[$key]['extremum'] = 'min';
            }
        }
    }

    private function getDeviationDirection(array $lastPrices, int $fast, int $slow) : int
    {
        if (!isset($lastPrices[0]['high']) || !isset($lastPrices[0]['low'])) {
            return 0;
        }
        $averages = $this->getAveragesByPeriods($lastPrices, $fast, $slow);
        $averages['current'] = ($lastPrices[0]['high'] + $lastPrices[0]['low']) / 2;

        switch (true) {
            case $averages['current'] < $averages['fast'] && $averages['current'] > $averages['slow']:
                return -1;
            case $averages['current'] > $averages['fast'] && $averages['current'] < $averages['slow']:
                return 1;
            default:
                return 0;
        }
    }
}
