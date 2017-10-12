<?php
namespace TinyApp\Model\Repository;

class DatabaseConnection
{
    private $connection;
    private $statement;

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

    public function prepare(string $sql)
    {
        $this->statement = $this->connection->prepare($sql);
    }

    public function fetch(string $sql = null, array $arguments = []) : array
    {
        $this->checkStatement($sql);
        $this->statement->execute($arguments);

        $return = [];
        while ($row = $stmt->fetch()) {
            $return[] = $row;
        }

        return $return;
    }

    public function execute(string $sql = null, $arguments)
    {
        $this->checkStatement($sql);
        $this->statement->execute($arguments);
        return $this->connection->lastInsertId();
    }

    private function checkStatement(string $sql = null)
    {
        if (!empty($sql)) {
            $this->statement = $this->connection->prepare($sql);
        }

        if (empty($this->statement)) {
            throw new \Exception('No statement prepared');
        }
//@TODO update this check
//        if (!($stmt instanceof pdostatement)) {
//            throw new \Exception('No statement prepared');
//        }
    }
}
