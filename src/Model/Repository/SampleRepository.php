<?php
namespace TinyApp\Model\Repository;

class SampleRepository
{
    private $write;

    public function __construct($write)
    {
        $this->write = $write;
    }

    public function getItem(int $id) : array
    {
        $items = $this->write->fetch(
            'SELECT * FROM `items` WHERE `id` = :id',
            ['id' => $id]
        );

        return !empty($items) ? array_pop($items) : [];
    }

    public function getItems() : array
    {
        $items = $this->write->fetch(
            'SELECT * FROM `items`'
        );

        return $items ?? [];
    }

    public function saveItems(array $items) : array
    {
        $this->write->prepare(
            'INSERT INTO `items`(`name`) VALUES (:name)'
        );
        $affectedIds = [];
        foreach ($items as $item) {
            $affectedIds[] = $this->write->execute(null, ['name' => $item['name']]);
        }

        return $affectedIds;
    }

    public function saveItem(array $item) : int
    {
        $affectedId = $this->write->execute(
            'INSERT INTO `items` (`name`) VALUES (:name)', ['name' => $item['name']]
        );

        return (int)$affectedId;
    }

    public function updateItem(array $item) : int
    {
        $affectedId = $this->write->execute(
            'UPDATE `items` SET `name` = :name WHERE `id` = :id AND `id` = last_insert_id(`id`)', ['name' => $item['name'], 'id' => $item['id']]
        );

        return (int)$affectedId;
    }

    public function deleteItem(int $id) : bool
    {
        $this->write->execute(
            'DELETE FROM `items` WHERE `id` = :id AND `id`', ['id' => $id]
        );

        return true;
    }
}
