<?php
namespace FxBot\Controller;

use LightApp\Controller\ControllerAbstract;
use FxBot\Model\Service\TradeService;
use LightApp\Model\System\Request;
use LightApp\Model\System\Response;

class ForexController extends ControllerAbstract
{
    private $tradeService;

    public function __construct(TradeService $tradeService)
    {
        $this->tradeService = $tradeService;
    }

    public function home(Request $request) : Response
    {
        return $this->htmlResponse('home.php');
    }

    public function stats(Request $request) : Response
    {
        $page = $request->getAttributes(['page'])['page'] ?? 1;
        $tradesPack = $this->tradeService->getTrades($page);

        return $this->htmlResponse(
            'stats.php',
            [
                'trades' => $tradesPack['trades'], 'page' => $tradesPack['page'], 'pages' => $tradesPack['pages'],
            ],
            ['trades' => 'html']
        );
    }
}
