<?php
namespace FxBot\Model\Command;

use LightApp\Model\Command\CommandInterface;
use FxBot\Model\Service\SimulationService;
use LightApp\Model\Command\CommandResult;

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
