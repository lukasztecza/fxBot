<?php
namespace TinyApp\Model\Middleware;

use TinyApp\System\Request;
use TinyApp\System\Response;
use TinyApp\System\ApplicationMiddlewareInterface;

class RenderingMiddleware implements ApplicationMiddlewareInterface
{
    const CONTENT_TYPE_HTML = 'text/html';
    const CONTENT_TYPE_JSON = 'application/json';

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

        $headers = $response->getHeaders();

        $location = $headers['Location'] ?? null;
        if ($location) {
            $this->setHeaders($headers);
            exit;
        }

        //@TODO move content types to constants and add application/xml octet-string for download or display file
        $contentType = $headers['Content-Type'] ?? self::CONTENT_TYPE_HTML;
        $variables = $response->getVariables();

        switch($contentType) {
            case self::CONTENT_TYPE_HTML:
                $this->returnHtmlResponse($response->getTemplate(), $variables, $headers);
                exit;
            case self::CONTENT_TYPE_JSON:
                $this->returnJsonResponse($variables, $headers);
                exit;
            default:
                unset($headers['Content-Type']);
                $this->returnHtmlResponse($response->getTemplate(), $variables, $headers);
                exit;
        }
    }

    private function setHeaders(array $headers)
    {
        foreach ($headers as $key => $value) {
            header($key . ': ' . $value);
        }
    }

    private function returnJsonResponse(array $variables, array $headers)
    {
        $this->setHeaders($headers);
        echo json_encode($variables);
    }

    private function returnHtmlResponse(string $template, array $variables, array $headers)
    {
        $this->setHeaders($headers);
        $this->renderTemplate($template, $variables);
    }

    private function renderTemplate(string $template, array $variables)
    {
        if (empty($template) || !file_exists(__DIR__ . '/../../View/' . $template)) {
            throw new \Exception('Template does not exist ' . var_export($template, true));
        }
        extract($variables);
        include(__DIR__ . '/../../View/' . $template);
    }
}
