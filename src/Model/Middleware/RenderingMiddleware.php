<?php
namespace TinyApp\Model\Middleware;

use TinyApp\System\Request;
use TinyApp\System\Response;
use TinyApp\Model\Middleware\ApplicationMiddlewareInterface;

class RenderingMiddleware implements ApplicationMiddlewareInterface
{
    private $controller;
    private $action;

    public function __construct($controller, string $action)
    {
        $this->controller = $controller;
        $this->action = $action;
    }

    public function process(Request $request)
    {
        $controller = $this->controller;
        $action = $this->action;
        $response = $controller->$action($request);
        if (!($response instanceof Response)) {
            throw new \Exception('Controller has to return Response object');
        }

        $contentType = $response->headers()['Content-Type'] ?? null;
        switch($contentType) {
            case 'application/json':
                $this->renderJsonResponse($response);
                break;
            default:
                $this->renderHtmlResponse($response->variables());
                break;
        }
    }

    private function renderJsonResponse(Response $response)
    {
        header('Content-Type: application/json');
        echo json_encode($response->variables());
        exit;
    }

    private function renderHtmlResponse(array $variables)
    {
        $template = $variables['template'];
        unset($variables['template']);
        extract($variables);
        include(__DIR__ . '/../../View/' . $template);
    }
}
