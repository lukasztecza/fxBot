<?php
namespace TinyApp\Model\Service;

use TinyApp\Model\Repository\MarketRepository;

class MarketService
{
    private const PER_PAGE = 5;

    private $marketRepository;

    public function __construct(MarketRepository $marketRepository) {
        $this->marketRepository = $marketRepository;
    }

    public function getItems(int $page) : array
    {
        try {
            return $this->itemsRepository->getItems($page, self::PER_PAGE);
        } catch(\Throwable $e) {
            trigger_error('Failed to get items with message ' . $e->getMessage(), E_USER_NOTICE);

            return ['items' => [], 'page' => null, 'pages' => 0];
        }
    }

    public function saveValues(array $values) : array
    {
        try {
            $this->appendLocalExtremas($values);
            return $this->marketRepository->saveValues($values);
        } catch(\Throwable $e) {
            trigger_error('Failed to save items with message ' . $e->getMessage() . ' with paylaod ' . var_export($values, true), E_USER_NOTICE);

            return [];
        }
    }

    public function getLatestRecord() : array
    {
        try {
            return $this->marketRepository->getLatestRecord();
        } catch(\Throwable $e) {
            trigger_error('Failed to get latest market record date with message ' . $e->getMessage());

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

    public function deleteItems(array $ids) : bool
    {
        try {
            return $this->itemsRepository->deleteItems($ids);
        } catch(\Throwable $e) {
            trigger_error('Failed to delete items with message ' . $e->getMessage() . ' with ids ' . var_export($ids, true), E_USER_NOTICE);

            return false;
        }
    }

    public function getItem(int $id) : array
    {
        try {
            return $this->itemsRepository->getItem($id);
        } catch(\Throwable $e) {
            trigger_error('Failed to get item with message ' . $e->getMessage() . ' with id ' . var_export($id, true), E_USER_NOTICE);

            return [];
        }
    }

    public function saveItem(array $item) : int
    {
        try {
            return $this->itemsRepository->saveItem($item);
        } catch(\Throwable $e) {
            trigger_error('Failed to save item with message ' . $e->getMessage() . ' with payload ' . var_export($item, true), E_USER_NOTICE);

            return 0;
        }
    }

    public function updateItem(array $item) : int
    {
        try {
            return $this->itemsRepository->updateItem($item);
        } catch(\Throwable $e) {
            trigger_error('Failed to update item with message ' . $e->getMessage() . ' with paylaod '  .  var_export($item, true), E_USER_NOTICE);

            return 0;
        }
    }

    public function deleteItem(int $id) : bool
    {
        try {
            return $this->itemsRepository->deleteItem($id);
        } catch(\Throwable $e) {
            trigger_error('Failed to delete item with message ' . $e->getMessage() . ' with id ' . var_export($id, true), E_USER_NOTICE);

            return false;
        }
    }
}
