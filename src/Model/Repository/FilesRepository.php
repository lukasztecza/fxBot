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

    private const IMAGE_PUBLIC = 1;
    private const IMAGE_PRIVATE = 2;
    private const FILE_PUBLIC = 3;
    private const FILE_PRIVATE = 4;

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

    public function getPublicImages() : array
    {
        $items = $this->write->fetch(
            'SELECT * FROM `files` WHERE `type` = :type', ['type' => self::IMAGE_PUBLIC]
        );

        return $items ?? [];
    }

    public function uploadFiles(array $files, bool $public = false) : array
    {
        try {
            $this->write->beginTransaction();
            $uploaded = [];
            foreach ($files as $file) {
                $uploaded[] = $this->uploadFile($file, $public);
            }
            $this->write->commit();
        } catch (\Exception $e) {
            trigger_error('Failed to upload files with message ' . $e->getMessage() . ' with paylaod ' . var_export($files, true), E_USER_NOTICE);
            foreach ($uploaded as $file) {
                unlink($file['uploadPath']);
            }
            $this->write->rollBack();
            return [];
        }

        return $uploaded;
    }

    public function deleteFiles(array $ids) : bool
    {
    //@TODO try to do it using IN (:ids) with PDO
        $sql = 'SELECT * FROM `files` WHERE ';
        foreach ($ids as $id) {
            $sql .= '`id` = ? OR ';
            $params[] = $id;
        }
        if (empty($params)) {
            return false;
        }
        $sql = rtrim($sql, 'OR ');
        $files = $this->write->fetch($sql, $params);

        if (empty($files)) {
            return false;
        }

        $this->write->prepare('DELETE FROM `files` WHERE `id` = :id');
        foreach ($files as $file) {
            $this->deleteFile($file);
        }
        $this->write->cleanStatement();

        return true;
    }

    private function uploadFile(array $file, bool $public) : array
    {
        $uploadPath = $public ? self::PUBLIC_UPLOADS_PATH : self::PRIVATE_STORAGE_PATH;

        $name = date('YdmHis') . preg_replace('/[^a-zA-Z0-9]/', '', pathinfo($file['name'], PATHINFO_FILENAME));
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);

        // Check if image or other accepted file
        switch (true) {
            case
                getimagesize($file['tmp_name']) &&
                array_key_exists(strtolower($extension), self::IMAGES_MIME) &&
                self::IMAGES_MIME[$extension] === mime_content_type($file["tmp_name"])
            :
                $uploadPath .= self::IMAGES;
                $type = $public ? self::IMAGE_PUBLIC : self::IMAGE_PRIVATE;
                break;
            case
                array_key_exists(strtolower($extension), self::FILES_MIME) &&
                self::FILES_MIME[$extension] === mime_content_type($file["tmp_name"])
            :
                $uploadPath .= self::FILES;
                $type = $public ? self::FILE_PUBLIC : self::FILE_PRIVATE;
                break;
            default:
                throw new \Exception('Unsupported file mime content type ' . var_export($file));
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
