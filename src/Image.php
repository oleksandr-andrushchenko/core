<?php
/**
 * Created by JetBrains PhpStorm.
 * User: snowgirl
 * Date: 30.09.15
 * Time: 14:39
 * To change this template use File | Settings | File Templates.
 */

namespace SNOWGIRL_CORE;

/**
 * Class Image
 * @package SNOWGIRL_CORE
 */
class Image
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

    protected $file;

    /** @var App */
    protected static $app;

    public static function setApp(App $app)
    {
        self::$app = $app;
    }

    /**
     * @param $file - possible formats:
     * - https://local.example.com/img/landing-top-sneakers.jpg
     * - d6001bbe2484f5b662f3d1c267577dfa
     * @todo add support for new formats...
     */
    public function __construct($file)
    {
        $this->file = $file;
    }

    protected $isHash;

    protected function isLocalHash()
    {
        if (null === $this->isHash) {
            //@todo improve (check extension...)
            $this->isHash = 32 == strlen($this->file);
        }

        return $this->isHash;
    }

    protected function isLocalNonHash()
    {
        if ($tmp = parse_url($this->file)) {
            if (!isset($tmp['host'])) {
                return true;
            }
        }

        return false;
    }

    public function isLocal()
    {
        return $this->isLocalHash() || $this->isLocalNonHash();
    }

    protected $info;

    protected function getInfo()
    {
        if (null === $this->info) {
            if ($this->isLocalHash()) {
                $file = self::getServerNameByHash($this->file);
            } elseif ($this->isLocalNonHash()) {
                $file = str_replace(self::$app->config->domains->static, '', $this->file);
                $file = self::$app->dirs['@public'] . $file;
            } else {
                $file = $this->file;
            }

            $info = getimagesize($file);
            $this->info = is_array($info) ? $info : [];
        }

        return $this->info;
    }

    public function getWidth()
    {
        return $this->getInfo()[0] ?? null;
    }

    public function getHeight()
    {
        return $this->getInfo()[1] ?? null;
    }

    public function getMime()
    {
        return $this->getInfo()['mime'] ?? null;
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

    public static function getHashServerPath($format = self::FORMAT_NONE, $param = 0)
    {
        return implode('/', [
            self::$app->dirs['@public'],
            'img',
            $format,
            self::normalizeParam($format, $param)
        ]);
    }

    public static function getServerNameByHash($hash, $format = self::FORMAT_NONE, $param = 0)
    {
        return self::getServerNameByName($hash . '.' . self::EXTENSION, $format, $param);
    }

    public static function getServerNameByName($name, $format = self::FORMAT_NONE, $param = 0)
    {
        return implode('/', [
            self::getHashServerPath($format, $param),
            $name
        ]);
    }

    public function getLink($format = self::FORMAT_NONE, $param = 0, $domain = false)
    {
        if ($this->isLocalHash()) {
            return self::$app->router->makeLink('image', [
                'format' => $format,
                'param' => self::normalizeParam($format, $param),
                'file' => $this->file . '.' . self::EXTENSION
            ], $domain);
        }

        if ($this->isLocalNonHash()) {
            return self::$app->router->makeLink('default', [
                'action' => $this->file
            ], $domain);
        }

        return $this->file;
    }

    public function stringify($format = self::FORMAT_NONE, $param = 0)
    {
        return $this->getLink($format, $param);
    }

    public function delete(&$error = null)
    {
        try {
            if ($this->isLocalHash()) {
                $file = $this->file . '.' . self::EXTENSION;

                foreach ($this->formats as $format) {
                    foreach (glob(implode('/', [
                        self::$app->dirs['@public'],
                        'img',
                        $format,
                        '*'
                    ])) as $dir) {
                        if (is_file($f = implode('/', [$dir, $file]))) {
                            unlink($f);
                        }
                    }
                }
            } elseif ($this->isLocalNonHash()) {
                false || false;
            }

            return true;
        } catch (\Exception $ex) {
            $error = $ex->getMessage();
            return false;
        }
    }

    protected static function optimizeImagick($imagick, $quality = 85)
    {
        /** @var \Imagick $imagick */
        $imagick->stripImage();
        $imagick->setImageCompression(\Imagick::COMPRESSION_JPEG);
//        $imagick->setImageCompressionQuality($quality);
        $imagick->setImageInterlaceScheme(\Imagick::INTERLACE_PLANE);
        $imagick->setInterlaceScheme(\Imagick::INTERLACE_PLANE);
//        $imagick->gaussianBlurImage(0.8, 10);
    }

    public function getOptimizePrefix()
    {
        return 'opt_';
    }

    /**
     * @param $name
     * @param int $quality
     * @param bool|false $replace
     * @return array|bool|string
     */
    public function optimize($name, $quality = 85, $replace = false)
    {
        if (is_array($name)) {
            $output = [];

            foreach ($name as $_) {
                $output[$_] = $this->optimize($_, $quality, $replace);
            }

            return $output;
        }

        try {
            $imagick = new \Imagick($name);
            self::optimizeImagick($imagick, $quality);

            if ($replace) {
                $output = $name;
            } else {
                $output = dirname($name) . '/' . $this->getOptimizePrefix() . basename($name);
            }

            $v = true === $imagick->writeImage($output);
            $imagick->destroy();

            return $v ? $output : false;
        } catch (\ImagickException $ex) {
            return false;
        }
    }

    public static function download($target, $hash = null, &$error = null)
    {
        $hash = $hash ?: md5($target);
        $pathName = self::getHashServerPath(self::FORMAT_NONE, 0) . '/' . $hash . '.' . self::EXTENSION;

        if (file_exists($pathName)) {
            return $hash;
        }

        $source = $target;

        while (true) {
            try {
                $imagick = new \Imagick($source);
                self::optimizeImagick($imagick);

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
            } catch (\ImagickException $ex) {
                $error = $ex->getMessage();

                if (!isset($pathNameOriginal)) {
                    $pathNameOriginal = implode('/', [
                        self::$app->dirs['@tmp'],
                        'imp_' . basename($target)
                    ]);

                    shell_exec(implode(' ', [
                        'curl -s',
                        '"' . $target . '"',
                        '--output "' . $pathNameOriginal . '"',
                        '> /dev/null'
                    ]));

                    $source = $pathNameOriginal;
                    continue;
                }

                return false;
            }
        }
    }

    public static function downloadRemote($uri, &$error = null)
    {
        return self::download($uri, null, $error);
    }

    public static function downloadLocal(array $file, &$error = null)
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
        return self::download($file['tmp_name'], null, $error);
    }

    public function __toString()
    {
        return $this->stringify();
    }
}

Image::setApp(App::$instance);
