<?php

namespace libraries;


class FileEdit
{

    protected $imgArr = [];
    protected $directory;


    public function addFile($directory = false){

        if(!$directory) $this->directory = $_SERVER['DOCUMENT_ROOT'] . PATH . UPLOAD_DIR;
            else $this->directory = $directory;


        foreach ($_FILES as $key => $file){

            if(is_array($file['name'])){

                $fileArr = [];

                for($i = 0; $i < count($file['name']); $i++){

                    if(!empty($file['name'][$i])){

                        $fileArr['name'] = $file['name'][$i];
                        $fileArr['type'] = $file['type'][$i];
                        $fileArr['tmp_name'] = $file['tmp_name'][$i];
                        $fileArr['error'] = $file['error'][$i];
                        $fileArr['size'] = $file['size'][$i];

                        $resName = $this->createFile($fileArr);

                        if($resName) $this->imgArr[$key][] = $resName;

                    }

                }

            }else{

                if($file['name']){

                    $resName = $this->createFile($file);

                    if($resName) $this->imgArr[$key] = $resName;

                }

            }

        }

        return $this->getFiles();

    }

    protected function createFile($file){

        $fileNameArr = explode('.', $file['name']);
        $ext = $fileNameArr[count($fileNameArr) - 1];
        unset($fileNameArr[count($fileNameArr) - 1]);

        $fileName = implode('.', $fileNameArr);

        $fileName = (new TextModify())->translit($fileName);

        $fileName = $this->checkFile($fileName, $ext);

        $fileFullName = $this->directory . $fileName;

        if($this->uploadFile($file['tmp_name'], $fileFullName))
            return $fileName;

        return false;
    }

    protected function uploadFile($tmpName, $destination){

        if(move_uploaded_file($tmpName, $destination)) return true;

        return false;
    }

    protected function checkFile($fileName, $ext, $fileLastName = ''){

        if(!file_exists($this->directory . $fileName . $fileLastName . '.' . $ext))
               return $fileName . $fileLastName . '.' . $ext;

        return $this->checkFile($fileName, $ext, '_' . hash('crc32', time() . mt_rand(1, 1000)));

    }

    public function getFiles(){

        return $this->imgArr;

    }

}