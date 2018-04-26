<?php
namespace TinyApp\Model\Repository;

use Predis\Client;

class RedisStorage implements MemoryStorageInterface
{
    private $connection;

    public function __construct(string $scheme, string $host, string $port)
    {
        try {
            $this->connection = new Client([
                'scheme' => $scheme,
                'host'   => $host,
                'port'   => $port
            ]);
        } catch (\Throwable $e) {
            throw new \Exception('Could not create redis connection with ' . $e->getMessage());
        }
    }

    public function set(string $key, string $value) : void
    {
        $this->connection->set($key, $value);
    }

    public function get(string $key) : ?string
    {
        return $this->connection->get($key);
    }

    public function delete(string $key) : void
    {
        $this->connection->del($key);
    }
    public function flush() : void
    {
        $this->connection->flushall();
    }
}
