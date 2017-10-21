<?php
namespace TinyApp\Controller;

use TinyApp\Controller\ControllerInterface;
use TinyApp\Model\Service\SessionService;
use TinyApp\Model\System\Request;
use TinyApp\Model\System\Response;

class AuthenticationController implements ControllerInterface
{
    private $sessionService;

    public function __construct(SessionService $sessionService)
    {
        $this->sessionService = $sessionService;
    }

    public function login(Request $request) : Response
    {
        $this->sessionService->set('roles', ['ROLE_USER']);
        //var_dump(session_id());exit;
        var_dump($this->sessionService->get());exit;
    }

    public function logout(Request $request) : Response
    {
        $this->sessionService->set('roles', null);
        $this->sessionService->destroy();
        //var_dump(session_id());exit;
        var_dump($this->sessionService->get());exit;
    }
}
