<?php
namespace TinyApp\Model\Service;

use TinyApp\Model\Repository\ItemsRepository;

class ItemsService
{
    private $itemsRepository;

    public function __construct(ItemsRepository $itemsRepository) {
        $this->itemsRepository = $itemsRepository;
    }

    public function getItems() : array
    {
        return $this->itemsRepository->getItems();
    }

    public function getItem(int $id) : array
    {
        return $this->itemsRepository->getItem($id);
    }

    public function saveItem(array $item) : int
    {
        try {
            return $this->itemsRepository->saveItem($item);
        } catch (\Exception $e) {
            trigger_error('Failed to save item with message ' . $e->getMessage() . ' with payload ' . var_export($item, true), E_USER_NOTICE);
            return 0;
        }
    }

    public function saveItems(array $names) : array
    {
        try {
            $items = [];
            foreach ($names as $name) {
                $items[] = ['name' => $name];
            }

            return $this->itemsRepository->saveItems($items);
        } catch (\Exception $e) {
            trigger_error('Failed to save items with message ' . $e->getMessage() . ' with paylaod '  . var_export($names, true), E_USER_NOTICE);
            return [];
        }
    }

    public function updateItem(array $item) : int
    {
        try {
            return $this->itemsRepository->updateItem($item);
        } catch (\Exception $e) {
            trigger_error('Failed to update item with message ' . $e->getMessage() . ' with paylaod '  .  var_export($item, true), E_USER_NOTICE);
            return 0;
        }
    }

    public function deleteItem(int $id) : bool
    {
        try {
            return $this->itemsRepository->deleteItem($id);
        } catch (\Exception $e) {
            trigger_error('Failed to delete item with message ' . $e->getMessage() . ' with id ' . $id, E_USER_NOTICE);
            return false;
        }
    }
}
