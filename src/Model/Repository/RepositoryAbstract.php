<?php
namespace TinyApp\Model\Repository;

use TinyApp\Model\Repository\RepositoryInterface;
use TinyApp\Model\Repository\DatabaseConnectionInterface;

abstract class RepositoryAbstract implements RepositoryInterface
{
    private $write;
    private $counter;

    public function __construct(DatabaseConnectionInterface $write)
    {
        $this->write = $write;
        $this->counter = 1;
    }

    public function getWrite() : DatabaseConnectionInterface
    {
        return $this->write;
    }

    public function getRead() : DatabaseConnectionInterface
    {
        return $this->write;
    }

    protected function getPages(string $sql, array $arguments, int $perPage) : int
    {
        if ($perPage < 1) {
            throw new \Exception('Need at least one per page');
        }

        $total = $this->write->fetch($sql, $arguments);
        if (!empty($total[0]['count'])) {
            $pages = $total[0]['count'] / $perPage;
            return (int)$pages < $pages ? $pages + 1 : $pages;
        }

        return 0;
    }

    protected function getInPlaceholdersIncludingParams(array $values, array &$params) : string
    {
        $placeholders = [];
        foreach ($values as $value) {
            $placeholder = ':value' . $this->counter;
            $placeholders[] = $placeholder;
            $params[ltrim($placeholder, ':')] = $value;
            $this->counter++;
        }

        return implode(', ', $placeholders);
    }
}
