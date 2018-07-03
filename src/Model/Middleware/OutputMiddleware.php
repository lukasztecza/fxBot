<?php declare(strict_types=1);
namespace FxBot\Model\Middleware;

use LightApp\Model\Middleware\SimpleOutputMiddleware;
use LightApp\Model\Middleware\MiddlewareInterface;
use LightApp\Model\Service\SessionService;

class OutputMiddleware extends SimpleOutputMiddleware
{
    private $sessionService;
    private $environment;

    public function __construct(
        MiddlewareInterface $next,
        string $defaultContentType,
        SessionService $sessionService,
        string $environment
    ) {
        parent::__construct($next, $defaultContentType);
        $this->sessionService = $sessionService;
        $this->environment = $environment;
    }

    protected function addDefaultHeaders(array &$headers) : void
    {
        if ($this->environment !== 'dev') {
            parent::addDefaultHeaders($headers);
        }
    }

    protected function buildHtmlResponse(string $template, array $variables, array $headers, array $cookies) : void
    {
        $variables['loggedIn'] = $this->sessionService->get(['user'])['user'];

        parent::buildHtmlResponse($template, $variables, $headers, $cookies);
    }
}
