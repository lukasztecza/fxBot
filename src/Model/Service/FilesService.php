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
    {//@TODO add try catch in all
        return $this->filesRepository->getPublic(FilesRepository::IMAGE_PUBLIC);
    }

    public function getPublicNotImages() : array
    {
        return $this->filesRepository->getPublic(FilesRepository::FILE_PUBLIC);
    }

    public function deleteFiles(array $ids) : bool
    {
        return $this->filesRepository->deleteFiles($ids);
    }
}
