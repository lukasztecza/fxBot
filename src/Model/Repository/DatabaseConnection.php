<?php
namespace TinyApp\Model\Repository;

class DatabaseConnection
{
    private $connection;

    public function __construct($engine, $host, $database, $user, $password)
    {
        return;
        $this->connection = new \PDO(
            $engine . ':host=' . $host . ';dbname=' . $database . ';charset=utf8',
            $user,
            $password
        );
        $this->connection->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
        $this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    public function getConnection()
    {
        return $this->connection;
    }
}
