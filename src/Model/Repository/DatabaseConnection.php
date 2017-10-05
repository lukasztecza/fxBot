<?php
namespace TinyApp\Model\Repository;

class DatabaseConnection
{
    private $host;
    private $user;
    private $password;

    public function __construct($host, $user, $password)
    {
        $this->host = $host;
        $this->user = $user;
        $this->password = $password;
    }

    public function execute()
    {
        return 'hey';
    }
}
