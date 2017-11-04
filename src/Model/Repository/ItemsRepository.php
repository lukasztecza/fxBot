<?php
namespace TinyApp\Model\Repository;

use TinyApp\Model\Repository\DatabaseConnection;

class ItemsRepository
{
    private $write;

    public function __construct(DatabaseConnection $write)
    {
        $this->write = $write;
    }

    public function getItems(int $page, int $perPage) : array
    {
        $items = $this->write->fetch(
            'SELECT * FROM `items` LIMIT ' . --$page * $perPage . ', ' . $perPage
        );

        return $items ?? [];
    }

    public function getPages(int $perPage) : int
    {
        if ($perPage < 1) {
            throw new \Exception('Need at least one per page');
        }

        $total = $this->write->fetch('SELECT COUNT(*) as count FROM `items`');
        if (!empty($total[0]['count'])) {
            $pages = $total[0]['count'] / $perPage;
            return (int)$pages < $pages ? $pages + 1 : $pages;
        }

        return 0;
    }

    public function saveItems(array $items) : array
    {
        $this->write->begin();
        try {
            $this->write->prepare(
                'INSERT INTO `items`(`name`) VALUES (:name)'
            );
            $affectedIds = [];
            foreach ($items as $item) {
                $affectedIds[] = $this->write->execute(null, ['name' => $item['name']]);
            }
        } catch(\Exception $e) {
            $this->write->rollBack();
            trigger_error(
                'Rolling back after failed attempt to save items with message ' . $e->getMessage() . ' with payload ' . var_export($items, true)
            );
            throw $e;
        }
        $this->write->commit();

        return $affectedIds;
    }

    public function deleteItems(array $ids) : bool
    {
        $this->write->prepare('DELETE FROM `items` WHERE `id` = :id');
        foreach ($ids as $id) {
            $this->write->execute(null, ['id' => $id]);
        }
        $this->write->clean();

        return true;
    }

    public function getItem(int $id) : array
    {
        $items = $this->write->fetch(
            'SELECT * FROM `items` WHERE `id` = :id',
            ['id' => $id]
        );

        return !empty($items) ? array_pop($items) : [];
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
            'DELETE FROM `items` WHERE `id` = :id', ['id' => $id]
        );

        return true;
    }
}
