<?php
namespace TinyApp\Controller;

use TinyApp\Controller\ControllerInterface;
use TinyApp\Model\Service\SessionService;
use TinyApp\Model\System\Request;
use TinyApp\Model\System\Response;
use TinyApp\Model\Validator\ValidatorFactory;
use TinyApp\Model\Validator\LoginValidator;

class AuthenticationController implements ControllerInterface
{
    private $sessionService;
    private $validatorFactory;
    private $inMemoryUsername;
    private $inMemoryPasswordHash;

    public function __construct(
        SessionService $sessionService,
        ValidatorFactory $validatorFactory,
        string $inMemoryUsername,
        string $inMemoryPasswordHash
    ) {
        $this->sessionService = $sessionService;
        $this->validatorFactory = $validatorFactory;
        $this->inMemoryUsername = $inMemoryUsername;
        $this->inMemoryPasswordHash = $inMemoryPasswordHash;
    }

    public function login(Request $request) : Response
    {
        // Redirect to home users which are already logged in
        if (!empty($this->sessionService->get(['user'])['user'])) {
            return new Response(null, [], [], ['Location' => '/']);
        }

        // Login user and redirect to previous path if exists (user could be redirected here by Security Middleware)
        $validator = $this->validatorFactory->create(LoginValidator::class);
        if ($request->getMethod() === 'POST') {
            if ($validator->check($request)) {
                $payload = $request->getPayload(['username', 'password']);
                if (
                    $this->inMemoryUsername === $payload['username'] &&
                    password_verify($payload['password'], $this->inMemoryPasswordHash)
                ) {
                    $this->sessionService->set(['roles' => ['ROLE_USER']]);
                    $this->sessionService->set(['user' => $payload['username']]);

                    return new Response(null, [], [], [
                        'Location' => ($this->sessionService->get(['previousNotAllowedPath'], true)['previousNotAllowedPath'] ?? '/')
                    ]);
                }
                $error = 'Invalid credentials';
            }
        }

        return new Response(
            'authentication/loginForm.php',
            ['error' => $error ?? $validator->getError(), 'csrfToken' => $validator->getCsrfToken()],
            ['error' => 'html']
        );
    }

    public function logout(Request $request) : Response
    {
        // Logout user
        $this->sessionService->set(['roles' => null]);
        $this->sessionService->set(['user' => null]);
        $this->sessionService->destroy();

        return new Response(null, [], [], ['Location' => '/']);
    }
}