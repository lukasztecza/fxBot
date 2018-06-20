<?php declare(strict_types=1);
namespace FxBot\Model\Command;

use LightApp\Model\Command\CommandInterface;
use FxBot\Model\Service\LearningService;
use LightApp\Model\Command\CommandResult;

class LearningCommand implements CommandInterface
{
    private $learningService;

    public function __construct(LearningService $learningService)
    {
        $this->learningService = $learningService;
    }

    public function execute() : CommandResult
    {
        $learningResult = $this->learningService->learn();
        return new CommandResult($learningResult['status'], $learningResult['message']);
    }
}
