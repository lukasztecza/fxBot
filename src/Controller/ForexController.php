<?php declare(strict_types=1);
namespace FxBot\Controller;

use LightApp\Controller\ControllerAbstract;
use FxBot\Model\Service\TradeService;
use FxBot\Model\Service\IndicatorService;
use LightApp\Model\Validator\ValidatorFactory;
use LightApp\Model\System\Request;
use LightApp\Model\System\Response;
use FxBot\Model\Validator\CompareValidator;

class ForexController extends ControllerAbstract
{
    private $tradeService;
    private $indicatorService;
    private $validatorFactory;
    private $instruments;

    public function __construct(TradeService $tradeService, IndicatorService $indicatorService, ValidatorFactory $validatorFactory, array $instruments)
    {
        $this->tradeService = $tradeService;
        $this->indicatorService = $indicatorService;
        $this->validatorFactory = $validatorFactory;
        $this->instruments = $instruments;
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

    public function compare(Request $request) : Response
    {
        $validator = $this->validatorFactory->create(
            CompareValidator::class, [
                'validTypes' => $this->indicatorService->getAllIndicators(),
                'validInstruments' => $this->instruments
            ]
        );
        if ($request->getMethod() === 'POST') {
            if ($validator->check($request)) {
                $comparison = $this->indicatorService->getComparison(
                    $request->getPayload(['type'])['type'],
                    $request->getPayload(['instrument'])['instrument']
                );

                return $this->htmlResponse(
                    '/compare/graph.php',
                    ['comparison' => $comparison, 'type' => $request->getPayload(['type'])['type']],
                    ['comparison' => 'html']
                );
            }
        }

        return $this->htmlResponse(
            '/compare/form.php',
            ['error' => $error ?? $validator->getError(), 'csrfToken' => $validator->getCsrfToken()],
            ['error' => 'html']
        );
    }
}
