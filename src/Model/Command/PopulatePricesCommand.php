<?php
namespace TinyApp\Model\Command;

use TinyApp\Model\Service\ForexService;
use TinyApp\Model\Command\CommandResult;

class PopulatePricesCommand implements CommandInterface
{
    private $forexService;

    public function __construct(ForexService $forexService)
    {
        $this->forexService = $forexService;
    }

    public function execute() : CommandResult
    {
        if ($this->forexService->populatePrices()) {
            return new CommandResult(true, 'successfully populated all prices');
        } else {
            return new CommandResult(false, 'failed to populate all prices');
        }
    }
}
