<?php
namespace TinyApp\Model\Repository;

use TinyApp\Model\Repository\DatabaseConnectionInterface;

interface RepositoryInterface
{
    public function getWrite() : DatabaseConnectionInterface;
    public function getRead() : DatabaseConnectionInterface;
}
