<?php

namespace SNOWGIRL_CORE;

use SNOWGIRL_CORE\Helper\FS as FsHelper;

class Script
{
    protected static $dir;
    protected static $httpMap;
    protected static $serverLinkMap;
    protected static $serverMap;

    /** @var App */
    protected static $app;

    protected $domain;

    public static function setApp(App $app)
    {
        static::$app = $app;

        static::$httpMap = [];
        static::$serverLinkMap = [];
        static::$serverMap = [];

        foreach (array_keys(self::$app->namespaces) as $alias) {
            $source = ltrim($alias, '@');
            static::$httpMap[$alias] = '@dir/' . $source;
            static::$serverLinkMap[$source . '/@dir'] = 'public/@dir/' . $source;
            static::$serverMap[$alias] = self::$app->dirs[$alias] . '/@dir';
        }
    }

    protected static function getServerLinkMap()
    {
        $tmp = [];

        foreach (self::$serverLinkMap as $k => $v) {
            $tmp[str_replace('@dir', static::$dir, $k)] = str_replace('@dir', static::$dir, $v);
        }

        return $tmp;
    }

    protected $name;
    protected $rawContent;
    protected $cache;
    protected $priority;

    /**
     * @param            $arg - relative filename [self::$app->libraryRoot.'/static::$dir',
     *                        DOCUMENT_ROOT.'/public/static::$dir'] OR raw content
     * @param bool|false $raw
     * @param bool|false $cache
     * @param bool|false $domain
     */
    public function __construct($arg, $raw = false, $cache = false, $domain = false)
    {
        if ($raw) {
            $this->rawContent = $arg;
            $this->isLocal = true;
        } else {
            $this->name = $arg;
        }

        $this->cache = $cache;
        $this->priority = 9;

        $this->domain = self::$app->config->domains->{$domain ?: 'master'};
    }

    public function setPriority($priority)
    {
        $this->priority = $priority;
        return $this;
    }

    public function getPriority()
    {
        return $this->priority;
    }

    public function isRaw()
    {
        return null !== $this->rawContent;
    }

    public function getName()
    {
        if (null === $this->name) {
            $this->name = $this->rawContentToName();
        }

        return $this->name;
    }

    protected $isLocal;

    public function isLocal()
    {
        if (null === $this->isLocal) {
            $this->isLocal = false !== strpos($this->getName(), '@');
        }

        return $this->isLocal;
    }

    protected $serverName;

    public function getServerName()
    {
        if (!$this->isLocal()) {
            return false;
        }

        if (null === $this->serverName) {
            $tmp = str_replace('@dir', static::$dir, self::$serverMap[$this->getLocalAlias()]);
            $tmp = str_replace($this->getLocalAlias(), $tmp, $this->getName());
            $this->serverName = realpath($tmp);
        }

        return $this->serverName;
    }

    protected static function fixLocalHttpName($v)
    {
        return $v . (false === strpos($v, '?') ? '?' : '&') . 'counter=' . self::$app->config->client->{static::$dir . '_counter'};
    }

    protected $localAlias;

    protected function getLocalAlias()
    {
        if (!$this->isLocal()) {
            return false;
        }

        if (null === $this->localAlias) {
            foreach (self::$httpMap as $alias => $v) {
                if (false !== strpos($this->getName(), $alias)) {
                    $this->localAlias = $alias;
                }
            }
        }

        return $this->localAlias;
    }

    public function getRawHttpName()
    {
        return implode('/', [
            $this->domain,
            str_replace('@public', 'public/' . static::$dir, str_replace($this->getLocalAlias(), $this->getBaseHttpPathPrefix(), $this->getName()))
        ]);
    }

    public function getHttpName($withHtmlTag = false, $minify = false)
    {
        if (!$this->isLocal()) {
            if ($withHtmlTag) {
                return static::addHttpHtmlTag($this->getName());
            }

            return $this->getName();
        }

        if ($minify) {
            $base = 'minify_' . $this->getModifiedTime(true) . '_' . $this->getBaseFileName();
            $name = self::$app->dirs['@public'] . '/' . static::$dir . '/' . $base;

            if (!file_exists($name)) {
                file_put_contents($name, static::minifyContent($this->rawContentToTmpPath(file_get_contents($this->getServerName()))));
            }

            $tmp = (new static('@public/' . $base))->getRawHttpName();
        } else {
            $tmp = $this->getRawHttpName();
        }

        $tmp = static::fixLocalHttpName($tmp);

        if ($withHtmlTag) {
            $tmp = static::addHttpHtmlTag($tmp);
        }

        return $tmp;
    }

    protected $baseHttpPathPrefix;

    protected function getBaseHttpPathPrefix()
    {
        return $this->baseHttpPathPrefix ?: $this->baseHttpPathPrefix = str_replace('@dir', static::$dir, self::$httpMap[$this->getLocalAlias()]);
    }

    protected $baseHttpPath;

    protected function getBaseHttpPath()
    {
        if ($this->baseHttpPath) {
            return $this->baseHttpPath;
        }

        $tmp = [];
        $tmp[] = $this->getBaseHttpPathPrefix();
        $tmp2 = explode('/', $this->getName());
        array_shift($tmp2);
        array_pop($tmp2);
        $tmp = array_merge($tmp, $tmp2);
        $tmp = implode('/', $tmp);

        return $this->baseHttpPath = $tmp;
    }

    /**
     * @param $content
     *
     * @return bool|Script
     */
    public static function createFromContent($content)
    {
        $hash = md5($content);

        if (!file_exists(self::$app->dirs['@public'] . '/' . static::$dir . '/' . ($v = $hash . '.' . static::$dir))) {
            if (!file_put_contents(self::$app->dirs['@public'] . '/' . static::$dir . '/' . $v, $content)) {
                return false;
            }
        }

        return new static('@public/' . $v);
    }

    protected function rawContentToName()
    {
        $hash = md5($this->getRawContent());

        if (!file_exists(self::$app->dirs['@public'] . '/' . static::$dir . '/' . ($v = $hash . '.' . static::$dir))) {
            if (!file_put_contents(self::$app->dirs['@public'] . '/' . static::$dir . '/' . $v, $this->getRawContent())) {
                return false;
            }
        }

        return '@public/' . $v;
    }

    protected static function getCachePath()
    {
        return self::$app->dirs['@public'] . '/' . static::$dir;
    }


    /**
     * @param Script[] $scripts
     *
     * @return Script[]
     */
    public static function createFromScripts(array $scripts)
    {
        $output = [];

        /** @var Script[] $bulkOfLocalScriptsToConcat */
        $bulkOfLocalScriptsToConcat = [];

        $sizeof = count($scripts) - 1;

        foreach ($scripts as $k => $script) {
            if ($script->isLocal()) {
                $bulkOfLocalScriptsToConcat[] = $script;
            }

            if ($bulkOfLocalScriptsToConcat && ($sizeof == $k || !$script->isLocal())) {
                $key = [];

                foreach ($bulkOfLocalScriptsToConcat as $scriptToConcat) {
                    $key[] = $scriptToConcat->getName() . filemtime($scriptToConcat->getServerName());
                }

                $key = implode('', $key);

                $file = static::getCachePath() . '/' . ($base = md5($key) . '.' . static::$dir);

                $newScript = new static('@public/' . $base);

                if (!file_exists($file)) {
                    file_put_contents($file, implode("\n\n", array_map(function ($script) {
                        /** @var Script $script */
                        return $script->getTmpMappedContent();
                    }, $scripts)));

                    static::minify($file);
                } else {
                    self::$app->services->logger->make('Script[' . static::$dir . '][' . implode(' - ', $scripts) . ']: from cache');
                }

                $output[] = $newScript;

                $bulkOfLocalScriptsToConcat = [];
            }

            if (!$script->isLocal()) {
                $output[] = $script;
            }
        }

        return $output;
    }

    /**
     * @param Script[] $scripts
     *
     * @return static
     */
    public static function _createFromScripts(array $scripts)
    {
        $key = [];

        foreach ($scripts as $v) {
            $key[] = $v->getName() . filemtime($v->getServerName());
        }

        $key = implode('', $key);

        $file = static::getCachePath() . '/' . ($tmp = md5($key) . '.' . static::$dir);

        $object = new static('@public/' . $tmp);

        if (!file_exists($file)) {
            file_put_contents($file, implode("\n\n", array_map(function ($script) {
                /** @var Script $script */
                return $script->getTmpMappedContent();
            }, $scripts)));

            static::minify($file);
        } else {
            self::$app->services->logger->make('Script[' . static::$dir . '][' . implode(' - ', $scripts) . ']: from cache');
        }

        return $object;
    }

    public static function minifyContent($content)
    {
        return $content;
    }

    public static function minify($file, $to = null)
    {
//        if (!isset($file)) {
//            return self::$app->services->logger->make('Script[' . static::$dir . ']: no file', Logger::TYPE_ERROR);
//        }
//
//        if (!file_exists($file)) {
//            return self::$app->services->logger->make('Script[' . static::$dir . ']: file[' . $file . '] not exists', Logger::TYPE_ERROR);
//        }

        return file_put_contents($to ?: $file, static::minifyContent(file_get_contents($file)));
    }

    public function getRawContent()
    {
        if (null === $this->rawContent) {
            $this->rawContent = file_get_contents($this->getServerName());
        }

        return $this->rawContent;
    }

    protected function rawContentToTmpPath($content)
    {
        return $content;
    }

    public function getTmpMappedContent()
    {
        return $this->rawContentToTmpPath($this->getRawContent());
    }

    public function getBaseFileName()
    {
        return basename($this->getName());
    }

    protected function rawContentToHttp($content)
    {
        return $content;

    }

    protected static function addContentHtmlTag($content)
    {
        return $content;
    }

    protected static function addHttpHtmlTag($uri)
    {
        return $uri;
    }

    public function getHttpContent($withHtmlTag = false)
    {
        if ($this->cache) {
            $name = 'http_' . $this->getModifiedTime(true) . '_' . $this->getBaseFileName();
            $name = self::$app->dirs['@public'] . '/' . static::$dir . '/' . $name;
        } else {
            $name = null;
        }

        if ($this->cache && $name && file_exists($name)) {
            $content = file_get_contents($name);
        } else {
            $content = $this->getRawContent();
            $content = $this->rawContentToHttp($content);
            $content = static::minifyContent($content);

            if ($this->cache) {
                file_put_contents($name, $content);
            }
        }

        if ($withHtmlTag) {
            $content = static::addContentHtmlTag($content);
        }

        return $content;
    }

    public static function dropCache()
    {
        return FsHelper::rmDir(static::getCachePath());
    }

    /**
     * @todo need to test...
     *
     * @param bool|false $local
     *
     * @return bool|int|mixed
     */
    public function getModifiedTime($local = false)
    {
        if ($local || $this->isLocal()) {
            return filemtime($this->getServerName());
        }

        $uri = $this->getHttpName();

        if ($tmp = filemtime($uri)) {
            return $tmp;
        }

        $c = curl_init($uri);

        curl_setopt($c, CURLOPT_NOBODY, true);
        curl_setopt($c, CURLOPT_HEADER, true);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($c, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($c, CURLOPT_FILETIME, true);

        if (false !== curl_exec($c)) {
            $ts = curl_getinfo($c, CURLOPT_FILETIME);

            if ($ts != -1) {
                return $ts;
            }
        }

        if ($h = get_headers($uri, 1)) {
            if (false !== strstr($h[0], '200')) {
                if (isset($h['Last-Modified'])) {
                    return strtotime($h['Last-Modified']);
                }

                foreach ($h as $k => $v) {
                    if (strtolower(trim($k)) == 'last-modified') {
                        return $v;
                    }
                }
            }
        }

        return false;
    }

    /**
     * @param bool|false $local
     *
     * @return string
     */
    public function getUniqueHash($local = false)
    {
        if ($local || $this->isLocal()) {
            $file = $this->getServerName();
        } else {
            $file = $this->getHttpName();
        }

        clearstatcache();
        return md5(md5_file($file) . filesize($file));
    }

    protected function getLocalDomainScheme()
    {
        if (is_array($tmp = parse_url($this->domain)) && isset($tmp['scheme'])) {
            return $tmp['scheme'];
        }

        return null;
    }

    public function getDomainName()
    {
        if ($this->isLocal()) {
            return $this->domain;
        }

        $uri = $this->getHttpName();

        if (is_array($tmp2 = parse_url($uri))) {
            if (isset($tmp2['host'])) {
                if (isset($tmp2['scheme'])) {
                    return $tmp2['scheme'] . '://' . $tmp2['host'];
                }

                if (0 === strpos($uri, '//')) {
                    return self::getLocalDomainScheme() . '://' . $tmp2['host'];
                }
            }
        }

        return null;
    }

    /**
     * @todo benchmark...
     * @todo optimize...
     *
     * @param bool|false $withHtmlTag
     *
     * @return string
     */
    public function stringify($withHtmlTag = true)
    {
        //this should be  universal render function...
        //depends on rawContent, isLocal and cache...

        if (null === $this->rawContent) {
            return $this->getHttpName($withHtmlTag);
        } else {
            return $this->getHttpContent($withHtmlTag);
        }
    }

    public function output($withHtmlTag = true)
    {
        return $this->stringify($withHtmlTag);
    }

    public function __toString()
    {
        return $this->stringify();
    }
}

Script::setApp(App::$instance);