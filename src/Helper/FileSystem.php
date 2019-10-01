<?php

namespace SNOWGIRL_CORE\Helper;

class FileSystem
{
    public static function isFileExists($filename): bool
    {
        clearstatcache(true, $filename);
        return file_exists($filename);
    }

    public static function isDirectory($filename): bool
    {
        clearstatcache(true, $filename);
        return is_dir($filename);
    }

    public static function deleteDirectory($dirname): bool
    {
        if (is_dir($dirname)) {
            $files = array_diff(scandir($dirname), ['.', '..']);

            foreach ($files as $file) {
                (is_dir("$dirname/$file") && !is_link($dirname)) ? self::deleteDirectory("$dirname/$file") : unlink("$dirname/$file");
            }

            return rmdir($dirname);
        }

        return true;
    }

    public static function createFile($filename, $content): bool
    {
        return false !== file_put_contents($filename, $content);
    }

    public static function deleteFile($filename, $check = false): bool
    {
        if ($check) {
            if (is_file($filename)) {
                return unlink($filename);
            }

            return true;
        }

        return unlink($filename);
    }

    public static function deleteFilesByPattern($pattern, $flags = 0): int
    {
        $aff = 0;

        foreach (glob($pattern, $flags) as $file) {
            if (self::deleteFile($file)) {
                $aff++;
            }
        }

        return $aff;
    }

    public static function globRecursive($pattern, $flags = 0): array
    {
        $files = glob($pattern, $flags);

        foreach (glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
            $files = array_merge($files, self::globRecursive($dir . '/' . basename($pattern), $flags));
        }

        return $files;
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