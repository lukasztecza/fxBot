<?php
namespace TinyApp\Model\Command;

use TinyApp\Model\Service\SimulationService;
use TinyApp\Model\Command\CommandResult;

class SimulationCommand implements CommandInterface
{
    private $simulationService;

    public function __construct(SimulationService $simulationService)
    {
        $this->simulationService = $simulationService;
    }

    public function execute() : CommandResult
    {
        $simulationResult = $this->simulationService->run();
        return new CommandResult($simulationResult['status'], $simulationResult['message']);
    }
}
