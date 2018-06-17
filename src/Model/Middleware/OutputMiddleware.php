<?php declare(strict_types=1);
namespace FxBot\Model\Middleware;

use LightApp\Model\Middleware\SimpleOutputMiddleware;
use LightApp\Model\Middleware\MiddlewareInterface;
use LightApp\Model\Service\SessionService;

class OutputMiddleware extends SimpleOutputMiddleware
{
    private $assetsVersion;
    private $sessionService;

    public function __construct(
        MiddlewareInterface $next,
        string $defaultContentType,
        string $assetsVersion,
        SessionService $sessionService
    ) {
        parent::__construct($next, $defaultContentType);
        $this->assetsVersion = $assetsVersion;
        $this->sessionService = $sessionService;
    }

    protected function buildHtmlResponse(string $template, array $variables, array $headers, array $cookies) : void
    {
        $variables['loggedIn'] = $this->sessionService->get(['user'])['user'];
        $variables['assetsVersioning'] = '?v=' . $this->assetsVersion;

        parent::buildHtmlResponse($template, $variables, $headers, $cookies);
    }
}
