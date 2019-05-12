<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 5/10/19
 * Time: 10:18 PM
 */

namespace SNOWGIRL_CORE\Controller\Admin;

use SNOWGIRL_CORE\App;
use SNOWGIRL_CORE\Exception\HTTP\BadRequest;
use SNOWGIRL_CORE\Exception\HTTP\MethodNotAllowed;
use SNOWGIRL_CORE\Image;

class ImgAction
{
    public function __invoke(App $app)
    {
        (new PrepareServices)($app);

        if ($app->request->isPost()) {
            if (!$file = $app->request->getFileParam('file')) {
                throw (new BadRequest)->setInvalidParam('file');
            }

            if ($file = Image::downloadLocal($file, $error)) {
                return $app->response->setJSON(201, [
                    'hash' => $file,
                    'link' => $app->images->get($file)->getLink()
                ]);
            }
        } elseif ($app->request->isDelete()) {
            if (!$file = $app->request->get('file')) {
                throw (new BadRequest)->setInvalidParam('file');
            }

            if ($app->images->get($file)->delete($error)) {
                return $app->response->setJSON(204);
            }
        } else {
            throw (new MethodNotAllowed)->setValidMethod(['post', 'delete']);
        }

        $app->response->setJSON(200, [
            'error' => $error
        ]);
    }
}