<?php
namespace TinyApp\Model\Repository;

use TinyApp\Model\Repository\DatabaseConnectionInterface;

class PdoDatabaseConnection implements DatabaseConnectionInterface
{
    private $connection;
    private $statement;

    public function __construct(string $engine, string $host, string $database, string $user, string $password)
    {
        try {
            $this->connection = new \PDO(
                $engine . ':host=' . $host . ';dbname=' . $database . ';charset=utf8',
                $user,
                $password
            );
            $this->connection->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
            $this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch (\Throwable $e) {
            throw new \Excpetion('Could not create pod connection');
        }
    }

    public function prepare(string $sql) : void
    {
        $this->statement = $this->connection->prepare($sql);
    }

    public function clean() : void
    {
        $this->statement = null;
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

        if (empty($this->statement) || !($this->statement instanceof \PDOStatement)) {
            throw new \Exception('No statement prepared');
        }
    }
}
