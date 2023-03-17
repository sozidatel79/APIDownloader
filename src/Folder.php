<?php

class Folder {

    private $folder;

    public function __construct($folder) {
        $this->folder = $folder . DIRECTORY_SEPARATOR;
    }

    public function getFolder() {
        return $this->folder;
    }

    public function createFolder() {
        if(! file_exists($this->getFolder())) {
            mkdir($this->getFolder(), 777);
            chmod($this->getFolder(), 0777);
        }
        return $this;
    }
}