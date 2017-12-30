<?php
namespace TinyApp\Model\Repository;

class DatabaseConnection
{
    private $connection;
    private $statement;

    public function __construct($engine, $host, $database, $user, $password)
    {
        $this->connection = new \PDO(
            $engine . ':host=' . $host . ';dbname=' . $database . ';charset=utf8',
            $user,
            $password
        );
        $this->connection->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
        $this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    public function prepare(string $sql) : void
    {
        $this->statement = $this->connection->prepare($sql);
    }

    public function fetch(string $sql = null, array $arguments = []) : array
    {
        $this->checkStatement($sql);
        $this->statement->execute($arguments);

        $return = [];
        while ($row = $this->statement->fetch(\PDO::FETCH_ASSOC)) {
            $return[] = $row;
        }

        return $return;
    }

    public function execute(string $sql = null, $arguments) : string
    {
        $this->checkStatement($sql);
        $this->statement->execute($arguments);
        return $this->connection->lastInsertId();
    }

    public function clean() : void
    {
        $this->statement = null;
    }

    public function begin() : void
    {
        $this->connection->beginTransaction();
    }

    public function commit() : void
    {
        $this->connection->commit();
    }

    public function rollBack() : void
    {
        $this->connection->rollBack();
    }

    private function checkStatement(string $sql = null) : void
    {
        if (!empty($sql)) {
            $this->statement = $this->connection->prepare($sql);
        }

        if (empty($this->statement)) {
            throw new \Exception('No statement prepared');
        }

        if (!($this->statement instanceof \PDOStatement)) {
            throw new \Exception('No statement prepared');
        }
    }
}
