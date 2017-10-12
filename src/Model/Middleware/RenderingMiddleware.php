<?php
namespace TinyApp\Model\Middleware;

use TinyApp\Model\System\Request;
use TinyApp\Model\System\Response;
use TinyApp\Model\Middleware\ApplicationMiddlewareInterface;

class RenderingMiddleware implements ApplicationMiddlewareInterface
{
    const DEFAULT_CONTENT_TYPE = 'text/html';

    const CONTENT_TYPE_HTML = 'text/html';
    const CONTENT_TYPE_JSON = 'application/json';

    const TEMPLATES_PATH = __DIR__ . '/../../View/';

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

        $contentType = $headers['Content-Type'] ?? self::DEFAULT_CONTENT_TYPE;
        $variables = $response->getVariables();

        switch($contentType) {
            case self::CONTENT_TYPE_HTML:
                $this->returnHtmlResponse($response->getFile(), $variables, $headers);
                exit;
            case self::CONTENT_TYPE_JSON:
                $this->returnJsonResponse($variables, $headers);
                exit;
            //@TODO add download file content type
            default:
                throw new \Exception('Not supported Content-Type ' . $contentType);
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
        if (empty($template) || !file_exists(self::TEMPLATES_PATH . $template)) {
            throw new \Exception('Template does not exist ' . var_export($template, true));
        }
        extract($variables);
        unset($variables);
        include(self::TEMPLATES_PATH . $template);
    }
}
