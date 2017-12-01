<?php
namespace TinyApp\Model\Command;

class CommandResult
{
    private $status;
    private $message;

    public function __construct(bool $status, string $message)
    {
        $this->status = $status;
        $this->message = $message;
    }

    public function getStatus() : bool
    {
        return $this->status;
    }

    public function getMessage() : string
    {
        return $this->message;
    }
}
