<?php
namespace FxBot\Model\Command;

use LightApp\Model\Command\CommandInterface;
use FxBot\Model\Service\FetchingServiceInterface;
use LightApp\Model\Command\CommandResult;

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
