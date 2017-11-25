<?php
namespace TinyApp\Model\Repository;

class ItemsRepository extends RepositoryAbstract
{
    public function getItems(int $page, int $perPage) : array
    {
        $items = $this->getRead()->fetch(
            'SELECT * FROM `items` LIMIT ' . ($page - 1) * $perPage . ', ' . $perPage
        );
        $pages = $this->getPages('SELECT COUNT(*) as count FROM `items`', [], $perPage);

        return ['items' => $items, 'page' => $page, 'pages' => $pages];
    }

    public function saveItems(array $items) : array
    {
        $this->getWrite()->begin();
        try {
            $this->getWrite()->prepare(
                'INSERT INTO `items`(`name`) VALUES (:name)'
            );
            $affectedIds = [];
            foreach ($items as $item) {
                $affectedIds[] = $this->getWrite()->execute(null, ['name' => $item['name']]);
            }
        } catch(\Exception $e) {
            $this->getWrite()->rollBack();
            trigger_error(
                'Rolling back after failed attempt to save items with message ' . $e->getMessage() . ' with payload ' . var_export($items, true)
            );
            throw $e;
        }
        $this->getWrite()->commit();

        return $affectedIds;
    }

    public function deleteItems(array $ids) : bool
    {
        $this->getWrite()->prepare('DELETE FROM `items` WHERE `id` = :id');
        foreach ($ids as $id) {
            $this->getWrite()->execute(null, ['id' => $id]);
        }
        $this->getWrite()->clean();

        return true;
    }

    public function getItem(int $id) : array
    {
        $items = $this->getRead()->fetch(
            'SELECT * FROM `items` WHERE `id` = :id',
            ['id' => $id]
        );

        return !empty($items) ? array_pop($items) : [];
    }

    public function saveItem(array $item) : int
    {
        $affectedId = $this->getWrite()->execute(
            'INSERT INTO `items` (`name`) VALUES (:name)', ['name' => $item['name']]
        );

        return (int)$affectedId;
    }

    public function updateItem(array $item) : int
    {
        $affectedId = $this->getWrite()->execute(
            'UPDATE `items` SET `name` = :name WHERE `id` = :id AND `id` = last_insert_id(`id`)', ['name' => $item['name'], 'id' => $item['id']]
        );

        return (int)$affectedId;
    }

    public function deleteItem(int $id) : bool
    {
        $this->getWrite()->execute(
            'DELETE FROM `items` WHERE `id` = :id', ['id' => $id]
        );

        return true;
    }
}
