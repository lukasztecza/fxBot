<?php
namespace TinyApp\Model\Service;

use TinyApp\Model\Repository\FilesRepository;

class FilesService
{
    private $filesRepository;

    public function __construct(FilesRepository $filesRepository)
    {
        $this->filesRepository = $filesRepository;
    }

    public function uploadFiles(array $files, bool $public) : array
    {
        try {
            return $this->filesRepository->uploadFiles($files, $public);
        } catch(\Exception $e) {
            trigger_error(
                'Failed to upload files with message ' . $e->getMessage() . ' with paylaod ' . var_export([$files, $public], true), E_USER_NOTICE
            );

            return [];
        }
    }

    public function getPublicImages() : array
    {
        try {
            return $this->filesRepository->getPublic(FilesRepository::IMAGE_PUBLIC);
        } catch(\Exception $e) {
            trigger_error('Failed to get public images with message ' . $e->getMessage(), E_USER_NOTICE);

            return [];
        }
    }

    public function getPublicNotImages() : array
    {
        try {
            return $this->filesRepository->getPublic(FilesRepository::FILE_PUBLIC);
        } catch(\Exception $e) {
            trigger_error('Failed to get public not images with message ' . $e->getMessage(), E_USER_NOTICE);

            return [];
        }
    }

    public function deleteFiles(array $ids) : bool
    {
        try {
            return $this->filesRepository->deleteFiles($ids);
        } catch(\Exception $e) {
            trigger_error('Failed to delete all files for ids ' . var_export($ids, true) . ' with message ' . $e->getMessage(), E_USER_NOTICE);

            return false;
        }
    }
}
