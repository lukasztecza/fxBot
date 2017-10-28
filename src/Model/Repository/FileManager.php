<?php
namespace TinyApp\Model\Repository;

class FileManager
{
    const PRIVATE_STORAGE_PATH = __DIR__ . '/../../../storage';
    const PUBLIC_ASSETS_PATH = __DIR__ . '/../../../public/assets';

    const IMAGES_MIME = [
        "jpg" => "image/jpeg",
        "jpeg" => "image/jpeg",
        "png" => "image/png",
        "gif" => "image/gif"
    ];
    
    const FILES_MIME = [
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

    public function __construct()
    {

    }

    public function storeFile(array $file, bool $public = false) : bool
    {
        $path = $public ? self::PUBLIC_ASSETS_PATH : self::PRIVATE_STORAGE_PATH;
        $filePath = preg_replace(['/(\.\.\/)/', '/\.\./'], '', preg_replace('/[^a-zA-Z0-9\.\/]/', '', $file['tmp_name']));

        //@TODO if dir does not exist create
        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }

        //@TODO check if it is image or file and append files or images to path
        if (!move_uploaded_file($file['tmp_name'], $path . 'test.txt')) {
            return false;
        }

        return true;
    }

    public function getPath() : string
    {
//        if (file_exists()) {

  //      }
        return '';
    }
}
