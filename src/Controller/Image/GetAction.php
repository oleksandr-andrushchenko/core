<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 5/11/19
 * Time: 12:08 PM
 */

namespace SNOWGIRL_CORE\Controller\Image;

use SNOWGIRL_CORE\App\Web as App;
use SNOWGIRL_CORE\Exception;
use SNOWGIRL_CORE\Exception\HTTP\NotFound;
use SNOWGIRL_CORE\Exception\HTTP\BadRequest;
use SNOWGIRL_CORE\Image;

class GetAction
{
    /**
     * If request comes here - such image should not exists
     *
     * @param App $app
     *
     * @return bool
     * @throws Exception
     * @throws NotFound
     * @throws \ImagickException
     */
    public function __invoke(App $app)
    {
        $name = $app->request->get('file');

        if (!is_file($file = Image::getServerNameByName($name))) {
            throw new NotFound;
        }

        $format = (int)$app->request->get('format');

        if (!in_array($format, array_diff(Image::$formats, [Image::FORMAT_NONE]))) {
            throw new NotFound;
        }

        $param = (int)$app->request->get('param');

        if (0 == $param) {
            throw (new BadRequest)->setInvalidParam('param');
        }

        if (!is_dir($dir = Image::getHashServerPath($format, $param))) {
            if (!mkdir($dir, 0775, true)) {
                return false;
            }
        }

        $imagick = new \Imagick($file);

        if (Image::FORMAT_HEIGHT == $format) {
            $imagick->scaleImage(0, $param);
        } elseif (Image::FORMAT_WIDTH == $format) {
            $imagick->scaleImage($param, 0);
        } elseif (Image::FORMAT_CAPTION == $format) {
            $param = explode('-', $param);
            $imagick->scaleImage($param[1 == count($param) ? 0 : 1], $param[0]);
        } elseif (Image::FORMAT_AUTO == $format) {
            $param = explode('-', $param);
            $imagick->cropThumbnailImage($param[1 == count($param) ? 0 : 1], $param[0]);
        } else {
            throw new NotFound;
        }

        $imagick->writeImage($file = implode('/', [
            $dir,
            $name
        ]));

        $app->response
            ->setHttpResponseCode(200)
//        ->setHeader('Accept-Ranges', 'bytes')
//        ->setHeader('Cache-Control', 'max-age=' . $v, true)
//        ->setHeader('Content-Length', $imagick->getImageLength())
            ->setHeader('Content-Type', 'image/jpeg', true)
//        ->setHeader('ETag', sprintf('"%x-%x-%s"', $fs['ino'], $fs['size'], base_convert(str_pad($fs['mtime'], 16, "0"), 10, 16)), true)
//        ->setHeader('Expires', gmdate('D, d M Y H:i:s \G\M\T', time() + $v), true)
//        ->setHeader('Last-Modified', gmdate('D, d M Y H:i:s \G\M\T'), true)
            ->setBody($imagick->getImageBlob());

        $imagick->destroy();

        $app->response->send(true);
    }
}