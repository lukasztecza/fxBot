<?php
namespace TinyApp\Model\Repository;

class SampleRepository
{
    const STORAGE_PATH = __DIR__ . '/Storage/';

    private $write;

    public function __construct($write)
    {
        $this->write = $write;
    }

    public function getItem(int $key)
    {
        /*
        $items = $this->write->fetch(
            'SELECT * FROM `items` WHERE `id` = :id',
            ['id' => $key]
        );
        return array_pop($items);
        */
        $items = file_get_contents(self::STORAGE_PATH . 'items.json');
        $items = json_decode($items, true);
        return $items[$key];
    }

    public function getItems()
    {
        /*
        $items = $this->write->fetch(
            'SELECT * FROM `items`
        );
        */
        $items = file_get_contents(self::STORAGE_PATH . 'items.json');
        return json_decode($items, true);
    }

    public function saveItems(array $items)
    {
        /*
        $this->write->prepare(
            'INSERT INTO `items`(`text`) VALUES (:text)
        );
        foreach ($items as $item) {
            null,
            $lastId = $this->execute(['text' => $item]);
        }
        return $lastId;
        */
        $itemsStored = file_get_contents(self::STORAGE_PATH . 'items.json');
        $itemsStored = json_decode($itemsStored, true);
        foreach ($items as $item) {
            $itemsStored[] = $item;
        }
//        file_put_contents();
   //     $keys = array_keys($this->samples);
   //     return array_pop($keys);
    }
}
