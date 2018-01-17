<?php
namespace TinyApp\Model\Command;

use TinyApp\Model\Service\TradeService;
use TinyApp\Model\Command\CommandResult;

class TradeCommand implements CommandInterface
{
    private $tradeService;

    public function __construct(TradeService $tradeService)
    {
        $this->tradeService = $tradeService;
    }

    public function execute() : CommandResult
    {
        $tradeResult = $this->tradeService->trade();
        return new CommandResult($tradeResult['status'], $tradeResult['message']);
    }
}
