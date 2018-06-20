<?php declare(strict_types=1);
namespace FxBot\Model\Command;

use LightApp\Model\Command\CommandInterface;
use FxBot\Model\Service\TradeService;
use LightApp\Model\Command\CommandResult;

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
