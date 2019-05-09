<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 3/7/16
 * Time: 6:47 AM
 */

namespace SNOWGIRL_CORE\Controller;

use SNOWGIRL_CORE\Controller;
use SNOWGIRL_CORE\Exception;
use SNOWGIRL_CORE\Exception\HTTP\NotFound;
use SNOWGIRL_CORE\Exception\HTTP\BadRequest;
use SNOWGIRL_CORE\Image as ImageEntity;

/**
 * Class Image
 * @package SNOWGIRL_CORE\Controller
 */
class Image extends Controller
{
    /**
     * If request comes here - such image should not exists
     * 
     * @return \SNOWGIRL_CORE\Response
     * @throws Exception
     * @throws NotFound
     * @throws \ImagickException
     */
    public function actionGet()
    {
        $name = $this->app->request->get('file');

        if (!is_file($file = ImageEntity::getServerNameByName($name))) {
            throw new NotFound;
        }

        $format = (int)$this->app->request->get('format');

        if (!in_array($format, array_diff(ImageEntity::$formats, [ImageEntity::FORMAT_NONE]))) {
            throw new NotFound;
        }

        $param = (int)$this->app->request->get('param');

        if (0 == $param) {
            throw (new BadRequest)->setInvalidParam('param');
        }

        if (!is_dir($dir = ImageEntity::getHashServerPath($format, $param))) {
            if (!mkdir($dir, 0775, true)) {
                return false;
            }
        }

        $imagick = new \Imagick($file);

        if (ImageEntity::FORMAT_HEIGHT == $format) {
            $imagick->scaleImage(0, $param);
        } elseif (ImageEntity::FORMAT_WIDTH == $format) {
            $imagick->scaleImage($param, 0);
        } elseif (ImageEntity::FORMAT_CAPTION == $format) {
            $param = explode('-', $param);
            $imagick->scaleImage($param[1 == count($param) ? 0 : 1], $param[0]);
        } elseif (ImageEntity::FORMAT_AUTO == $format) {
            $param = explode('-', $param);
            $imagick->cropThumbnailImage($param[1 == count($param) ? 0 : 1], $param[0]);
        } else {
            throw new NotFound;
        }

        $imagick->writeImage($file = implode('/', [
            $dir,
            $name
        ]));

        $this->app->response
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

        return $this->app->response
            ->send(true);
    }
}