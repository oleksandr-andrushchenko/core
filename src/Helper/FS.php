<?php

namespace SNOWGIRL_CORE\Helper;

class FS
{
    public static function rmDir($dir)
    {
        if (is_dir($dir)) {
            $files = array_diff(scandir($dir), ['.', '..']);

            foreach ($files as $file) {
                (is_dir("$dir/$file") && !is_link($dir)) ? self::rmDir("$dir/$file") : unlink("$dir/$file");
            }

            return rmdir($dir);
        }
        return true;
    }

    public static function rmFile($file)
    {
        if (is_file($file)) {
            return unlink($file);
        }

        return true;
    }

    public static function globRecursive($pattern, $flags = 0)
    {
        $files = glob($pattern, $flags);

        foreach (glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
            $files = array_merge($files, self::globRecursive($dir . '/' . basename($pattern), $flags));
        }

        return $files;
    }

    /**
     * @todo...
     *
     * @param $dir
     */
    public static function scanDirRecursive($dir)
    {
        return [];
    }

    public static function chmodRecursive($filename, $mode)
    {
        if (is_dir($filename)) {
            foreach (new \DirectoryIterator($filename) as $item) {
                chmod($item->getPathname(), $mode);

                if ($item->isDir() && !$item->isDot()) {
                    self::chmodRecursive($item->getPathname(), $mode);
                }
            }
        } else {
            chmod($filename, $mode);
        }
    }
}