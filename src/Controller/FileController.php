<?php
namespace TinyApp\Controller;

use TinyApp\Controller\ControllerInterface;
use TinyApp\Model\System\Request;
use TinyApp\Model\System\Response;
use TinyApp\Model\Service\FileService;
use TinyApp\Model\Service\SessionService;
use TinyApp\Model\Validator\ValidatorFactory;
use TinyApp\Model\Validator\FilesUploadValidator;
use TinyApp\Model\Validator\FilesDeleteValidator;

class FileController implements ControllerInterface
{
    private $fileService;
    private $sessionService;

    public function __construct(FileService $fileService, ValidatorFactory $validatorFactory, SessionService $sessionService)
    {
        $this->fileService = $fileService;
        $this->validatorFactory = $validatorFactory;
        $this->sessionService = $sessionService;
    }

    public function upload(Request $request) : Response
    {
        // Upload files and redirect to files list
        $validator = $this->validatorFactory->create(FilesUploadValidator::class);
        if ($request->getMethod() === 'POST') {
            if ($validator->check($request)) {
                $files = $request->getFiles(['someFile']);
                $result = $this->fileService->uploadFiles($files, (bool)$request->getPayload(['public'])['public']);
                if (!empty($result)) {
                    $this->sessionService->set(['flash' => ['type' => 'success', 'text' => 'Files are added']]);
                    return new Response(null, [], [], ['Location' => '/file']);
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
            ['types' => $this->fileService->getTypes(), 'flash' => $this->sessionService->get(['flash'], true)['flash']],
            ['types' => 'html', 'flash' => 'html']
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
                    if ($this->fileService->deleteFiles($ids)) {
                        $this->sessionService->set(['flash' => ['type' => 'success', 'text' => 'Files are deleted']]);
                    } else {
                        $this->sessionService->set(['flash' => ['type' => 'error', 'text' => 'Files are not deleted']]);
                    }

                    return new Response(null, [], [], ['Location' => '/file/list/' . (int)$type . '/' . (int)$page]);
                }
            }
        }

        // Get files
        $filesPack = $this->fileService->getByType($type, $page);
        if (empty($filesPack['files'])) {
            $this->sessionService->set(['flash' => ['type' => 'error', 'text' => 'There is no files under selected category and page']]);
            return new Response(null, [], [], ['Location' => '/file']);
        }

        // Set escape rules
        $rules = ['error' => 'html', 'flash' => 'html'];
        foreach ($filesPack['files'] as $key => $file) {
            $rules['files.' . $key . '.name'] = 'file';
        }

        return new Response(
            $this->fileService->isTypeImage($type) ? 'files/listImages.php' : 'files/listFiles.php',
            [
                'files' => $filesPack['files'],
                'type' => $type,
                'page' => $filesPack['page'],
                'pages' => $filesPack['pages'],
                'private' => $this->fileService->isTypePrivate($type),
                'flash' => $this->sessionService->get(['flash'], true)['flash'],
                'error' => isset($error) ? $error : $validator->getError(),
                'csrfToken' => $validator->getCsrfToken()
            ],
            $rules
        );
    }
}
