<?php

namespace SNOWGIRL_CORE;

use GlobIterator;
use Imagick;
use SNOWGIRL_CORE\Command\BatchCommand;
use Throwable;

class Images
{
    private const EXTENSION = 'jpg';

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
        self::FORMAT_AUTO,
    ];

    /**
     * @var AbstractApp
     */
    private $app;

    public function __construct(AbstractApp $app)
    {
        $this->app = $app;
    }

    public function get($file): Image
    {
        return new Image($file);
    }

    protected static function normalizeParam($format = self::FORMAT_NONE, $param = 0)
    {
        if (in_array($format, [self::FORMAT_CAPTION, self::FORMAT_AUTO]) && is_array($param)) {
            $tmp = [];

            // width
            if (isset($param[0])) {
                $tmp[] = $param[0];
            }

            // height
            if (isset($param[1])) {
                $tmp[] = $param[1];
            }

            $param = implode('-', $tmp);
        }

        return $param;
    }

    public function getLink(Image $image, $format = self::FORMAT_NONE, $param = 0, $domain = false)
    {
        if ($image->isLocalHash()) {
            return $this->app->router->makeLink('image', [
                'format' => $format,
                'param' => $this->normalizeParam($format, $param),
                'file' => $image->getFile() . '.' . self::EXTENSION,
            ], $domain);
        }

        if ($image->isLocalNonHash()) {
            return $this->app->router->makeLink('default', [
                'action' => $image->getFile(),
            ], $domain);
        }

        return $image->getFile();
    }

    /**
     * @return bool
     */
    public function tests(): bool
    {
        $this->test('/home/snowgirl/Downloads/girlway-tests-images/719x396.png');
        $this->test('/home/snowgirl/Downloads/girlway-tests-images/495x700.jpg');

        return true;
    }

    /**
     * @todo should be always synced with Image\GetAction method
     * @param int $format
     * @param int $param
     * @param Image $image
     * @return null|array
     */
    public function getDimensions(Image $image, $format = self::FORMAT_NONE, $param = 0): ?array
    {
        if (Images::FORMAT_HEIGHT == $format) {
            return $this->getHeightDimensions($image, $param);
        } elseif (Images::FORMAT_WIDTH == $format) {
            return $this->getWidthDimensions($image, $param);
        } elseif (Images::FORMAT_CAPTION == $format) {
            $param = explode('-', $param);
            return [$param[0], $param[1 == count($param) ? 0 : 1]];
        } elseif (Images::FORMAT_AUTO == $format) {
            $param = explode('-', $param);
            return [$param[0], $param[1 == count($param) ? 0 : 1]];
        }

        return [$image->getWidth(), $image->getHeight()];
    }

    public function getDimensionsByPath(string $path): ?array
    {
        try {
            $imagick = new Imagick($path);
            $width = $imagick->getImageWidth();
            $height = $imagick->getImageHeight();
            $imagick->destroy();
            return [$width, $height];
        } catch (Throwable $e) {
            return null;
        }
    }

    public function getLinkByFile($file, $format = self::FORMAT_NONE, $param = 0, $domain = false)
    {
        return $this->getLink($this->get($file), $format, $param, $domain);
    }

    public function delete(Image $image, &$error = null)
    {
        try {
            if ($image->isLocalHash()) {
                foreach (glob($this->getPathName('*', '*', $image->getFile())) as $file) {
                    unlink($file);
                }
            } elseif ($image->isLocalNonHash()) {
                false || false;
            }

            return true;
        } catch (Throwable $e) {
            $error = $e->getMessage();
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

        foreach (glob($this->getPathName('*', '*', $image)) as $file) {
            if (unlink($file)) {
                $aff++;
            }
        }

        return $aff;
    }

    public function getLocalByFile($file): string
    {
        return str_replace('.' . self::EXTENSION, '', basename($file));
    }

    public function getPathName($format, $param, $hash): string
    {
        return $this->app->dirs['@public'] . '/img/' . $format . '/' . $this->normalizeParam($format, $param) . '/' . $hash . '.' . self::EXTENSION;
    }

    public function walkLocal($format, $param, string $hash, callable $job, int $size = 1000): int
    {
        $path = $this->getPathName($format, $param, $hash);

        if ('*' === $hash) {
            $iterator = new GlobIterator($path, GlobIterator::CURRENT_AS_PATHNAME);
        } else {
            $iterator = glob($path);
        }

        $command = new BatchCommand($iterator, $job, $size);

        return $command();
    }

    protected function optimizeImagick($imagick, $quality = 70)
    {
        /** @var Imagick $imagick */
        $imagick->stripImage();
        $imagick->setImageCompression(Imagick::COMPRESSION_JPEG);
        $imagick->setImageCompressionQuality($quality);
        $imagick->setImageInterlaceScheme(Imagick::INTERLACE_PLANE);
        $imagick->setInterlaceScheme(Imagick::INTERLACE_PLANE);
//        $imagick->gaussianBlurImage(0.8, 10);
    }

    public function getOptimizePrefix()
    {
        return 'opt_';
    }

    /**
     * @param $file
     * @param int $quality
     * @param bool $replace
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
            $imagick = new Imagick($file);
            $this->optimizeImagick($imagick, $quality);

            if ($replace) {
                $output = $file;
            } else {
                $output = dirname($file) . '/' . $this->getOptimizePrefix() . basename($file);
            }

            $v = true === $imagick->writeImage($output);
            $imagick->destroy();

            return $v ? $output : false;
        } catch (Throwable $e) {
            return false;
        }
    }

    public function getHash($target): string
    {
        return md5($target);
    }

    public function getHashLength(): int
    {
        return 32;
    }

    public function getExtension(): string
    {
        return self::EXTENSION;
    }

    public function getFileName($target, $hash = null)
    {
        $hash = $hash ?: $this->getHash($target);
        return $hash . '.' . self::EXTENSION;
    }

    /**
     * @param string $target
     * @param string|null $hash
     * @param string|null $error
     * @return bool|string
     */
    public function download(string $target, string $hash = null, string &$error = null)
    {
        try {
            $hash = $hash ?: $this->getHash($target);

            $imagick = new Imagick($target);
            $this->optimizeImagick($imagick);

            $height = $imagick->getImageHeight();
            $width = $imagick->getImageWidth();

            $name = $this->addDimensions($hash, $width, $height);
            $pathName = $this->getPathName(Images::FORMAT_NONE, 0, $name);

            if ('image/png' == $imagick->getImageMimeType()) {
//            if (('image/png' == $imagick->getImageMimeType() && (Imagick::COLORSPACE_RGB == $imagick->getColorspace()))) {
                $white = new Imagick();
                $white->newImage($width, $height, 'white');
                $white->compositeimage($imagick, Imagick::COMPOSITE_OVER, 0, 0);
                $imagick = $white;
            }

            $imagick->setImageFormat(self::EXTENSION);

            if (true === $imagick->writeImage($pathName)) {
//                $v = $hash;

                if (0 < filesize($pathName)) {
                    $v = $name;
                } else {
                    unlink($pathName);
                    $v = false;
                }
            } else {
                $v = false;
            }

            $imagick->destroy();
            return $v;
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }

        return false;
    }

    public function downloadWithCurl($uri, $hash = null, &$error = null)
    {
        try {
            $hash = $hash ?: $this->getHash($uri);

            $local = implode('/', [
                $this->app->dirs['@tmp'],
                'imp_curl_' . md5($uri),
            ]);

            shell_exec(implode(' ', [
                'curl -s -L',
                '"' . $uri . '"',
                '-o "' . $local . '"',
                '> /dev/null',
            ]));

            $output = $this->download($local, $hash, $error);
            unlink($local);
            return $output;
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }

        return false;
    }

    public function downloadWithWget($uri, $hash = null, &$error = null)
    {
        try {
            $hash = $hash ?: $this->getHash($uri);

            $local = implode('/', [
                $this->app->dirs['@tmp'],
                'imp_wget_' . md5($uri),
            ]);

            shell_exec(implode(' ', [
                'wget --quiet',
                '-U "Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.8.1.6) Gecko/20070802 SeaMonkey/1.1.4"',
                '--output-document=' . $local,
                '"' . $uri . '"',
                '> /dev/null',
            ]));

            $output = $this->download($local, $hash, $error);
            unlink($local);
            return $output;
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }

        return false;
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

    /**
     * @param string $hash
     * @param int|null $width
     * @param int|null $height
     * @return string
     * @throws Exception
     */
    public function addDimensions(string $hash, int $width = null, int $height = null): string
    {
        if (null === $width || null === $height) {
            if (!$info = $this->getDimensionsByPath($this->getPathName(self::FORMAT_NONE, 0, $hash))) {
                throw new Exception('invalid getimagesize result');
            }

            $width = $info[0];
            $height = $info[1];
        }

        return str_replace(['{hash}', '{width}', '{height}'], [$hash, $width, $height], '{hash}_{width}x{height}');
    }

    private function getHeightDimensions(Image $image, int $targetHeight): ?array
    {
        $width = $image->getWidth();
        $height = $image->getHeight();

        if (!$width || !$height) {
            return null;
        }

        $ratio = $height / $width;
        $newHeight = $targetHeight;
        $newWidth = round($newHeight / $ratio);

        return [$newWidth, $newHeight];
    }

    private function getWidthDimensions(Image $image, int $targetWidth): ?array
    {
        $width = $image->getWidth();
        $height = $image->getHeight();

        if (!$width || !$height) {
            return null;
        }

        $ratio = $height / $width;
        $newWidth = $targetWidth;
        $newHeight = round($newWidth * $ratio);

        return [$newWidth, $newHeight];
    }

    /**
     * @param $file
     * @throws \ImagickException
     */
    private function test($file)
    {
        $sizes = [
            [300, 300],
            [500, 500],
            [800, 800],
        ];

        foreach (self::$formats as $format) {
            foreach ($sizes as $size) {
                $imagick = new Imagick($file);

                if (Images::FORMAT_HEIGHT == $format) {
                    $imagick->scaleImage(0, $size[1]);
                    $imagick->writeImage(str_replace('.', '_height_' . implode('x', $size) . '.', $file));
                } elseif (Images::FORMAT_WIDTH == $format) {
                    $imagick->scaleImage($size[0], 0);
                    $imagick->writeImage(str_replace('.', '_width_' . implode('x', $size) . '.', $file));
                } elseif (Images::FORMAT_CAPTION == $format) {
                    $imagick->scaleImage($size[0], $size[0]);
                    $imagick->writeImage(str_replace('.', '_caption_1_' . implode('x', [$size[0], $size[0]]) . '.', $file));

                    $imagick->scaleImage($size[0], $size[1]);
                    $imagick->writeImage(str_replace('.', '_caption_2_' . implode('x', $size) . '.', $file));
                } elseif (Images::FORMAT_AUTO == $format) {
                    $imagick->cropThumbnailImage($size[0], $size[0]);
                    $imagick->writeImage(str_replace('.', '_auto_1_' . implode('x', [$size[0], $size[0]]) . '.', $file));

                    $imagick->cropThumbnailImage($size[0], $size[1]);
                    $imagick->writeImage(str_replace('.', '_auto_2_' . implode('x', $size) . '.', $file));
                }
            }
        }
    }
}