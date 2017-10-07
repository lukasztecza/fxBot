<?php
namespace TinyApp\Model\Middleware;

use TinyApp\System\Request;
use TinyApp\System\Response;
use TinyApp\System\ApplicationMiddlewareInterface;

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
            throw new \Exception('Controller has to return Response object, returned ' . var_export($response, true));
        }

        $contentType = $response->headers()['Content-Type'] ?? null;
        $location = $response->headers()['Location'] ?? null;

        if ($location) {
            $this->setHeaders($response->headers());
            exit;
        }

        switch($contentType) {
            case 'application/json':
                $this->returnJsonResponse($response);
                break;
            default:
                $this->returnHtmlResponse($response);
                break;
        }
    }

    private function setHeaders(array $headers)
    {
        foreach ($headers as $key => $value) {
            header($key . ': ' . $value);
        }
    }

    private function returnJsonResponse(Response $response)
    {
        $this->setHeaders($response->headers());
        echo json_encode($response->variables());
        exit;
    }

    private function returnHtmlResponse(Response $response)
    {
        $template = $response->variables()['template'] ?? null;
        $variables = $response->variables();
        if (!$template) {
            throw new \Exception('Template not specified in response variables ' . var_export($variables, true));
        }
        $this->setHeaders($response->headers());
        $this->renderTemplate($variables);
        exit;
    }

    private function renderTemplate($variables)
    {
        extract($variables);
        include(__DIR__ . '/../../View/' . $template);
    }
}
