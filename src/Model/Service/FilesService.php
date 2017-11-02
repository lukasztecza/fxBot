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
        return $this->filesRepository->uploadFiles($files, $public);
    }

    public function getPublicImages() : array
    {
        return $this->filesRepository->getPublicImages();
    }

    public function deleteFile(array $ids) : bool
    {
        return $this->filesRepository->deleteFiles($ids);
    }
}
