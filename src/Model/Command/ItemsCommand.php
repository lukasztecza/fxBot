<?php
namespace TinyApp\Model\Command;

use TinyApp\Model\Command\CommandResult;
use TinyApp\Model\Repository\ItemsRepository;

class ItemsCommand implements CommandInterface
{
    private $itemsRepository;
    private $commandFixtures;

    public function __construct($itemsRepository, array $commandFixtures)
    {
        $this->itemsRepository = $itemsRepository;
        $this->commandFixtures = $commandFixtures;
    }

    public function execute() : CommandResult
    {
        $items = [];
        foreach ($this->commandFixtures as $item) {
            $items[] = ['name' => $item];
        }

        $result = $this->itemsRepository->saveItems($items);

        return new CommandResult(true, var_export($result, true));
    }
}
