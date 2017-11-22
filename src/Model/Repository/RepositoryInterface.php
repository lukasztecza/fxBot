<?php
namespace TinyApp\Model\Repository;

use TinyApp\Model\Repository\DatabaseConnection;

interface RepositoryInterface
{
    public function getWrite() : DatabaseConnection;
    public function getRead() : DatabaseConnection;
}
