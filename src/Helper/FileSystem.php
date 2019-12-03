<?php

namespace SNOWGIRL_CORE\Helper;

use DateTime;

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

    public static function getRemoteFileSize(string $url): ?int
    {
        $curl = curl_init($url);

        curl_setopt($curl, CURLOPT_NOBODY, true);
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.102 Safari/537.36");

        $data = curl_exec($curl);

        if ($data === false) {
            return null;
        }

        curl_close($curl);

        if ($data && preg_match("/^HTTP\/1\.[01] (\d\d\d)/", $data, $matches)) {
            $status = (int)$matches[1];

            if (preg_match("/Content-Length: (\d+)/", $data, $matches)) {
                if ($status == 200 || ($status > 300 && $status <= 308)) {
                    return (int)$matches[1];
                }
            }
        }

        return null;
    }

    public static function getRemoteFileLastModifiedTime1(string $url): ?DateTime
    {
        $data = filemtime($url);

        if (false === $data) {
            return null;
        }

        return new DateTime($data);
    }

    public static function getRemoteFileLastModifiedTime2(string $url): ?DateTime
    {
        $curl = curl_init($url);

        curl_setopt($curl, CURLOPT_NOBODY, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FILETIME, true);

        $data = curl_exec($curl);

        if ($data === false) {
            return null;
        }

        $data = curl_getinfo($curl, CURLINFO_FILETIME);

        curl_close($curl);

        if ($data != -1) {
            return new DateTime($data);
        }

        return null;
    }

    public static function getRemoteFileLastModifiedTime3(string $url): ?DateTime
    {
        $data = get_headers($url, 1);

        if ($data && strstr($data[0], '200') !== false) {
            return new DateTime($data['Last-Modified']);
        }

        return null;
    }

    public static function getRemoteFileLastModifiedTime(string $url): ?DateTime
    {
        $output = self::getRemoteFileLastModifiedTime1($url);

        if (null === $output) {
            $output = self::getRemoteFileLastModifiedTime2($url);

            if (null === $output) {
                $output = self::getRemoteFileLastModifiedTime3($url);

                return $output;
            }
        }

        return $output;
    }
}