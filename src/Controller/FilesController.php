<?php
namespace TinyApp\Controller;

use TinyApp\Controller\ControllerInterface;
use TinyApp\Model\System\Request;
use TinyApp\Model\System\Response;
use TinyApp\Model\Service\FilesService;
use TinyApp\Model\Validator\ValidatorFactory;
use TinyApp\Model\Validator\FilesUploadValidator;
use TinyApp\Model\Validator\FilesDeleteValidator;

class FilesController implements ControllerInterface
{
    private $filesService;

    public function __construct(FilesService $filesService, ValidatorFactory $validatorFactory)
    {
        $this->filesService = $filesService;
        $this->validatorFactory = $validatorFactory;
    }

    public function upload(Request $request) : Response
    {
        // Upload file and redirect to files list
        $validator = $this->validatorFactory->create(FilesUploadValidator::class);
        if ($request->getMethod() === 'POST') {
            if ($validator->check($request)) {
                $files = $request->getFiles();
                $result = $this->filesService->uploadFiles($files, (bool)$request->getPayload(['public'])['public']);
                if (!empty($result)) {
                     return new Response(null, [], [], ['Location' => '/files']);
                }
                $error = 'Failed to upload files';
            }
        }

        return new Response(
            'files/upload.php',
            ['error' => isset($error) ? $error : $validator->getError(), 'csrfToken' => $validator->getCsrfToken()],
            ['error' => 'html']
        );
    }

    public function list(Request $request) : Response
    {
        return new Response(
            'files/list.php',
            ['types' => $this->filesService->getTypes()],
            ['types' => 'html']
        );
    }

    public function listPaginated(Request $request) : Response
    {
        $type = $request->getAttributes(['type'])['type'];
        $page = $request->getAttributes(['page'])['page'];

        // Delete selected files and redirect to files list
        $validator = $this->validatorFactory->create(FilesDeleteValidator::class);
        if ($request->getMethod() === 'POST') {
            if ($validator->check($request)) {
                $ids = $request->getPayload(['ids'])['ids'];
                if (!empty($ids)) {
                    $this->filesService->deleteFiles($ids);
                    return new Response(null, [], [], ['Location' => '/files/list/' . (int)$type . '/' . (int)$page]);
                }
            }
        }

        // Get files
        $filesPack = $this->filesService->getByType($type, $page);
        if (empty($filesPack['files'])) {
            return new Response(null, [], [], ['Location' => '/files']);
        }

        // Set escape rules
        $rules = ['error' => 'html'];
        foreach ($filesPack['files'] as $key => $file) {
            $rules['files.' . $key . '.name'] = 'file';
        }
// @TODO After succes redirect to page from which comes POST
        return new Response(
            $this->filesService->isTypeImage($type) ? 'files/listImages.php' : 'files/listFiles.php',
            [
                'files' => $filesPack['files'],
                'type' => $type,
                'page' => $filesPack['page'],
                'pages' => $filesPack['pages'],
                'private' => $this->filesService->isTypePrivate($type),
                'error' => isset($error) ? $error : $validator->getError(),
                'csrfToken' => $validator->getCsrfToken()
            ],
            $rules
        );
    }
}
