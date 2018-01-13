<?php
namespace TinyApp\Model\Command;

use TinyApp\Model\Service\FetchingServiceInterface;
use TinyApp\Model\Command\CommandResult;

class PopulatePricesCommand implements CommandInterface
{
    private $fetchingService;

    public function __construct(FetchingServiceInterface $fetchingService)
    {
        $this->fetchingService = $fetchingService;
    }

    public function execute() : CommandResult
    {
        if ($this->fetchingService->populatePrices()) {
            return new CommandResult(true, 'successfully populated all prices');
        } else {
            return new CommandResult(false, 'failed to populate all prices');
        }
    }
}
