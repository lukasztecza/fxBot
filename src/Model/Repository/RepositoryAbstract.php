<?php
namespace TinyApp\Model\Repository;

use TinyApp\Model\Repository\RepositoryInterface;
use TinyApp\Model\Repository\DatabaseConnection;

abstract class RepositoryAbstract implements RepositoryInterface
{
    private $write;

    public function __construct(DatabaseConnection $write)
    {
        $this->write = $write;
    }

    public function getWrite() : DatabaseConnection
    {
        return $this->write;
    }

    public function getRead() : DatabaseConnection
    {
        return $this->write;
    }

    public function getPages(string $sql, array $arguments, int $perPage) : int
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
}
