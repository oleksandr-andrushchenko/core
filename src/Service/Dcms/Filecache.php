<?php

namespace SNOWGIRL_CORE\Service\Dcms;

use SNOWGIRL_CORE\Exception;
use SNOWGIRL_CORE\Helper\FS as FsHelper;
use SNOWGIRL_CORE\Service\Dcms;
use SNOWGIRL_CORE\Service\Logger;

class Filecache extends Dcms
{
    protected $dir;
    protected $fileNamePrefix = 'cache';
    protected $metaDataArrayMaxSize = 100;
    protected $directoryPerm = 0700;
    protected $filePerm = 0600;
    protected $readControl = false;
    protected $readControlType = 'crc32';
    protected $directoryLevel = 0;
    protected $fileLocking = true;
    protected $metaDataArray = [];
    protected $isAutomaticCleaning = true;
    protected $automaticCleaningFactor = 10;

    public function __construct($config)
    {
        parent::__construct($config);

        $this->dir = '/' . trim($this->dir, '/') . '/';

        if ($this->fileNamePrefix && !preg_match('~^[a-zA-Z0-9_]+$~D', $this->fileNamePrefix)) {
            throw new Exception('Invalid fileNamePrefix : must use only [a-zA-Z0-9_]');
        }

        if ($this->metaDataArrayMaxSize < 10) {
            throw new Exception('Invalid metaDataArrayMaxSize, must be > 10');
        }
    }

    protected function getDir($path = null)
    {
        return $this->dir . ($path ?: '');
    }

    protected function _set($id, $data, $tags, $lifetime)
    {
        clearstatcache();
        $file = $this->file($id);
        $path = $this->path($id);

        if ($this->directoryLevel > 0) {
            if (!is_writable($path)) {
                $this->recursiveMkdirAndChmod($id);
            }

            if (!is_writable($path)) {
                return false;
            }
        }

        if ($this->readControl) {
            $hash = $this->hash($data);
        } else {
            $hash = '';
        }

        $res = $this->setMeta($id, [
            'hash' => $hash,
            'mtime' => time(),
            'expire' => $this->expireTime($lifetime),
            'tags' => $tags
        ]);

        if (!$res) {
            $this->log('_set - error on saving metadata', Logger::TYPE_WARN);
            return false;
        }

        $output = $this->filePutContents($file, $data);

        return $output;
    }

    protected function _setMulti($idToData, $lifetime)
    {
        $output = [];

        foreach ($idToData as $id => $data) {
            $output[$id] = $this->set($id, $data, [], $lifetime);
        }

        return $output;
    }

    protected function _get($id)
    {
        $testValidity = true;

        if (!$this->___test($id)) {
            return false;
        }

        $meta = $this->getMeta($id);
        $file = $this->file($id);
        $data = $this->fileGetContents($file);

        if ($this->readControl && ($this->hash($data) != $meta['hash'])) {
            $this->log('_get  - read_control - stored hash and computed hash do not match', Logger::TYPE_WARN);
            $this->delete($id);
            return false;
        }

        return $data;
    }

    protected function _getMulti($id)
    {
        $output = [];

        foreach ($id as $kid) {
            $output[$kid] = $this->get($id);
        }

        return $output;
    }

    protected function ___test($id, $testValidity = true)
    {
        if (!$meta = $this->getMeta($id)) {
            return false;
        }

        if (!$testValidity || (time() <= $meta['expire'])) {
            return $meta['mtime'];
        }

        return false;
    }

    protected function _test($id)
    {
        clearstatcache();
        return $this->___test($id, true);
    }

    protected function deleteFile($file)
    {
        if (!is_file($file)) {
            return false;
        }

        if (!@unlink($file)) {
            $this->log("deleteFile - we can't remove $file", Logger::TYPE_WARN);
            return false;
        }

        return true;
    }

    protected function _delete($id)
    {
        $file = $this->file($id);
        $boolRemove = $this->deleteFile($file);
        $boolMetadata = $this->deleteMeta($id);
        return $boolMetadata && $boolRemove;
    }

    protected function ___clean($dir, $mode, $tags = [])
    {
        if (!is_dir($dir)) {
            return false;
        }

        $result = true;
        $glob = @glob($dir . $this->fileNamePrefix . '--*');

        if (false === $glob) {
            return true;
        }

        $metadataFiles = [];

        foreach ($glob as $file) {
            if (is_file($file)) {
                $fileName = basename($file);

                if ($this->isMetaFile($fileName)) {
                    if (static::CLEANING_MODE_ALL == $mode) {
                        $metadataFiles[] = $file;
                    }

                    continue;
                }

                $id = $this->fileNameToId($fileName);
                $meta = $this->getMeta($id);

                if (false === $meta) {
                    $meta = ['expire' => 1, 'tags' => []];
                }

                switch ($mode) {
                    case (static::CLEANING_MODE_ALL):
                        $result = $result && $this->_delete($id);
                        break;
                    case (static::CLEANING_MODE_OLD):
                        if (time() > $meta['expire']) {
                            $result = $this->_delete($id) && $result;
                        }
                        break;
                    case (static::CLEANING_MODE_MATCHING_ANY_TAG):
                        $matching = false;

                        foreach ($tags as $tag) {
                            if (in_array($tag, $meta['tags'])) {
                                $matching = true;
                                break;
                            }
                        }

                        if ($matching) {
                            $result = $this->_delete($id) && $result;
                        }
                        break;
                    default:
                        throw new Exception('Invalid mode for ___clean() method');
                        break;
                }
            }

            if (is_dir($file) && $this->directoryLevel > 0) {
                $result = $this->___clean($file . DIRECTORY_SEPARATOR, $mode, $tags) && $result;

                if (static::CLEANING_MODE_ALL == $mode) {
                    @rmdir($file);
                }
            }
        }

        foreach ($metadataFiles as $file) {
            if (file_exists($file)) {
                $result = $this->deleteFile($file) && $result;
            }
        }

        return $result;
    }

    protected function _flush()
    {
        clearstatcache();
        return $this->___clean($this->dir, static::CLEANING_MODE_ALL);
    }

    protected function _clean($tags)
    {
        clearstatcache();
        return $this->___clean($this->dir, static::CLEANING_MODE_MATCHING_ANY_TAG, $tags);
    }

    protected function getMeta($id)
    {
        if (isset($this->metaDataArray[$id])) {
            return $this->metaDataArray[$id];
        }

        $file = $this->getMetaFile($id);

        if (!$result = $this->fileGetContents($file)) {
            return false;
        }

        if (!$meta = @unserialize($result)) {
            return false;
        }

        $this->setMeta($id, $meta, false);

        return $meta;
    }

    protected function setMeta($id, $meta, $save = true)
    {
        if (count($this->metaDataArray) >= $this->metaDataArrayMaxSize) {
            $this->metaDataArray = array_slice($this->metaDataArray, (int)($this->metaDataArrayMaxSize / 10));
        }

        if ($save) {
            $file = $this->getMetaFile($id);

            if (!$this->filePutContents($file, serialize($meta))) {
                return false;
            }
        }

        $this->metaDataArray[$id] = $meta;
        return true;
    }

    protected function deleteMeta($id)
    {
        if (isset($this->metaDataArray[$id])) {
            unset($this->metaDataArray[$id]);
        }

        $file = $this->getMetaFile($id);
        return $this->deleteFile($file);
    }

    protected function getMetaFile($id)
    {
        return $this->path($id) . $this->idToFileName('im---' . $id);
    }

    protected function isMetaFile($fileName)
    {
        return substr($this->fileNameToId($fileName), 0, 5) == 'im---';
    }

    protected function expireTime($lifetime)
    {
        if (null === $lifetime) {
            return 9999999999;
        }

        return time() + $lifetime;
    }

    protected function hash($data)
    {
        switch ($this->readControlType) {
            case 'md5':
                return md5($data);
            case 'crc32':
                return crc32($data);
            case 'strlen':
                return strlen($data);
            case 'adler32':
                return hash('adler32', $data);
            default:
                throw new Exception("Incorrect hash function : {$this->readControlType}");
        }
    }

    protected function idToFileName($id)
    {
        return $this->fileNamePrefix . '---' . $id;
    }

    protected function file($id)
    {
        return $this->path($id) . $this->idToFileName($id);
    }

    protected function path($id, $parts = false)
    {
        $partsArray = [];
        $root = $this->dir;

        if ($this->directoryLevel > 0) {
            $hash = hash('adler32', $id);

            for ($i = 0; $i < $this->directoryLevel; $i++) {
                $root = $root . $this->fileNamePrefix . '--' . substr($hash, 0, $i + 1) . DIRECTORY_SEPARATOR;
                $partsArray[] = $root;
            }
        }

        if ($parts) {
            return $partsArray;
        }

        return $root;
    }

    protected function recursiveMkdirAndChmod($id)
    {
        if ($this->directoryLevel <= 0) {
            return true;
        }

        foreach ($this->path($id, true) as $part) {
            if (!is_dir($part)) {
                @mkdir($part, $this->directoryPerm);
                @chmod($part, $this->directoryPerm);
            }
        }

        return true;
    }

    protected function fileGetContents($file)
    {
        $result = false;

        if (!is_file($file)) {
            return false;
        }

        $f = @fopen($file, 'rb');

        if ($f) {
            if ($this->fileLocking) {
                @flock($f, LOCK_SH);
            }

            $result = stream_get_contents($f);

            if ($this->fileLocking) {
                @flock($f, LOCK_UN);
            }

            @fclose($f);
        }

        return $result;
    }

    protected function filePutContents($file, $string)
    {
        $result = false;

        $f = @fopen($file, 'ab+');

        if ($f) {
            if ($this->fileLocking) {
                @flock($f, LOCK_EX);
            }

            fseek($f, 0);
            ftruncate($f, 0);
            $tmp = @fwrite($f, $string);

            if (false !== $tmp) {
                $result = true;
            }

            @fclose($f);
        }

        @chmod($file, $this->filePerm);
        return $result;
    }

    protected function fileNameToId($fileName)
    {
        return preg_replace('~^' . $this->fileNamePrefix . '---(.*)$~', '$1', $fileName);
    }

    protected function _setWithTag($id, $tagId, $data)
    {
        $dir = $this->getDir('/' . $tagId);
        $file = $dir . '/' . $id . '.txt';

        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $isOk = file_put_contents($file, $data);
        return $isOk;
    }

    protected function _getWithTag($id, $tagId)
    {
        $dir = $this->getDir('/' . $tagId);
        $file = $dir . '/' . $id . '.txt';

        if (file_exists($file)) {
            return file_get_contents($file);
        }

        return false;
    }

    protected function _flushByTag($tagId)
    {
        return FsHelper::rmDir($this->getDir('/' . $tagId));
    }
}