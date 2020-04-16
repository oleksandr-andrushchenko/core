<?php

namespace SNOWGIRL_CORE\Controller\Image;

use Imagick;
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
     * @todo implement allowed params whitelist
     * @param App $app
     * @return bool
     * @throws Exception
     * @throws NotFoundHttpException
     * @throws \ImagickException
     */
    public function __invoke(App $app)
    {
        $name = $app->request->get('file');
        $format = $app->request->get('format');
        $param = $app->request->get('param');

        $peaces = explode('.', $name);

        if (2 != count($peaces) || 'jpg' != $peaces[1]) {
            throw new NotFoundHttpException;
        }

        $hash = $peaces[0];

        $pattern = '#([a-z0-9]{' . $app->images->getHashLength() . '})(_[1-9][0-9]{0,3}x[1-9][0-9]{0,3})?#';

        if (!preg_match($pattern, $hash, $matches)) {
            throw new NotFoundHttpException;
        }

        $md5Hash = $matches[1];
        $dimensions = !empty($matches[2]);

        if (
            !is_file($file = $app->images->getPathName(Images::FORMAT_NONE, 0, $hash)) &&
            !is_file($app->images->getPathName($format, $param, $hash))
        ) {
            if ($dimensions) {
                if (
                    !is_file($app->images->getPathName($format, $param, $md5Hash)) &&
                    !is_file($app->images->getPathName(Images::FORMAT_NONE, 0, $md5Hash))
                ) {
                    throw new NotFoundHttpException;
                }

                $canonicalLink = $app->images->getLinkByFile($md5Hash, $format, $param);

                return $app->request->redirect($canonicalLink, 302);
            }

            foreach (glob($app->images->getPathName(Images::FORMAT_NONE, 0, $md5Hash . '_*')) as $pathname) {
                if (preg_match($pattern, basename($pathname), $matches)) {
                    $canonicalLink = $app->images->getLinkByFile($matches[1] . $matches[2], $format, $param);

                    return $app->request->redirect($canonicalLink, 301);
                }
            }
        }

        if (!in_array($format, array_diff(Images::$formats, [Images::FORMAT_NONE]))) {
            throw new NotFoundHttpException;
        }

        if (0 == $param) {
            throw (new BadRequestHttpException)->setInvalidParam('param');
        }

        $newFile = $app->images->getPathName($format, $param, $hash);

        if (!is_dir($dir = dirname($newFile))) {
            if (!mkdir($dir, 0775, true)) {
                return false;
            }
        }

        $imagick = new Imagick($file);

        if (Images::FORMAT_HEIGHT == $format) {
            $imagick->scaleImage(0, $param);
        } elseif (Images::FORMAT_WIDTH == $format) {
            $imagick->scaleImage($param, 0);
        } elseif (Images::FORMAT_CAPTION == $format) {
            $param = explode('-', $param);
            $imagick->scaleImage($param[0], $param[1 == count($param) ? 0 : 1]);
        } elseif (Images::FORMAT_AUTO == $format) {
            $param = explode('-', $param);
            $imagick->cropThumbnailImage($param[0], $param[1 == count($param) ? 0 : 1]);
        } else {
            throw new NotFoundHttpException;
        }

        $imagick->writeImage($newFile);

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

//        $app->response->send(true);
//        return true;
    }
}