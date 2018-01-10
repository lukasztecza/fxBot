<?php
namespace TinyApp\Model\Command;

use TinyApp\Model\Service\ForexService;
use TinyApp\Model\Command\CommandResult;

class PopulateIndicatorsCommand implements CommandInterface
{
    private $forexService;

    public function __construct(ForexService $forexService)
    {
        $this->forexService = $forexService;
    }

    public function execute() : CommandResult
    {
        if ($this->forexService->populateIndicators()) {
            return new CommandResult(true, 'successfully populated all indicators');
        } else {
            return new CommandResult(false, 'failed to populated all indicators');
        }
    }
}
