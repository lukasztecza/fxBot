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

        $file = $this->filesService->getByName($attributes['file']);
        if (empty($file[0]['name']) || empty($file[0]['type'])) {
            return $this->getNotFoundResponse($request);
        }

        $contentType = $this->filesService->getContentTypeByExtension(pathinfo($file[0]['name'], PATHINFO_EXTENSION));
        if (empty($contentType)) {
            return $this->getNotFoundResponse($request);
        }

        if (!empty($file)) {
            return new Response(
                $file[0]['name'],
                ['type' => $file[0]['type']],
                [],
                ['Content-Type' => ($this->filesService->isImageContentType($contentType) ? $contentType : 'application/octet-stream')]
            );
        }
    }

    private function getNotFoundResponse(Request $request) : Response
    {
        return new Response('404.php', [], [], [$request->getServerProtocol() . ' 404 Not Found']);
    }
}
