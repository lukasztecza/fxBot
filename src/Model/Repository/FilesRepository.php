<?php
namespace TinyApp\Model\Repository;

use TinyApp\Model\Repository\DatabaseConnection;

class FilesRepository
{
    private const UPLOAD_TMP_DIR = APP_ROOT_DIR . '/tmp/upload';

    private const PRIVATE_STORAGE_PATH = APP_ROOT_DIR . '/private';
    private const PUBLIC_UPLOADS_PATH = APP_ROOT_DIR . '/public/upload';

    private const IMAGES = '/images';
    private const FILES = '/files';

    public const IMAGE_PUBLIC = 1;
    public const IMAGE_PRIVATE = 2;
    public const FILE_PUBLIC = 3;
    public const FILE_PRIVATE = 4;

    private const IMAGES_MIME = [
        "jpg" => "image/jpeg",
        "jpeg" => "image/jpeg",
        "png" => "image/png",
        "gif" => "image/gif"
    ];

    private const FILES_MIME = [
        "txt" => "text/plain",
        "pdf" => "application/pdf",
        "odt" => "application/vnd.oasis.opendocument.text",
        "ods" => "application/vnd.oasis.opendocument.spreadsheet",
        "odp" => "application/vnd.oasis.opendocument.presentation",
        "doc" => "application/msword",
        "docx" => "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
        "xls" => "application/vnd.ms-excel",
        "xlsx" => "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
        "ppt" => "application/vnd.ms-powerpoint",
        "pptx" => "application/vnd.openxmlformats-officedocument.presentationml.presentation",
        "mp3" => "audio/mpeg",
        "wav" => "audio/x-wav"
    ];

    private $write;

    public function __construct(DatabaseConnection $write)
    {
        $this->write = $write;
        // Create tmp upload directory if it does not exist (has to be set in php.ini because it can not be set on runtime using ini_set)
        if (!file_exists(self::UPLOAD_TMP_DIR)) {
            mkdir(self::UPLOAD_TMP_DIR, 0775, true);
        }
    }

    public function getPublic(int $type) : array
    {
        $items = $this->write->fetch(
            'SELECT * FROM `files` WHERE `type` = :type', ['type' => $type]
        );

        return $items ?? [];
    }

    public function uploadFiles(array $files, bool $public = false) : array
    {
        try {
            $this->write->begin();
            $uploaded = [];
            foreach ($files as $file) {
                $uploaded[] = $this->uploadFile($file, $public);
            }
            $this->write->commit();
        } catch (\Exception $e) {
            trigger_error(
                'Rolling back after failed attempt to upload files with message ' . $e->getMessage() . ' with payload ' . var_export([$files, $public], true)
            );
            foreach ($uploaded as $file) {
                unlink($file['uploadPath']);
            }
            $this->write->rollBack();
            throw $e;
        }

        return $uploaded;
    }

    public function deleteFiles(array $ids) : bool
    {
        $this->write->prepare('SELECT * FROM `files` WHERE `id` = :id');
        $files = [];
        foreach ($ids as $id) {
            $output = $this->write->fetch(null, ['id' => $id]);
            if (!empty($output)) {
                $files[] = array_pop($output);
            }
        }
        $this->write->clean();

        if (empty($files)) {
            return false;
        }

        $this->write->prepare('DELETE FROM `files` WHERE `id` = :id');
        foreach ($files as $file) {
            $this->deleteFile($file);
        }
        $this->write->clean();

        return true;
    }

    private function uploadFile(array $file, bool $public) : array
    {
        $uploadPath = $public ? self::PUBLIC_UPLOADS_PATH : self::PRIVATE_STORAGE_PATH;

        $name = date('YdmHis') . preg_replace('/[^a-zA-Z0-9]/', '', pathinfo($file['name'], PATHINFO_FILENAME));
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $mime = mime_content_type($file["tmp_name"]);

        // Check if image or other accepted file
        switch (true) {
            case
                getimagesize($file['tmp_name']) &&
                array_key_exists(strtolower($extension), self::IMAGES_MIME) &&
                self::IMAGES_MIME[$extension] === $mime
            :
                $uploadPath .= self::IMAGES;
                $type = $public ? self::IMAGE_PUBLIC : self::IMAGE_PRIVATE;
                break;
            case
                array_key_exists(strtolower($extension), self::FILES_MIME) &&
                self::FILES_MIME[$extension] === $mime
            :
                $uploadPath .= self::FILES;
                $type = $public ? self::FILE_PUBLIC : self::FILE_PRIVATE;
                break;
            default:
                throw new \Exception(
                    'Unsupported file mime content type ' . var_export($mime, true) . ' for file ' . var_export([$file, $public], true)
                );
        }

        // Create directory if it does not exist
        if (!file_exists($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        // Finalise preparation of paths
        $uploadPath .= '/' . $name . '.' . $extension;
        if (file_exists($uploadPath)) {
            throw new \Exception('File already exists ' . var_export($uploadPath, true ) . ' with payload ' . var_export($file, true));
        }

        // Move uploaded file and store its new path in db
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            throw new \Exception('Failed to move uploaded file to ' . var_export($uploadPath, true) . ' with payload ' . var_export($file, true));
        }
        $id = $this->saveFile($name, $extension, $type);

        return [
            'id' => $id,
            'name' => $name,
            'extension' => $extension,
            'type' => $type,
            'uploadPath' => $uploadPath
        ];
    }

    private function saveFile(string $name, string $extension, int $type) : int
    {
        $affectedId = $this->write->execute(
            'INSERT INTO `files` (`name`, `extension`, `type`) VALUES (:name, :extension, :type)', [
                'name' => $name,
                'extension' => $extension,
                'type' => $type
            ]
        );

        return (int)$affectedId;
    }

    private function deleteFile(array $file) : void
    {
        $uploadPath = '';
        switch ($file['type']) {
            case self::IMAGE_PUBLIC:
                $uploadPath .= self::PUBLIC_UPLOADS_PATH . self::IMAGES;
                break;
            case self::IMAGE_PRIVATE:
                $uploadPath .= self::PRIVATE_STORAGE_PATH . self::IMAGES;
                break;
            case self::FILE_PUBLIC:
                $uploadPath .= self::PUBLIC_UPLOADS_PATH . self::FILES;
                break;
            case self::IMAGE_PRIVATE:
                $uploadPath .= self::PRIVATE_STORAGE_PATH . self::FILES;
                break;
        }
        $uploadPath .= '/' . $file['name'] . '.' . $file['extension'];
        $this->write->execute(null, ['id' => $file['id']]);
        unlink($uploadPath);
    }
}
