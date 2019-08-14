<?php

namespace SNOWGIRL_CORE\Controller\Admin;

use SNOWGIRL_CORE\App\Web as App;
use SNOWGIRL_CORE\Exception\HTTP\BadRequest;
use SNOWGIRL_CORE\Exception\HTTP\MethodNotAllowed;
use SNOWGIRL_CORE\RBAC;

class ImgAction
{
    use PrepareServicesTrait;

    /**
     * @param App $app
     *
     * @return \SNOWGIRL_CORE\Response
     * @throws \SNOWGIRL_CORE\Exception\HTTP\Forbidden
     */
    public function __invoke(App $app)
    {
        $this->prepareServices($app);

        if ($app->request->isPost()) {
            $app->rbac->checkPerm(RBAC::PERM_UPLOAD_IMG);

            if (!$file = $app->request->getFileParam('file')) {
                throw (new BadRequest)->setInvalidParam('file');
            }

            if ($file = $app->images->downloadLocal($file, false, $error)) {
                return $app->response->setJSON(201, [
                    'hash' => $file,
                    'link' => $app->images->getLinkByFile($file)
                ]);
            }
        } elseif ($app->request->isDelete()) {
            $app->rbac->checkPerm(RBAC::PERM_DELETE_IMG);

            if (!$file = $app->request->get('file')) {
                throw (new BadRequest)->setInvalidParam('file');
            }

            if ($app->images->deleteByFile($file, $error)) {
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