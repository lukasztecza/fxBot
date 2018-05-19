<?php
namespace TinyApp\Model\Command;

use TinyApp\Model\Service\LearningService;
use TinyApp\Model\Command\CommandResult;

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
