<?php
namespace TinyApp\Model\Service;

class SampleService
{
    private $userRepository;

    public function __construct($sampleRepository) {
        $this->sampleRepository = $sampleRepository;
    }

    public function getItems()
    {
        return $this->sampleRepository->getItems();
    }

    public function getItem(int $key)
    {
        return $this->sampleRepository->getItem($key);
    }

    public function saveItems(array $items)
    {
        return $this->sampleRepository->saveItems($items);
    }
}
