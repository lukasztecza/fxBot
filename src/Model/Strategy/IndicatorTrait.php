<?php
namespace TinyApp\Model\Strategy;

trait IndicatorTrait
{
    protected function getInstrumentScores(
        array $lastIndicators,
        array $instruments,
        float $bankFactor,
        float $inflationFactor,
        float $tradeFactor,
        float $companiesFactor,
        float $salesFactor,
        float $unemploymentFactor,
        float $bankRelativeFactor
    ) : array {
        $typeValues = [];
        $instrumentScores = [];
        foreach ($lastIndicators as $index => $values) {
            if (
                !empty($values['type']) &&
                in_array($values['instrument'], $instruments) &&
                !isset($typeValues[$values['type']][$values['instrument']]['actual'][1])
            ) {
                $instrumentScores[$values['instrument']] = 0;
                $typeValues[$values['type']][$values['instrument']]['actual'][] = $values['actual'];
                $typeValues[$values['type']][$values['instrument']]['forecast'][] = $values['forecast'];
            }
        }

        $bankRates = [];
        foreach ($typeValues as $type => $instrumentValues) {
            foreach ($instrumentValues as $instrument => $values) {
                if (!isset($values['actual'][0]) || !isset($values['actual'][1])) {
                    continue;
                }

                if (
                    ($type === 'unemployment' && $values['actual'][0] < $values['actual'][1]) ||
                    ($type !== 'unemployment' && $values['actual'][0] > $values['actual'][1])
                ) {
                    $factorName = $type . 'Factor';
                    if (isset($instrumentScores[$instrument])) {
                        $instrumentScores[$instrument] = $instrumentScores[$instrument] + $$factorName;
                    } else {
                        $instrumentScores[$instrument] = $$factorName;
                    }
                }
            }
        }

        asort($bankRates);
        $counter = 0;
        foreach ($bankRates as $instrument => $value) {
            $instrumentScores[$instrument] = isset($instrumentScores[$instrument]) ? $instrumentScores[$instrument] + $counter : $counter;
            $counter = $counter + $bankRelativeFactor;
        }
        asort($instrumentScores);

        return $instrumentScores;
    }
}
