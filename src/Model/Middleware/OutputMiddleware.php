<?php
namespace TinyApp\Model\Middleware;

use TinyApp\Model\System\Request;
use TinyApp\Model\System\Response;
use TinyApp\Model\Middleware\ApplicationMiddlewareAbstract;
use TinyApp\Model\Middleware\ApplicationMiddlewareInterface;
use TinyApp\Model\Service\FilesService;

class OutputMiddleware extends ApplicationMiddlewareAbstract
{
    private const CONTENT_TYPE_HTML = 'text/html';
    private const CONTENT_TYPE_JSON = 'application/json';
    private const CONTENT_TYPE_STREAM = 'application/octet-stream';

    private const TEMPLATES_PATH = APP_ROOT_DIR . '/src/View';

    private $assetsVersion;
    private $filesService;

    public function __construct(ApplicationMiddlewareInterface $next, string $assetsVersion, FilesService $filesService)
    {
        parent::__construct($next);
        $this->assetsVersion = $assetsVersion;
        $this->filesService = $filesService;
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

        switch (true) {
            case $location:
                $this->setHeaders($headers);
                break;
            case $contentType === self::CONTENT_TYPE_HTML:
                $this->buildHtmlResponse($response->getFile(), $response->getVariables(), $headers, $response->getCookies());
                break;
            case $contentType === self::CONTENT_TYPE_JSON:
                $this->buildJsonResponse($response->getVariables(), $headers);
                break;
            case $this->filesService->isImageContentType($contentType):
                $this->buildImageResponse($response->getFile(), $response->getVariables(), $headers);
                break;
            case $contentType === self::CONTENT_TYPE_STREAM:
                $this->buildDownloadResponse($response->getFile(), $response->getVariables(), $headers);
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

    private function buildJsonResponse(array $variables, array $headers) : void
    {
        $this->setHeaders($headers);
        echo json_encode($variables);
    }

    private function buildHtmlResponse(string $template, array $variables, array $headers, array $cookies) : void
    {
        if (empty($template) || !file_exists(self::TEMPLATES_PATH . '/' . $template)) {
            throw new \Exception('Template does not exist ' . var_export($template, true));
        }

        $this->setHeaders($headers);
        $this->setCookies($cookies);
        extract($variables);
        unset($variables);
        unset($headers);
        unset($cookies);
        $assetsVersioning = '?v=' . $this->assetsVersion;
        include(self::TEMPLATES_PATH . '/' . $template);
    }

    private function buildImageResponse(string $file, array $variables, array $headers) : void
    {
        $path = isset($variables['type']) ? $this->filesService->getUploadPathByType($variables['type']) : null;
        if (empty($path) || !file_exists($path . '/' . $file)) {
            throw new \Exception(
                'Image does not exists or can not be accessed ' . var_export($path . '/' . $file, true) .
                ' for provided variables ' . var_export($variables, true)
            );
        }

        $this->setHeaders($headers);
        readfile($path . '/' . $file);
    }

    private function buildDownloadResponse(string $file, array $variables, array $headers) : void
    {
        $path = isset($variables['type']) ? $this->filesService->getUploadPathByType($variables['type']) : null;
        if (empty($path) || !file_exists($path . '/' . $file)) {
            throw new \Exception(
                'File does not exists or can not be accessed ' . var_export($path . '/' . $file, true) .
                ' for provided variables ' . var_export($variables, true)
            );
        }
        $file = $path . '/' . $file;

        $headers['Content-Description'] = 'File Transfer';
        $headers['Content-Disposition'] = 'attachment; filename="' . basename($file)  . '"';
        $headers['Expires'] = '0';
        $headers['Cache-Control'] = 'must-revalidate';
        $headers['Pragma'] = 'public';
        $headers['Content-Length'] = filesize($file);

        $this->setHeaders($headers);
        readfile($file);
    }
}
