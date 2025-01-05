<?php 
namespace CivilRecords\Engine;

use Symfony\Component\HttpFoundation\File\UploadedFile;


class FileUploader
{
    protected $storage;

    public function __construct($storage)
    {
        $this->storage = new $storage();
    }

    public function upload(UploadedFile $file, $directory): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $newFilename = $originalFilename . '-' . uniqid() . '.' . $file->guessClientExtension();
        $this->storage->move($file, $newFilename, $directory);

        return $newFilename;
    }
}