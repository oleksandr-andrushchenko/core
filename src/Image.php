<?php

namespace SNOWGIRL_CORE;

class Image
{
    private $file;
    private $isLocalHash;
    private $infoPeaces;
    private $namePeaces;

    /**
     * @var AbstractApp
     */
    private static $app;

    public static function setApp(AbstractApp $app)
    {
        self::$app = $app;
    }

    public function __construct($file)
    {
        $this->file = $file;
    }

    public function getFile()
    {
        return $this->file;
    }

    public function getHeight(): ?int
    {
        $peaces = $this->getNamePeaces();

        if (!empty($peaces[2])) {
            return $peaces[2];
        }

        $peaces = $this->getInfoPeaces();

        if (!empty($peaces[1])) {
            return $peaces[1];
        }

        return null;
    }

    public function getWidth(): ?int
    {
        $peaces = $this->getNamePeaces();

        if (!empty($peaces[1])) {
            return $peaces[1];
        }

        $peaces = $this->getInfoPeaces();

        if (!empty($peaces[0])) {
            return $peaces[0];
        }

        return null;
    }

    public function getMime(): ?string
    {
        if ($this->isLocalHash()) {
            return 'image/jpeg';
        }

        $peaces = $this->getInfoPeaces();

        if (!empty($peaces['mime'])) {
            return $peaces['mime'];
        }

        return null;
    }

    public function isLocalHash(): bool
    {
        if (null === $this->isLocalHash) {
            //@todo improve (check extension...)
            $this->isLocalHash = 32 == strlen(explode('_', $this->file)[0]);
        }

        return $this->isLocalHash;
    }

    public function isLocalNonHash(): bool
    {
        if ($tmp = parse_url($this->file)) {
            if (!isset($tmp['host'])) {
                return true;
            }
        }

        return false;
    }

    public function isLocal(): bool
    {
        return $this->isLocalHash() || $this->isLocalNonHash();
    }

    public function getPathName(): string
    {
        return self::$app->images->getPathName(Images::FORMAT_NONE, 0, $this->file);
    }

    public function getPathNames($format, $param, $file): array
    {
        return glob(self::$app->images->getPathName($format, $param, $file));
    }

    public function __toString()
    {
        return $this->file;
    }

    public function hasDimensions(): bool
    {
        return 32 == strpos($this->file, '_');
    }

    private function getInfoPeaces(): array
    {
        if (null === $this->infoPeaces) {
            if ($this->isLocalHash()) {
                self::$app->container->logger->warning(__METHOD__);
                $file = self::$app->images->getPathName(Images::FORMAT_NONE, 0, $this->file);
            } elseif ($this->isLocalNonHash()) {
                $file = str_replace(self::$app->config('domains.static'), '', $this->file);
                $file = self::$app->dirs['@public'] . $file;
            } else {
                $file = $this->file;
            }

            $this->infoPeaces = self::$app->images->getDimensionsByPath($file) ?: [];
        }

        return $this->infoPeaces;
    }

    private function getNamePeaces(): array
    {
        if (null === $this->namePeaces) {
            if ($this->hasDimensions()) {
                $tmp = explode('_', $this->file);
                $this->namePeaces = array_merge([$tmp[0]], explode('x', $tmp[1]));
            } else {
                $this->namePeaces = [];
            }
        }

        return $this->namePeaces;
    }
}
