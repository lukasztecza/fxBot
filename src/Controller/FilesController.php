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
                extract($request->getPayload(['public']));
                $result = $this->filesService->uploadFiles($files, (bool)$public);
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
        // Get images and other files
        $images = $this->filesService->getPublicImages();
        $otherFiles = $this->filesService->getPublicNotImages();

        // Delete selected files and redirect to files list
        $validator = $this->validatorFactory->create(FilesDeleteValidator::class);
        if ($request->getMethod() === 'POST') {
            if ($validator->check($request)) {
                extract($request->getPayload(['ids']));
                if (!empty($ids)) {
                    $this->filesService->deleteFiles($ids);
                    return new Response(null, [], [], ['Location' => '/files']);
                }
            }
        }

        return new Response(
            'files/list.php',
            [
                'images' => $images,
                'otherFiles' => $otherFiles,
                'error' => isset($error) ? $error : $validator->getError(),
                'csrfToken' => $validator->getCsrfToken()
            ],
            ['error' => 'html']
        );
    }
}
