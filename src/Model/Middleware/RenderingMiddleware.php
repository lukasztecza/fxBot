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

    private $next;

    public function __construct(ApplicationMiddlewareInterface $next)
    {
        $this->next = $next;
    }

    public function process(Request $request) : Response
    {
//        $controller = $this->controller;
//        $action = $this->action;
//        $response = $controller->$action($request);
        $response = $this->next->process($request);
        if (!($response instanceof Response)) {
            throw new \Exception('Controller has to return Response object, returned ' . var_export($response, true));
        }

        $headers = $response->getHeaders();
        $location = $headers['Location'] ?? null;
        $contentType = $headers['Content-Type'] ?? self::DEFAULT_CONTENT_TYPE;
        $this->setHeaders($headers);

        switch (true) {
            case $location:
                break;
            case $contentType === self::CONTENT_TYPE_HTML:
                $variables = $response->getVariables();
                $this->buildHtmlResponse($response->getFile(), $variables);
                break;
            case $contentType === self::CONTENT_TYPE_JSON:
                $variables = $response->getVariables();
                $this->buildJsonResponse($variables);
                break;
            //@TODO add download file content type
            default:
                throw new \Exception('Not supported Content-Type ' . $contentType);
        }

        return $response;
    }

    private function setHeaders(array $headers)
    {
        foreach ($headers as $key => $value) {
            header($key . ': ' . $value);
        }
    }

    private function buildJsonResponse(array $variables)
    {
        echo json_encode($variables);
    }

    private function buildHtmlResponse(string $template, array $variables)
    {
        if (empty($template) || !file_exists(self::TEMPLATES_PATH . $template)) {
            throw new \Exception('Template does not exist ' . var_export($template, true));
        }
        extract($variables);
        unset($variables);
        include(self::TEMPLATES_PATH . $template);
    }
}
