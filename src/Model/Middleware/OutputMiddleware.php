<?php
namespace TinyApp\Model\Middleware;

use TinyApp\Model\System\Request;
use TinyApp\Model\System\Response;
use TinyApp\Model\Middleware\ApplicationMiddlewareAbstract;
use TinyApp\Model\Middleware\ApplicationMiddlewareInterface;

class OutputMiddleware extends ApplicationMiddlewareAbstract
{
    const CONTENT_TYPE_HTML = 'text/html';
    const CONTENT_TYPE_JSON = 'application/json';

    const TEMPLATES_PATH = APP_ROOT_DIR . '/src/View';

    private $assetsVersion;

    public function __construct(ApplicationMiddlewareInterface $next, string $assetsVersion)
    {
        parent::__construct($next);
        $this->assetsVersion = $assetsVersion;
    }

    public function process(Request $request) : Response
    {
        $response = $this->getNext()->process($request);
        if (!($response instanceof Response)) {
            throw new \Exception('Controller has to return Response object, returned ' . var_export($response, true));
        }

        $headers = $response->getHeaders();
        $location = $headers['Location'] ?? null;
        $contentType = $headers['Content-Type'] ?? null;
        $this->setCookies($response->getCookies());
        $this->setHeaders($headers);

        switch (true) {
            case $location || $contentType === null:
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

    private function setHeaders(array $headers) : void
    {
        foreach ($headers as $key => $value) {
            if (!is_numeric($key)) {
                header($key . ': ' . $value);
            } else {
                header($value);
            }
        }
    }

    private function setCookies(array $cookies) : void
    {
        foreach ($cookies as $cookie) {
            setcookie(
                $cookie['name'],
                $cookie['value'],
                $cookie['expire'],
                $cookie['path'],
                $cookie['domain'],
                $cookie['secure'],
                $cookie['httponly']
            );
        }
    }

    private function buildJsonResponse(array $variables) : void
    {
        echo json_encode($variables);
    }

    private function buildHtmlResponse(string $template, array $variables) : void
    {
        if (empty($template) || !file_exists(self::TEMPLATES_PATH . '/' . $template)) {
            throw new \Exception('Template does not exist ' . var_export($template, true));
        }
        extract($variables);
        unset($variables);
        $assetsVersioning = '?v=' . $this->assetsVersion;
        include(self::TEMPLATES_PATH . '/' . $template);
    }
}
