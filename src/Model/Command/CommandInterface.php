<?php
namespace TinyApp\Model\Command;

use TinyApp\Model\Command\CommandResult;

interface CommandInterface
{
    public function execute() : CommandResult;
}
