<?php

namespace Minimalism\Sendfile;


use RuntimeException;
use ZipArchive;

class ZipArch
{
    protected $file = [];
    protected $zip;
    protected $tmpZipFile;

    public function __construct()
    {
        $this->zip = new ZipArchive;
        $this->tmpZipFile = tempnam(sys_get_temp_dir(), 'zip_');
        $res = $this->zip->open($this->tmpZipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if(!$res) {
            throw new RuntimeException($res->getStatusString());
        }
    }

    public function attachString($zipPath, $string)
    {
        $this->assertZip();
        if (!$this->zip->addFromString($zipPath, $string)) {
            throw new RuntimeException("Add String Fail");
        };
    }

    public function attachFile($zipPath, $filePath)
    {
        $this->assertZip();
        if (!$this->zip->addFile($filePath, $zipPath)) {
            throw new RuntimeException("Add File Fail");
        };
    }

    public function send($filename)
    {
        $this->assertZip();
        $this->zip->close();
        $this->zip = null;

        $fileContent = file_get_contents($this->tmpZipFile);
        header("Content-type: application/octet-stream");
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        // header("Content-Length: " . mb_strlen($fileContent));
        header("Content-Length: " . strlen($fileContent));
        echo $fileContent;
    }

    public function __destruct()
    {
        unlink($this->tmpZipFile);
    }
    
    private function assertZip()
    {
        if ($this->zip === null) {
            throw new RuntimeException("ZipArchive Had Been Closed!");
        }
    }
}