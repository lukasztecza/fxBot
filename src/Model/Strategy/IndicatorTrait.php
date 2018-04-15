<?php
namespace TinyApp\Model\Strategy;

trait IndicatorTrait
{
    protected function getInstrumentScores(
        array $lastIndicators,
        array $instruments,
        float $actualFactors,
        float $forecastFactor,
        float $bankFactor
    ) : array
    {
        $typeValues = [];
        foreach ($lastIndicators as $index => $values) {
            if (
                !empty($values['type']) &&
                in_array($values['instrument'], $instruments) &&
                !isset($typeValues[$values['type']][$values['instrument']]['actual'][1])
            ) {
                $typeValues[$values['type']][$values['instrument']]['actual'][] = $values['actual'];
                $typeValues[$values['type']][$values['instrument']]['forecast'][] = $values['forecast'];
            }
        }

        $instrumentScores = [];
        $bankRates = [];
        foreach ($typeValues as $type => $instrumentValues) {
            foreach ($instrumentValues as $instrument => $values) {
                if (
                    ($type === 'unemployment' && $values['actual'][0] < $values['actual'][1]) ||
                    ($type !== 'unemployment' && $values['actual'][0] > $values['actual'][1])
                ) {
                    if (isset($instrumentScores[$instrument])) {
                        $instrumentScores[$instrument] = $instrumentScores[$instrument] + $actualFactors;
                    } else {
                        $instrumentScores[$instrument] = $actualFactors;
                    }
                }

                if (
                    ($type === 'unemployment' && $values['actual'][0] < $values['forecast'][0]) ||
                    ($type !== 'unemployment' && $values['actual'][0] > $values['forecast'][0])
                ) {
                    if (isset($instrumentScores[$instrument])) {
                        $instrumentScores[$instrument] = $instrumentScores[$instrument] + $forecastFactor;
                    } else {
                        $instrumentScores[$instrument] = $forecastFactor;
                    }
                }

                if (
                    ($type === 'bank')
                ) {
                    $bankRates[$instrument] = $values['actual'][0];
                }
            }
        }
        asort($bankRates);
        $counter = 0;
        foreach ($bankRates as $instrument => $value) {
            $instrumentScores[$instrument] = isset($instrumentScores[$instrument]) ? $instrumentScores[$instrument] + $counter : $counter;
            $counter = $counter + $bankFactor;
        }
        asort($instrumentScores);

        return $instrumentScores;
    }
}
