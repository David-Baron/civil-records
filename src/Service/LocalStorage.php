<?php

namespace CivilRecords\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class LocalStorage
{
    const LOCALE_STORAGE = '../__storage';
    const DIR_BACKUP = '/backup';
    const DIR_UPLOAD = '/upload';
    const DIRECTORY = [
        'DIR_BACKUP' => '/backup',
        'DIR_UPLOAD' => '/upload',
    ];

    /**
     * @param int $directory One of [DIR_BACKUP | DIR_UPLOAD]
     */
    public function move(UploadedFile $origin_file, string $new_file, $directory)
    {
        if (!file_exists(self::LOCALE_STORAGE . self::DIRECTORY[$directory] . '/' . $new_file)) {
            return move_uploaded_file($origin_file, self::LOCALE_STORAGE . self::DIRECTORY[$directory] . '/' . $new_file);
        }

        throw new \Exception("File exist. Please rename it.", 1);
    }
}
