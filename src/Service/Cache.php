<?php

namespace SNOWGIRL_CORE\Service;

use SNOWGIRL_CORE\App;
use SNOWGIRL_CORE\Exception;
use SNOWGIRL_CORE\Service;
use SNOWGIRL_CORE\Helper\Arrays;

abstract class Cache extends Service
{
    use ToggleTrait;

    protected function initialize2(App $app = null)
    {
        parent::initialize2($app);

        if (null !== $app) {
            $this->setOption('logger', $app->services->logger)
                ->setOption('dir', $app->dirs['@tmp']);
        }
    }

    protected $lifetime = 3600;
    protected $isAutomaticCleaning = false;
    protected $automaticCleaningFactor = 0;
    protected $globalPrefix;
    protected $prefix;
    protected $dir;

    //runtime cache
    protected $frontend = [];

    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
        return $this;
    }

    public function getTempPrefix()
    {
        $file = $this->getPrefixFileName();

        if (file_exists($file) && $tmp = trim(file_get_contents($file))) {
            return $tmp;
        }

        return 0;
    }

    protected function getPrefixFileName()
    {
        return $this->dir . '/' . strtolower($this->getProviderName()) . '_prefix.txt';
    }

    protected $setOperation = true;

    public function disableSetOperation()
    {
        $this->setOperation = false;
        return $this;
    }

    protected $getOperation = true;

    public function disableGetOperation()
    {
        $this->getOperation = false;
        return $this;
    }

    abstract protected function _set($id, $data, $tags, $lifetime);

    public function set($k, $v, $tags = [], $lifetime = false)
    {
        $this->frontend[$k] = $v;

        if (!$this->enabled) {
            return false;
        }

        if (!$this->setOperation) {
            return false;
        }

        $id = $this->buildId($k);
//        $this->validateIdOrTag($id);

        if (is_string($tags)) {
            $tags = [$tags];
        }

//        $this->validateTagsArray($tags);

        $data = serialize($v);

        if ($this->automaticCleaningFactor > 0 && 1 == rand(1, $this->automaticCleaningFactor)) {
            $this->log('set - automatic cleaning running');
            //@todo...
//                $this->clean(Zend_Cache::CLEANING_MODE_OLD);
        }

        $this->log('set \'' . $k . '\'');
        $result = $this->_set($id, $data, $tags, $this->buildLifetime($lifetime));

        if (!$result) {
            $this->delete($k);
            return false;
        }

        return true;
    }

    abstract protected function _setMulti($idToData, $lifetime);

    public function setMulti(array $k2v, $lifetime = false)
    {
        $this->frontend = array_merge($this->frontend, $k2v);

        if (!$this->enabled) {
            return false;
        }

        if (!$this->setOperation) {
            return false;
        }

        $idK2v = [];

        foreach ($k2v as $k => $v) {
            $id = $this->buildId($k);
//            self::validateIdOrTag($id);
            $idK2v[$id] = serialize($v);
        }

        $this->log('set \'' . implode(', ', array_keys($k2v)) . '\'');
        $results = $this->_setMulti($idK2v, $this->buildLifetime($lifetime));

        array_walk($results, function ($i, $k) {
            if (!$i) {
                $this->log('set - failed to save item \'' . $k . '\' -> removing it');
                $this->delete($k);
            }
        });

        return $results;
    }

    abstract protected function _get($id);

    public function get($k)
    {
        if (Arrays::_isset($k, $this->frontend)) {
            return $this->frontend[$k];
        }

        if (!$this->enabled) {
            return false;
        }

        if (!$this->getOperation) {
            return false;
        }

        $id = $this->buildId($k);
//        $this->validateIdOrTag($id);

        $this->log('get \'' . $k . '\'');
        $data = $this->_get($id);

        if (false === $data) {
            return false;
        }

        $data = unserialize($data);

        $this->frontend[$k] = $data;

        return $data;
    }

    /**
     * Should keep internal $id order
     *
     * @param $id
     *
     * @return mixed
     */
    abstract protected function _getMulti($id);

    /**
     * @param array $k
     *
     * @return array
     */
    public function getMulti(array $k)
    {
        $k2fetch = [];

        foreach ($k as $key) {
            if (!Arrays::_isset($key, $this->frontend)) {
                $k2fetch[] = $key;
            }
        }

        if (0 == count($k2fetch)) {
            $data = [];

            foreach ($k as $key) {
                $data[$key] = $this->frontend[$key];
            }

            return $data;
        }

        if (!$this->enabled || !$this->getOperation) {
            $data = [];

            foreach ($k as $key) {
                $data[$key] = in_array($key, $k2fetch) ? false : (Arrays::_isset($key, $this->frontend) ? $this->frontend[$key] : false);
            }

            return $data;
        }

        $id = array_map(function ($i) {
            return $this->buildId($i);
        }, $k2fetch);

        $k2id = array_combine($k2fetch, $id);

        $this->log('get \'' . implode(', ', $k2fetch) . '\'');
        $source = $this->_getMulti($id);

        $source = array_map(function ($i) {
            return $i === false ? false : unserialize($i);
        }, $source);

        $data = [];

        foreach ($k as $key) {
            if (Arrays::_isset($key, $this->frontend)) {
                $data[$key] = $this->frontend[$key];
            } else {
                $data[$key] = $this->frontend[$key] = $source[$k2id[$key]];
            }
        }

        return $data;
    }

    abstract protected function _delete($id);

    public function delete($k)
    {
        if (Arrays::_isset($k, $this->frontend)) {
            unset($this->frontend[$k]);
        }

        if (!$this->enabled) {
            return false;
        }

        $id = $this->buildId($k);

        $this->log('delete \'' . $k . '\'');

        return $this->_delete($id);
    }

    abstract protected function _clean($tags);

    public function clean(array $tags = null)
    {
        if (null === $tags) {
            return $this->flush();
        }

        if (!$this->enabled) {
            return false;
        }

//        $this->validateTagsArray($tags);

        $this->log('clean \'' . implode(', ', $tags) . '\'');

        return $this->_clean($tags);
    }

    abstract protected function _flush();

    public function flush()
    {
        //...removed coz of admin do not use cache but need to have opp to drop it...

//        if (!$this->enabled) {
//            return false;
//        }

        $this->log('flush');

        $this->frontend = [];

        return $this->_flush();
    }

    public function rotate()
    {
        return $this->flush();
    }

    abstract protected function _test($id);

    public function test($k)
    {
        if (!$this->enabled) {
            return false;
        }

        $id = $this->buildId($k);
//        $this->validateIdOrTag($id);

        $this->log('test \'' . $k . '\'');

        return $this->_test($id);
    }

    protected function validateIdOrTag($string)
    {
        if (!is_string($string)) {
            new Exception('Invalid id or tag : must be a string');
        }

        if (substr($string, 0, 9) == 'internal-') {
            new Exception('"internal-*" ids or tags are reserved');
        }

        if (!preg_match('~^[a-zA-Z0-9_]+$~D', $string)) {
            new Exception('Invalid id or tag \'' . $string . '\' : must use only [a-zA-Z0-9_]');
        }
    }

    /**
     * @param $tags
     */
    protected function validateTagsArray($tags)
    {
        if (!is_array($tags)) {
            throw new Exception('Invalid tags array : must be an array');
        }

        foreach ($tags as $tag) {
            $this->validateIdOrTag($tag);
        }

        reset($tags);
    }

    protected function buildId($id)
    {
        return $this->prefix . $id;
    }

    public function call($k, \Closure $v, $tags = [], $lifetime = false)
    {
        if (false !== ($r = $this->get($k))) {
            return $r;
        }

        $this->set($k, $r = $v(), $tags, $lifetime);
        return $r;
    }

    abstract protected function _setWithTag($id, $tagId, $data);

    public function setWithTag($k, $tag, $v)
    {
        if (!$this->enabled) {
            return false;
        }

        $id = $this->buildId($k);
//        $this->validateIdOrTag($id);

        $tagId = $this->buildId($tag);
//        $this->validateIdOrTag($tagId);

        $data = serialize($v);

        $this->log('setWithTag \'' . $k . '\' \'' . $tag . '\'');
        $this->_setWithTag($id, $tagId, $data);

        return true;
    }

    abstract protected function _getWithTag($id, $tagId);

    public function getWithTag($k, $tag)
    {
        if (!$this->enabled) {
            return false;
        }

        $id = $this->buildId($k);
//        $this->validateIdOrTag($id);

        $tagId = $this->buildId($tag);
//        $this->validateIdOrTag($tagId);

        $this->log('getWithTag \'' . $k . '\' \'' . $tag . '\'');
        $data = $this->_getWithTag($id, $tagId);

        if (false === $data) {
            return false;
        }

        return unserialize($data);
    }

    public function callWithTag($tag, $key, \Closure $v)
    {
        if (false !== ($r = $this->getWithTag($key, $tag))) {
            return $r;
        }

        $this->setWithTag($key, $tag, $r = $v());
        return $r;
    }

    abstract protected function _flushByTag($tagId);

    public function flushByTag($tag)
    {
        if (!$this->enabled) {
            return false;
        }

        $tagId = $this->buildId($tag);
//        $this->validateIdOrTag($tagId);

        $this->log('flushByTag \'' . $tag . '\'');
        $isOk = $this->_flushByTag($tagId);

        return $isOk;
    }

    protected function buildLifetime($v = false)
    {
        return $v ?: $this->lifetime ?: 3600;
    }

    public function prefetch(array $k)
    {
        $this->frontend = array_merge($this->frontend, $this->getMulti($k));
        return $this;
    }

    /**
     * Universal smart function
     * Returns items from Cache or throw $itemFetcher, result as (id1 => val1, id2 => val2, ...)
     *
     * @param array $rawK
     * @param       $keyGenerator - fn(ID) {return string}
     * @param       $itemFetcher  - fn(IDs) {return array(id1 => item1, ...)}
     *
     * @return array
     */
    public function findMany(array $rawK, $keyGenerator, $itemFetcher)
    {
        if (!$rawK) {
            return [];
        }

        $rawK = array_unique($rawK);
        $items = $k = $rawK2fetch = [];

        foreach ($rawK as $rawKey) {
            $k[] = call_user_func($keyGenerator, $rawKey);
        }

        $k2rawK = array_combine($k, $rawK);
        $srcCache = $this->getMulti($k);

        foreach ($k as $key) {
            if (array_key_exists($key, $srcCache) && false !== $srcCache[$key]) {
                $items[$k2rawK[$key]] = $srcCache[$key];
            } else {
                $rawK2fetch[] = $k2rawK[$key];
            }
        }

        if ($rawK2fetch) {
            $key2v = $existing = [];
            $id2k = array_combine($rawK, $k);

            foreach (call_user_func($itemFetcher, $rawK2fetch) as $id => $item) {
                $existing[] = $id;
                $items[$id] = $item;
                $key2v[$id2k[$id]] = $item;
            }

            foreach (array_diff($rawK2fetch, $existing) as $id) {
                $items[$id] = null;
                $key2v[$id2k[$id]] = null;
            }

            $this->setMulti($key2v);
            $ordered = [];

            foreach ($rawK as $id) {
                $ordered[$id] = $items[$id];
            }

            $items = $ordered;
        }

        return $items;
    }

    public function findManyWithArgs(array $rawK, $keyGenerator, $itemFetcher, array $args)
    {
        if (!$rawK) {
            return [];
        }

        $rawK = array_unique($rawK);
        $items = $k = $rawK2fetch = [];

        $tmp = $args;
        array_unshift($tmp, $keyGenerator);

        foreach ($rawK as $rawKey) {
            $tmp2 = $tmp;
            $tmp2[] = $rawKey;
            $k[] = call_user_func(...$tmp2);
        }

        $k2rawK = array_combine($k, $rawK);
        $srcCache = $this->getMulti($k);

        foreach ($k as $key) {
            if (array_key_exists($key, $srcCache) && false !== $srcCache[$key]) {
                $items[$k2rawK[$key]] = $srcCache[$key];
            } else {
                $rawK2fetch[] = $k2rawK[$key];
            }
        }

        if ($rawK2fetch) {
            $key2v = $existing = [];
            $id2k = array_combine($rawK, $k);

            $tmp = $args;
            array_unshift($tmp, $itemFetcher);
            $tmp[] = $rawK2fetch;

            foreach (call_user_func(...$tmp) as $id => $item) {
                $existing[] = $id;
                $items[$id] = $item;
                $key2v[$id2k[$id]] = $item;
            }

            foreach (array_diff($rawK2fetch, $existing) as $id) {
                $items[$id] = null;
                $key2v[$id2k[$id]] = null;
            }

            $this->setMulti($key2v);
            $ordered = [];

            foreach ($rawK as $id) {
                $ordered[$id] = $items[$id];
            }

            $items = $ordered;
        }

        return $items;
    }
}