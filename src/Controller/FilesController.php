<?php
namespace TinyApp\Controller;

use TinyApp\Controller\ControllerInterface;
use TinyApp\Model\System\Request;
use TinyApp\Model\System\Response;
use TinyApp\Model\Service\FilesService;

class FilesController implements ControllerInterface
{
    private $filesService;

    public function __construct(FilesService $filesService)
    {
        $this->filesService = $filesService;
    }

    public function upload(Request $request) : Response
    {
        if ($request->getMethod() === 'POST') {
            $files = $request->getFiles();
            extract($request->getPayload(['public']));
            $result = $this->filesService->uploadFiles($files, (bool)$public);
            if (!empty($result)) {
                 return new Response(null, [], [], ['Location' => '/files']);
            }
            $error = 'Failed to upload files';
        }
        return new Response(
            'files/upload.php',
            ['error' => $error ?? ''],
            ['error' => 'html']
        );
    }

    public function list(Request $request) : Response
    {
        $files = $this->filesService->getPublicImages();
        if ($request->getMethod() === 'POST') {
            extract($request->getPayload(['ids']));
            if (!empty($ids)) {
                $this->filesService->deleteFile($ids);
                return new Response(null, [], [], ['Location' => '/files']);
            }
        }
        return new Response(
            'files/list.php',
            ['files' => $files]
        );
    }
}
