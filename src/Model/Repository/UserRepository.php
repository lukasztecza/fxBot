<?php
namespace TinyApp\Model\Repository;

class UserRepository
{
    private $read;
    private $write;

    public function __construct($read, $write)
    {
        $this->read = $read;
        $this->write = $write;
    }

    public function getUser()
    {
        $this->read->getConnection();
    }
}
