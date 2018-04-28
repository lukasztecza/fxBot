<?php
namespace TinyApp\Model\Repository;

class MemcachedStorage implements MemoryStorageInterface
{
    private $connection;
    private $maxTtl;

    public function __construct(string $host, string $port, int $maxTtl)
    {
        try {
            $this->connection = new \Memcached();
            $this->connection->addServer($host, $port);
        } catch (\Throwable $e) {
            throw new \Exception('Could not create memcached connection with ' . $e->getMessage());
        }

        $this->maxTtl = $maxTtl;
    }

    public function set(string $key, string $value, int $ttl = null) : void
    {
        $ttl = $ttl ?? $this->maxTtl;
        $this->connection->set($key, $value, $ttl);
    }

    public function get(string $key) : ?string
    {
        return $this->connection->get($key);
    }

    public function delete(string $key) : void
    {
        $this->connection->delete($key);
    }

    public function flush() : void
    {
        $this->connection->flush();
    }
}
