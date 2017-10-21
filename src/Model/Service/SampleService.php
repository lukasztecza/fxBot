<?php
namespace TinyApp\Model\Service;

class SampleService
{
    private $userRepository;

    public function __construct($sampleRepository) {
        $this->sampleRepository = $sampleRepository;
    }

    public function getItems() : array
    {
        return $this->sampleRepository->getItems();
    }

    public function getItem(int $id) : array
    {
        return $this->sampleRepository->getItem($id);
    }

    public function saveItem(array $item) : int
    {
        try {
            return $this->sampleRepository->saveItem($item);
        } catch (\Exception $e) {
            error_log('Failed to save item with payload ' . var_export($item, true), E_USER_NOTICE);
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

            return $this->sampleRepository->saveItems($items);
        } catch (\Exception $e) {
            error_log('Failed to save items ' . var_export($names, true), E_USER_NOTICE);
            return [];
        }
    }

    public function updateItem(array $item) : int
    {
        try {
            return $this->sampleRepository->updateItem($item);
        } catch (\Exception $e) {
            error_log('Failed to update item with id ' . $id, E_USER_NOTICE);
            return 0;
        }
    }

    public function deleteItem(int $id) : bool
    {
        try {
            return $this->sampleRepository->deleteItem($id);
        } catch (\Exception $e) {
            error_log('Failed to delete item with id ' . $id, E_USER_NOTICE);
            return false;
        }
    }
}
