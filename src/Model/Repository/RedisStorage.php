<?php
namespace TinyApp\Model\Repository;

use Predis\Client;

class RedisStorage implements MemoryStorageInterface
{
    private $connection;
    private $maxTtl;

    public function __construct(string $scheme, string $host, string $port, string $password, int $maxTtl)
    {
        try {
            $this->connection = new Client([
                'scheme' => $scheme,
                'host' => $host,
                'port' => $port,
                'password' => $password
            ]);
        } catch (\Throwable $e) {
            throw new \Exception('Could not create redis connection with ' . $e->getMessage());
        }

        $this->maxTtl = $maxTtl;
    }

    public function set(string $key, string $value, int $ttl = null) : void
    {
        $this->connection->set($key, $value);
        $ttl = $ttl ?? $this->maxTtl;
        $this->connection->expire($key, $ttl);
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
