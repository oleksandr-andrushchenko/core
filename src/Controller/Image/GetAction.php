<?php

namespace SNOWGIRL_CORE\Controller\Image;

use SNOWGIRL_CORE\Http\HttpApp as App;
use SNOWGIRL_CORE\Exception;
use SNOWGIRL_CORE\Http\Exception\NotFoundHttpException;
use SNOWGIRL_CORE\Http\Exception\BadRequestHttpException;
use SNOWGIRL_CORE\Images;

class GetAction
{
    /**
     * If request comes here - such image should not exists
     *
     * @param App $app
     *
     * @return bool
     * @throws Exception
     * @throws NotFoundHttpException
     * @throws \ImagickException
     */
    public function __invoke(App $app)
    {
        $name = $app->request->get('file');

        if (!is_file($file = $app->images->getServerNameByName($name))) {
            throw new NotFoundHttpException;
        }

        $format = (int)$app->request->get('format');

        if (!in_array($format, array_diff(Images::$formats, [Images::FORMAT_NONE]))) {
            throw new NotFoundHttpException;
        }

        $param = (int)$app->request->get('param');

        if (0 == $param) {
            throw (new BadRequestHttpException)->setInvalidParam('param');
        }

        if (!is_dir($dir = $app->images->getHashServerPath($format, $param))) {
            if (!mkdir($dir, 0775, true)) {
                return false;
            }
        }

        $imagick = new \Imagick($file);

        if (Images::FORMAT_HEIGHT == $format) {
            $imagick->scaleImage(0, $param);
        } elseif (Images::FORMAT_WIDTH == $format) {
            $imagick->scaleImage($param, 0);
        } elseif (Images::FORMAT_CAPTION == $format) {
            $param = explode('-', $param);
            $imagick->scaleImage($param[1 == count($param) ? 0 : 1], $param[0]);
        } elseif (Images::FORMAT_AUTO == $format) {
            $param = explode('-', $param);
            $imagick->cropThumbnailImage($param[1 == count($param) ? 0 : 1], $param[0]);
        } else {
            throw new NotFoundHttpException;
        }

        $imagick->writeImage($file = implode('/', [
            $dir,
            $name
        ]));

        $app->response
            ->setCode(200)
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