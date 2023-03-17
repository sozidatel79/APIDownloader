<?php

class File {
    private $file;
    public function __construct($file_name, $folder = null) {
        $this->file =  !$folder ? dirname(__DIR__) . DIRECTORY_SEPARATOR . $file_name : $folder . DIRECTORY_SEPARATOR . $file_name;
        if(! file_exists($this->getFilePointer()) &&  ! strpos($this->getFilePointer(), 'coola_data')) {
            $f = fopen($this->getFilePointer(), 'w');
            fclose($f);
            $this->write(0);
        }
    }
    public function getFilePointer() {
        return $this->file;
    }
    public function read($folder = null) {
        return file_get_contents($this->getFilePointer());
    }
    public function write($value, $append = false) {
        file_put_contents($this->getFilePointer(), $value, $append);
    }
    public function writeToSeparateFiles($folder, $increment, $value) {
        file_put_contents($this->getFilePointer() . '-'. $increment, $value);
    }

}
