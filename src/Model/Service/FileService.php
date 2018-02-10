<?php
namespace TinyApp\Model\Service;

use TinyApp\Model\Repository\FileRepository;

class FileService
{
    private const PER_PAGE = 5;

    private $fileRepository;

    public function __construct(FileRepository $fileRepository)
    {
        $this->fileRepository = $fileRepository;
    }

    public function uploadFiles(array $files, bool $public) : array
    {
        try {
            return $this->fileRepository->uploadFiles($files, $public);
        } catch(\Throwable $e) {
            trigger_error(
                'Failed to upload files with message ' . $e->getMessage() . ' with paylaod ' . var_export([$files, $public], true), E_USER_NOTICE
            );

            return [];
        }
    }

    public function getTypes() : array
    {
        return $this->fileRepository->getTypes();
    }

    public function isTypeImage(int $type) : bool
    {
        return $this->fileRepository->isTypeImage($type);
    }

    public function isTypePrivate(int $type) : bool
    {
        return $this->fileRepository->isTypePrivate($type);
    }

    public function isImageContentType(string $contentType) : bool
    {
        return $this->fileRepository->isImageMime($contentType);
    }

    public function getUploadPathByType(int $type) : string
    {
        try {
            return $this->fileRepository->getUploadPathByType($type);
        } catch(\Throwable $e) {
            trigger_error('Failed to get upload path by type ' . var_export($type, true) . ' with message ' . $e->getMessage(), E_USER_NOTICE);

            return '';
        }
    }

    public function getContentTypeByExtension(string $extension) : string
    {
        try {
            return $this->fileRepository->getSupportedMimeByExtension($extension);
        } catch(\Throwable $e) {
            trigger_error(
                'Failed to get content type by extension ' . var_export($extension, true) . ' with message ' . $e->getMessage(), E_USER_NOTICE
            );

            return '';
        }
    }

    public function getByType(int $type, int $page) : array
    {
        try {
            return $this->fileRepository->getByType($type, $page, self::PER_PAGE);
        } catch(\Throwable $e) {
            trigger_error(
                'Failed to get files for type ' . var_export($type, true) .
                ' and page ' . var_export($page, true) . ' with message ' . $e->getMessage(),
                E_USER_NOTICE
            );

            return ['files' => [], 'page' => null, 'pages' => 0];
        }
    }

    public function getByName(string $name) : array
    {
        try {
            return $this->fileRepository->getByName($name);
        } catch(\Throwable $e) {
            trigger_error('Failed to get file for name ' . var_export($name, true) . ' with message ' . $e->getMessage(), E_USER_NOTICE);

            return [];
        }
    }

    public function deleteFiles(array $ids) : bool
    {
        try {
            return $this->fileRepository->deleteFiles($ids);
        } catch(\Throwable $e) {
            trigger_error('Failed to delete all files for ids ' . var_export($ids, true) . ' with message ' . $e->getMessage(), E_USER_NOTICE);

            return false;
        }
    }
}
