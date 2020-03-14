<?php

namespace SNOWGIRL_CORE\Controller\Admin;

use SNOWGIRL_CORE\Http\HttpApp as App;
use SNOWGIRL_CORE\Http\Exception\BadRequestHttpException;
use SNOWGIRL_CORE\Http\Exception\ForbiddenHttpException;
use SNOWGIRL_CORE\Http\Exception\MethodNotAllowedHttpException;
use SNOWGIRL_CORE\RBAC;

class ImgAction
{
    use PrepareServicesTrait;

    /**
     * @param App $app
     *
     * @return \SNOWGIRL_CORE\Response
     * @throws ForbiddenHttpException
     */
    public function __invoke(App $app)
    {
        $this->prepareServices($app);

        if ($app->request->isPost()) {
            $app->rbac->checkPerm(RBAC::PERM_UPLOAD_IMG);

            if (!$file = $app->request->getFileParam('file')) {
                throw (new BadRequestHttpException)->setInvalidParam('file');
            }

            if ($file = $app->images->downloadLocal($file, $error)) {
                return $app->response->setJSON(201, [
                    'hash' => $file,
                    'link' => $app->images->getLinkByFile($file)
                ]);
            }
        } elseif ($app->request->isDelete()) {
            $app->rbac->checkPerm(RBAC::PERM_DELETE_IMG);

            if (!$file = $app->request->get('file')) {
                throw (new BadRequestHttpException)->setInvalidParam('file');
            }

            if ($app->images->deleteByFile($file, $error)) {
                return $app->response->setJSON(204);
            }
        } else {
            throw (new MethodNotAllowedHttpException)->setValidMethod(['post', 'delete']);
        }

        $app->response->setJSON(200, [
            'error' => $error
        ]);
    }
}