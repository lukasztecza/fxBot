<?php declare(strict_types=1);
namespace FxBot\Controller;

use LightApp\Controller\ControllerAbstract;
use LightApp\Model\Service\SessionService;
use LightApp\Model\Validator\ValidatorFactory;
use LightApp\Model\System\Request;
use LightApp\Model\System\Response;
use FxBot\Model\Validator\LoginValidator;

class AuthenticationController extends ControllerAbstract
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
        if (!empty($this->sessionService->get(['user'])['user'])) {
            return $this->redirectResponse('/');
        }

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

                    return $this->redirectResponse($this->sessionService->get(['previousNotAllowedPath'], true)['previousNotAllowedPath'] ?? '/');
                }
                $error = 'Invalid credentials';
            }
        }

        return $this->htmlResponse(
            'authentication/loginForm.php',
            ['error' => $error ?? $validator->getError(), 'csrfToken' => $validator->getCsrfToken()],
            ['error' => 'html']
        );
    }

    public function logout(Request $request) : Response
    {
        $this->sessionService->set(['roles' => null]);
        $this->sessionService->set(['user' => null]);
        $this->sessionService->destroy();

        return $this->redirectResponse('/');
    }
}
