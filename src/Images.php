<?php

namespace SNOWGIRL_CORE;

class Images
{
    public const EXTENSION = 'jpg';

    public const FORMAT_NONE = 0;
    public const FORMAT_HEIGHT = 1;
    public const FORMAT_WIDTH = 2;
    public const FORMAT_CAPTION = 3;
    public const FORMAT_AUTO = 4;

    public static $formats = [
        self::FORMAT_NONE,
        self::FORMAT_HEIGHT,
        self::FORMAT_WIDTH,
        self::FORMAT_CAPTION,
        self::FORMAT_AUTO
    ];

    protected $app;

    public function __construct(AbstractApp $app)
    {
        $this->app = $app;
    }

    public function get($file)
    {
        return new Image($file);
    }

    public function isLocalHash(Image $image)
    {
        if (null === $image->getIsLocalHash()) {
            //@todo improve (check extension...)
            $image->setIsLocalHash(32 == strlen($image->getFile()));
        }

        return $image->getIsLocalHash();
    }

    public function isLocalNonHash(Image $image)
    {
        if ($tmp = parse_url($image->getFile())) {
            if (!isset($tmp['host'])) {
                return true;
            }
        }

        return false;
    }

    public function isLocal(Image $image)
    {
        return $this->isLocalHash($image) || $this->isLocalNonHash($image);
    }

    protected function getInfo(Image $image)
    {
        if (null === $image->getInfo()) {
            if ($this->isLocalHash($image)) {
                $file = $this->getServerNameByHash($image->getFile());
            } elseif ($this->isLocalNonHash($image)) {
                $file = str_replace($this->app->config('domains.static'), '', $image->getFile());
                $file = $this->app->dirs['@public'] . $file;
            } else {
                $file = $image->getFile();
            }

            $info = getimagesize($file);
            $image->setInfo(is_array($info) ? $info : []);
        }

        return $image->getInfo();
    }

    public function getWidth(Image $image)
    {
        return $this->getInfo($image)[0] ?? null;
    }

    public function getHeight(Image $image)
    {
        return $this->getInfo($image)[1] ?? null;
    }

    public function getMime(Image $image)
    {
        return $this->getInfo($image)['mime'] ?? null;
    }

    protected static function normalizeParam($format = self::FORMAT_NONE, $param = 0)
    {
        if (in_array($format, [self::FORMAT_CAPTION, self::FORMAT_AUTO]) && is_array($param)) {
            $tmp = [];

            if (isset($param['height'])) {
                $tmp[] = $param['height'];
            } elseif (isset($param[0])) {
                $tmp[] = $param[0];
            }

            if (isset($param['width'])) {
                $tmp[] = $param['width'];
            } elseif (isset($param[1])) {
                $tmp[] = $param[1];
            }

            $param = implode('-', $tmp);
        }

        return $param;
    }

    public function getHashServerPath($format = self::FORMAT_NONE, $param = 0)
    {
        return implode('/', [
            $this->app->dirs['@public'],
            'img',
            $format,
            $this->normalizeParam($format, $param)
        ]);
    }

    public function getServerNameByHash($hash, $format = self::FORMAT_NONE, $param = 0)
    {
        return $this->getServerNameByName($hash . '.' . self::EXTENSION, $format, $param);
    }

    public function getServerNameByName($name, $format = self::FORMAT_NONE, $param = 0)
    {
        return implode('/', [
            $this->getHashServerPath($format, $param),
            $name
        ]);
    }

    public function getLink(Image $image, $format = self::FORMAT_NONE, $param = 0, $domain = false)
    {
        if ($this->isLocalHash($image)) {
            return $this->app->router->makeLink('image', [
                'format' => $format,
                'param' => $this->normalizeParam($format, $param),
                'file' => $image->getFile() . '.' . self::EXTENSION
            ], $domain);
        }

        if ($this->isLocalNonHash($image)) {
            return $this->app->router->makeLink('default', [
                'action' => $image->getFile()
            ], $domain);
        }

        return $image->getFile();
    }

    public function getLinkByFile($file, $format = self::FORMAT_NONE, $param = 0, $domain = false)
    {
        return $this->getLink($this->get($file), $format, $param, $domain);
    }

    public function delete(Image $image, &$error = null)
    {
        try {
            if ($this->isLocalHash($image)) {
                $file = $image->getFile() . '.' . self::EXTENSION;

                foreach (self::$formats as $format) {
                    foreach (glob(implode('/', [
                        $this->app->dirs['@public'],
                        'img',
                        $format,
                        '*'
                    ])) as $dir) {
                        if (is_file($f = implode('/', [$dir, $file]))) {
                            unlink($f);
                        }
                    }
                }
            } elseif ($this->isLocalNonHash($image)) {
                false || false;
            }

            return true;
        } catch (\Exception $ex) {
            $error = $ex->getMessage();
            return false;
        }
    }

    public function deleteByFile($file, &$error = null)
    {
        return $this->delete($this->get($file), $error);
    }

    public function deleteLocal($image): int
    {
        $aff = 0;

        foreach (glob($this->app->dirs['@public'] . '/img/*/*/' . $image . '.' . self::EXTENSION) as $file) {
            if (unlink($file)) {
                $aff++;
            }
        }

        return $aff;
    }

    public function getLocalByFile($file): string
    {
//        return substr(basename($file), 0, 32);
        return str_replace('.' . self::EXTENSION, '', basename($file));
    }

    public function getAllLocalFiles(): array
    {
        return glob($this->app->dirs['@public'] . '/img/0/0/*.' . self::EXTENSION);
    }

    protected function optimizeImagick($imagick, $quality = 70)
    {
        /** @var \Imagick $imagick */
        $imagick->stripImage();
        $imagick->setImageCompression(\Imagick::COMPRESSION_JPEG);
        $imagick->setImageCompressionQuality($quality);
        $imagick->setImageInterlaceScheme(\Imagick::INTERLACE_PLANE);
        $imagick->setInterlaceScheme(\Imagick::INTERLACE_PLANE);
//        $imagick->gaussianBlurImage(0.8, 10);
    }

    public function getOptimizePrefix()
    {
        return 'opt_';
    }

    /**
     * @param            $file
     * @param int $quality
     * @param bool|false $replace
     *
     * @return array|bool|string
     */
    public function optimize($file, $quality = 85, $replace = false)
    {
        if (is_array($file)) {
            $output = [];

            foreach ($file as $_) {
                $output[$_] = $this->optimize($_, $quality, $replace);
            }

            return $output;
        }

        try {
            $imagick = new \Imagick($file);
            $this->optimizeImagick($imagick, $quality);

            if ($replace) {
                $output = $file;
            } else {
                $output = dirname($file) . '/' . $this->getOptimizePrefix() . basename($file);
            }

            $v = true === $imagick->writeImage($output);
            $imagick->destroy();

            return $v ? $output : false;
        } catch (\ImagickException $ex) {
            return false;
        }
    }

    public function getHash($target)
    {
        return md5($target);
    }

    public function getFileName($target, $hash = null)
    {
        $hash = $hash ?: $this->getHash($target);
        return $hash . '.' . self::EXTENSION;
    }

    /**
     * @todo add HEIGHT x WIDTH to hash name
     *
     * @param      $target
     * @param null $hash
     * @param null $error
     *
     * @return bool|string
     */
    public function download($target, $hash = null, &$error = null)
    {
        try {
            $hash = $hash ?: $this->getHash($target);
            $pathName = $this->getServerNameByHash($hash);

            $imagick = new \Imagick($target);
            $this->optimizeImagick($imagick);

            if ('image/png' == $imagick->getImageMimeType()) {
//            if (('image/png' == $imagick->getImageMimeType() && (\Imagick::COLORSPACE_RGB == $imagick->getColorspace()))) {
                $width = $imagick->getImageWidth();
                $height = $imagick->getImageHeight();
                $white = new \Imagick();
                $white->newImage($width, $height, 'white');
                $white->compositeimage($imagick, \Imagick::COMPOSITE_OVER, 0, 0);
                $imagick = $white;
            }

            $imagick->setImageFormat(self::EXTENSION);

            if (true === $imagick->writeImage($pathName)) {
//                $v = $hash;

                if (0 < filesize($pathName)) {
                    $v = $hash;
                } else {
                    unlink($pathName);
                    $v = false;
                }
            } else {
                $v = false;
            }

            $imagick->destroy();
            return $v;
        } catch (\Exception $ex) {
            $error = $ex->getMessage();
            return false;
        }
    }

    public function downloadWithCurl($uri, $hash = null, &$error = null)
    {
        try {
            $hash = $hash ?: $this->getHash($uri);

            $local = implode('/', [
                $this->app->dirs['@tmp'],
                'imp_curl_' . md5($uri)
            ]);

            shell_exec(implode(' ', [
                'curl -s -L',
                '"' . $uri . '"',
                '-o "' . $local . '"',
                '> /dev/null'
            ]));

            $output = $this->download($local, $hash, $error);
            unlink($local);
            return $output;
        } catch (\Exception $ex) {
            $error = $ex->getMessage();
            return false;
        }
    }

    public function downloadWithWget($uri, $hash = null, &$error = null)
    {
        try {
            $hash = $hash ?: $this->getHash($uri);

            $local = implode('/', [
                $this->app->dirs['@tmp'],
                'imp_wget_' . md5($uri)
            ]);

            shell_exec(implode(' ', [
                'wget --quiet',
                '-U "Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.8.1.6) Gecko/20070802 SeaMonkey/1.1.4"',
                '--output-document=' . $local,
                '"' . $uri . '"',
                '> /dev/null'
            ]));

            $output = $this->download($local, $hash, $error);
            unlink($local);
            return $output;
        } catch (\Exception $ex) {
            $error = $ex->getMessage();
            return false;
        }
    }

    public function downloadRemote($uri, $hash = null, &$error = null)
    {
        return $this->download($uri, $hash, $error);
    }

    public function downloadLocal(array $file, &$error = null)
    {
        if ($file['error']) {
            $error = $file['error'];
            return false;
        }

        if (!$file['size']) {
            $error = 'Size is zero';
            return false;
        }

        //@todo check ext & mime & size

//            $sourcePath = $file['tmp_name'];
//            $targetPath = "upload/" . $file['name'];
//            move_uploaded_file($sourcePath, $targetPath);

        //@todo tmp_name always different for same files.. (its means no cache..)
        return $this->download($file['tmp_name'], null, $error);
    }
}