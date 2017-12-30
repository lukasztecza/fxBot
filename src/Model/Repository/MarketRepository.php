<?php
namespace TinyApp\Model\Repository;

class MarketRepository extends RepositoryAbstract
{
    public function getItems(int $page, int $perPage) : array
    {
        $items = $this->getRead()->fetch(
            'SELECT * FROM `items` LIMIT ' . ($page - 1) * $perPage . ', ' . $perPage
        );
        $pages = $this->getPages('SELECT COUNT(*) as count FROM `items`', [], $perPage);

        return ['items' => $items, 'page' => $page, 'pages' => $pages];
    }

    public function saveValues(array $values) : array
    {
        $this->getWrite()->begin();
        try {
            $this->getWrite()->prepare(
                'INSERT INTO `market` (`pack`, `instrument`, `datetime`, `open`, `high`, `low`, `average`, `close`, `extrema`)
                VALUES (:pack, :instrument, :datetime, :open, :high, :low, :average, :close, :extrema)'
            );
            $affectedIds = [];
            foreach ($values as $value) {
                $affectedIds[] = $this->getWrite()->execute(null, [
                    'pack' => $value['pack'],
                    'instrument' => $value['instrument'],
                    'datetime' => $value['datetime'],
                    'open' => $value['open'],
                    'high' => $value['high'],
                    'low' => $value['low'],
                    'average' => $value['average'],
                    'close' => $value['close'],
                    'extrema' => $value['extrema']
                ]);
            }
        } catch(\Throwable $e) {
            $this->getWrite()->rollBack();
            trigger_error(
                'Rolling back after failed attempt to save values with message ' . $e->getMessage() . ' with payload ' . var_export($values, true)
            );
            throw $e;
        }
        $this->getWrite()->commit();

        return $affectedIds;
    }

    public function getLatestRecord() : array
    {
        $records = $this->getRead()->fetch(
            'SELECT * FROM `market` ORDER BY `id` DESC LIMIT 1'
        );

        return !empty($records) ? array_pop($records) : [];
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
