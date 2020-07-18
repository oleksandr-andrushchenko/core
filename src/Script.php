<?php

namespace SNOWGIRL_CORE;

use Psr\Log\LoggerInterface;

class Script
{
    protected $dir;
    protected $dirs;
    protected $aliases;
    protected $domain;

    private $name;
    private $rawContent;
    private $cache;
    private $priority;

    private $isLocal;
    private $serverName;
    private $localAlias;
    private $baseHttpPathPrefix;
    private $baseHttpPath;

    private $counter;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(string $arg, array $dirs, array $aliases, int $counter, LoggerInterface $logger, bool $raw = false, bool $cache = false, string $domain = 'master')
    {
        if ($raw) {
            $this->rawContent = $arg;
            $this->isLocal = true;
        } else {
            $this->name = $arg;
        }

        $this->cache = $cache;
        $this->priority = 9;

        $this->dirs = $dirs;
        $this->aliases = $aliases;

        $this->counter = $counter;
        $this->domain = $domain;
        $this->logger = $logger;
    }

    public function setPriority(int $priority): Script
    {
        $this->priority = $priority;

        return $this;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function isRaw(): bool
    {
        return null !== $this->rawContent;
    }

    public function getName(): string
    {
        if (null === $this->name) {
            $this->name = $this->rawContentToName();
        }

        return $this->name;
    }

    public function getServerName(): string
    {
        if (!$this->isLocal()) {
            return false;
        }

        if (null === $this->serverName) {
            $tmp = str_replace('@dir', $this->dir, $this->dirs[$this->getLocalAlias()] . '/@dir');
            $tmp = str_replace($this->getLocalAlias(), $tmp, $this->getName());

            $this->serverName = realpath($tmp);
        }

        return $this->serverName;
    }

    public function getRawHttpName(): string
    {
        return implode('/', [
            $this->domain,
            str_replace('@public', 'public/' . $this->dir, str_replace($this->getLocalAlias(), $this->getBaseHttpPathPrefix(), $this->getName()))
        ]);
    }

    public function getHttpName(bool $withHtmlTag = false, bool $minify = false): string
    {
        if (!$this->isLocal()) {
            if ($withHtmlTag) {
                return $this->addHttpHtmlTag($this->getName());
            }

            return $this->getName();
        }

        if ($minify) {
            $base = 'minify_' . $this->getModifiedTime(true) . '_' . $this->getBaseFileName();
            $name = $this->dirs['@public'] . '/' . $this->dir . '/' . $base;

            if (!file_exists($name)) {
                file_put_contents($name, $this->minifyContent($this->rawContentToTmpPath(file_get_contents($this->getServerName()))));
            }

            $tmp = (new static('@public/' . $base))->getRawHttpName();
        } else {
            $tmp = $this->getRawHttpName();
        }

        $tmp = $this->fixLocalHttpName($tmp);

        if ($withHtmlTag) {
            $tmp = $this->addHttpHtmlTag($tmp);
        }

        return $tmp;
    }

    public function minifyContent(string $content): string
    {
        return $content;
    }

    public function minify(string $file, string $to = null): string
    {
        return file_put_contents($to ?: $file, $this->minifyContent(file_get_contents($file)));
    }

    public function getRawContent(): string
    {
        if (null === $this->rawContent) {
            $this->rawContent = file_get_contents($this->getServerName());
        }

        return $this->rawContent;
    }

    public function getBaseFileName(): string
    {
        return basename($this->getName());
    }

    public function getHttpContent(bool $withHtmlTag = false): string
    {
        if ($this->cache) {
            $name = 'http_' . $this->getModifiedTime(true) . '_' . $this->getBaseFileName();
            $name = $this->dirs['@public'] . '/' . $this->dir . '/' . $name;
        } else {
            $name = null;
        }

        if ($this->cache && $name && file_exists($name)) {
            $content = file_get_contents($name);
        } else {
            $content = $this->getRawContent();
            $content = $this->rawContentToHttp($content);
            $content = $this->minifyContent($content);

            if ($this->cache) {
                file_put_contents($name, $content);
            }
        }

        if ($withHtmlTag) {
            $content = $this->addContentHtmlTag($content);
        }

        return $content;
    }

    public function getUniqueHash(bool $local = false): string
    {
        if ($local || $this->isLocal()) {
            $file = $this->getServerName();
        } else {
            $file = $this->getHttpName();
        }

        return md5(md5_file($file) . filesize($file));
    }

    public function getDomainName(): ?string
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
    public function stringify(bool $withHtmlTag = true): string
    {
        //this should be  universal render function...
        //depends on rawContent, isLocal and cache...

        if (null === $this->rawContent) {
            return $this->getHttpName($withHtmlTag);
        }

        return $this->getHttpContent($withHtmlTag);
    }

    public function output(bool $withHtmlTag = true): string
    {
        return $this->stringify($withHtmlTag);
    }

    public function __toString()
    {
        return $this->stringify();
    }

    protected function getBaseHttpPath(): string
    {
        if (null === $this->baseHttpPath) {
            $tmp = [];
            $tmp[] = $this->getBaseHttpPathPrefix();
            $tmp2 = explode('/', $this->getName());
            array_shift($tmp2);
            array_pop($tmp2);
            $tmp = array_merge($tmp, $tmp2);
            $tmp = implode('/', $tmp);

            $this->baseHttpPath = $tmp;
        }

        return $this->baseHttpPath;
    }

    private function isLocal(): bool
    {
        if (null === $this->isLocal) {
            $this->isLocal = 0 === strpos($this->getName(), '@');
        }

        return $this->isLocal;
    }

    /**
     * @todo need to test...
     *
     * @param bool|false $local
     *
     * @return bool|int|mixed
     */
    private function getModifiedTime(bool $local = false)
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

    private function fixLocalHttpName(string $name): string
    {
        return $name . (false === strpos($name, '?') ? '?' : '&') . 'counter=' . $this->counter;
    }

    private function getLocalAlias(): string
    {
        if (!$this->isLocal()) {
            return '';
        }

        if (null === $this->localAlias) {
            $this->localAlias = '';

            foreach ($this->aliases as $alias) {
                if (false !== strpos($this->getName(), $alias)) {
                    $this->localAlias = $alias;
                }
            }
        }

        return $this->localAlias;
    }

    private function getBaseHttpPathPrefix(): string
    {
        if (null === $this->baseHttpPathPrefix) {
            $this->baseHttpPathPrefix = str_replace('@dir', $this->dir, '@dir/' . ltrim($this->getLocalAlias(), '@'));
        }

        return $this->baseHttpPathPrefix;
    }

    private function rawContentToName(): string
    {
        $hash = md5($this->getRawContent());

        if (!file_exists($this->dirs['@public'] . '/' . $this->dir . '/' . ($v = $hash . '.' . $this->dir))) {
            $file = $this->dirs['@public'] . '/' . $this->dir . '/' . $v;

            if (!file_put_contents($file, $this->getRawContent())) {
                $this->logger->error("can't save to $file");

                return '';
            }
        }

        return '@public/' . $v;
    }

    private function rawContentToTmpPath(string $content): string
    {
        return $content;
    }

    private function getLocalDomainScheme(): ?string
    {
        if (is_array($tmp = parse_url($this->domain)) && isset($tmp['scheme'])) {
            return $tmp['scheme'];
        }

        return null;
    }

    private function rawContentToHttp(string $content): string
    {
        return $content;

    }

    public static function addContentHtmlTag(string $content): string
    {
        return $content;
    }

    public static function addHttpHtmlTag(string $uri): string
    {
        return $uri;
    }
}