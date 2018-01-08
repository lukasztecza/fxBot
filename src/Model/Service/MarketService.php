<?php
namespace TinyApp\Model\Service;

use TinyApp\Model\Repository\MarketRepository;

class MarketService
{
    private $marketRepository;

    public function __construct(MarketRepository $marketRepository) {
        $this->marketRepository = $marketRepository;
    }

    public function savePrices(array $prices) : array
    {
        try {
            $this->appendLocalExtremas($prices);
            return $this->marketRepository->savePrices($prices);
        } catch(\Throwable $e) {
            trigger_error('Failed to save prices with message ' . $e->getMessage() . ' with paylaod ' . var_export($prices, true), E_USER_NOTICE);

            return [];
        }
    }

    public function getLatestPriceByInstrumentAndPack(string $instrument, string $pack) : array
    {
        try {
            return $this->marketRepository->getLatestPriceByInstrumentAndPack($instrument, $pack);
        } catch(\Throwable $e) {
            trigger_error('Failed to get latest price with message ' . $e->getMessage());

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

    public function deletePriceById(int $id) : bool
    {
        try {
            return $this->marketRepository->deletePriceById($id);
        } catch(\Throwable $e) {
            trigger_error('Failed to delete price with message ' . $e->getMessage() . ' with id ' . var_export($id, true), E_USER_NOTICE);

            return false;
        }
    }
}
