<?php
namespace TinyApp\Model\Strategy;

trait IndicatorTrait
{
    protected function getInstrumentScores(array $lastIndicators, array $instruments, array $typeGroupFactors) : array
    {
        $typeValues = [];
        foreach ($lastIndicators as $index => $values) {
            if (
                !empty($values['type']) &&
                in_array($values['instrument'], $instruments) &&
                !isset($typeValues[$values['type']][$values['instrument']][1])
            ) {
                $typeValues[$values['type']][$values['instrument']]['actual'][] = $values['actual'];
                $typeValues[$values['type']][$values['instrument']]['forecast'][] = $values['forecast'];
            }
        }

        $valuesMap = [];
        foreach ($typeValues as $type => $instrumentValues) {
            foreach ($instrumentValues as $instrument => $values) {
                $valuesMap[$type]['absolute'][$instrument] = $values['actual'][0];
                $valuesMap[$type]['relative'][$instrument] = $values['actual'][0] - $values['actual'][1];
                $valuesMap[$type]['expectations'][$instrument] = $values['actual'][0] - $values['forecast'][0];
            }
        }

        foreach ($valuesMap as $type => $indicatorGroup) {
            foreach ($valuesMap[$type] as $groupName => $values) {
                asort($valuesMap[$type][$groupName]);
            }
        }

        $scores = [];
        foreach ($instruments as $instrument) {
            $scores[$instrument] = 0;
        }
        foreach ($valuesMap as $type => $indicatorGroup) {
            foreach ($indicatorGroup as $groupName => $values) {
                $counter = 0;
                foreach ($values as $instrument => $value) {
                    $counter++;
                    if (array_key_exists($type, $typeGroupFactors)) {
                        $scores[$instrument] += $counter * $typeGroupFactors[$type][$groupName];
                    } else {
                        $scores[$instrument] += $counter * 0;
                    }
                }
            }
        }

        return $scores;
    }
}
