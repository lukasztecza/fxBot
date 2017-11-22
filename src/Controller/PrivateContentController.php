<?php
namespace TinyApp\Controller;

use TinyApp\Controller\ControllerInterface;
use TinyApp\Model\Service\FilesService;
use TinyApp\Model\Service\SessionService;
use TinyApp\Model\System\Request;
use TinyApp\Model\System\Response;

class PrivateContentController implements ControllerInterface
{
    private $sessionService;
    private $filesService;

    public function __construct(SessionService $sessionService, FilesService $filesService)
    {
        $this->sessionService = $sessionService;
        $this->filesService = $filesService;
    }

    public function serve(Request $request) : Response
    {
        $attributes = $request->getAttributes(['directory', 'file']);

        $file = $this->filesService->getPrivateByName($attributes['file']);

        var_dump($file);exit;
        if (1) {
            return new Response('403.php', [], [], [$request->getServerProtocol() . ' 403 Forbidden']);
        }

        return new Response($direcotry . '/' . $file, [], [], ['Content-Type' => 'image/png']);
        //@TODO return image/* content type and update outputmiddleware to serve it
    }
}
