<?php

namespace SNOWGIRL_CORE;

class File
{
    private $name;
    private $pointer;

    public function __construct($file)
    {
        $this->name = $file;
        $this->pointer = fopen($this->name, 'w+');
    }

    public function __destruct()
    {
        $this->close();
    }

    public function size()
    {
        return filesize($this->name);
    }

    public function write($string)
    {
        return fwrite($this->pointer, $string);
    }

    public function writeNewLine()
    {
        return $this->write("\n");
    }

    public function close()
    {
        if ($this->pointer) {
            $output = fclose($this->pointer);
            $this->pointer = null;
            return $output;
        }

        return true;
    }

    public function clear()
    {
        return ftruncate($this->pointer, 0);
    }

    public function delete()
    {
        return unlink($this->name);
    }
}