<?php
namespace TinyApp\Model\Service;

use TinyApp\Model\Repository\IndicatorRepository;

class IndicatorService
{
    private $indicatorRepository;

    public function __construct(IndicatorRepository $indicatorRepository) {
        $this->indicatorRepository = $indicatorRepository;
    }

    public function saveIndicators(array $indicators) : array
    {
        try {
            return $this->indicatorRepository->saveIndicators($indicators);
        } catch(\Throwable $e) {
            trigger_error('Failed to save indicators with message ' . $e->getMessage() . ' with paylaod ' . var_export($indicators, true), E_USER_NOTICE);

            return [];
        }
    }

    public function getLatestIndicatorByPack(string $pack) : array
    {
        try {
            return $this->indicatorRepository->getLatestIndicatorByPack($pack);
        } catch(\Throwable $e) {
            trigger_error('Failed to get latest indicator with message ' . $e->getMessage());

            return [];
        }
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
