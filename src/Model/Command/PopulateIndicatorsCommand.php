<?php
namespace TinyApp\Model\Command;

use TinyApp\Model\Service\FetchingServiceInterface;
use TinyApp\Model\Command\CommandResult;

class PopulateIndicatorsCommand implements CommandInterface
{
    private $fetchingService;

    public function __construct(FetchingServiceInterface $fetchingService)
    {
        $this->fetchingService = $fetchingService;
    }

    public function execute() : CommandResult
    {
        if ($this->fetchingService->populateIndicators()) {
            return new CommandResult(true, 'successfully populated all indicators');
        } else {
            return new CommandResult(false, 'failed to populated all indicators');
        }
    }
}
