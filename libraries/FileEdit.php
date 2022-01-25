<?php

namespace libraries;


class FileEdit
{

    protected $imgArr = [];
    protected $directory;


    public function addFile($directory = false){

        if($directory) $this->directory = $_SERVER['DOCUMENT_ROOT'] . PATH . UPLOAD_DIR;
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

    }

    public function getFiles(){

        return $this->imgArr;

    }

}