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
        $validator = $this->validatorFactory->create(LoginValidator::class);
        if ($request->getMethod() === 'POST') {
            $payload = $request->getPayload(['username', 'password']);
            if ($validator->check($payload)) {
                if (
                    $this->inMemoryUsername === $payload['username'] &&
                    password_verify($payload['password'], $this->inMemoryPasswordHash)
                ) {
                    $this->sessionService->set('roles', ['ROLE_USER']);
                    $cookies = $request->getCookies(['previousPath']);
                    return new Response(null, [], [], ['Location' => $request->getHost() . $cookies['previousPath']]);
                }
                $error = 'Invalid credentials';
            }
        }

        return new Response(
            'loginForm.php',
            ['error' => $error ?? $validator->getError()]
        );
    }

    public function logout(Request $request) : Response
    {
        $this->sessionService->set('roles', null);
        $this->sessionService->destroy();
        return new Response(null, [], [], ['Location' => $request->getHost() . '/']);
    }
}
