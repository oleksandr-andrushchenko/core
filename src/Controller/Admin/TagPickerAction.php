<?php

namespace SNOWGIRL_CORE\Controller\Admin;

use SNOWGIRL_CORE\Http\HttpApp as App;
use SNOWGIRL_CORE\Http\Exception\BadRequestHttpException;
use SNOWGIRL_CORE\Http\Exception\MethodNotAllowedHttpException;

class TagPickerAction
{
    use PrepareServicesTrait;

    public function __invoke(App $app)
    {
        $this->prepareServices($app);

        if (!in_array($app->request->getMethod(), ['GET', 'POST'])) {
            throw (new MethodNotAllowedHttpException)->setValidMethod(['get', 'post']);
        }

        if (!$table = $app->request->get('table')) {
            throw (new BadRequestHttpException)->setInvalidParam('table');
        }

        $picker = $app->managers->getByTable($table)->makeTagPicker(
            $app->request->get('name'),
            1 == $app->request->get('multiple'),
            $app->request->has('params') ? $app->request->get('params') : []
        );

        $picker = (string)$picker;

        if ($app->request->isJSON()) {
            $app->response->setJSON(200, ['view' => $picker]);
        } elseif ($app->request->isAjax()) {
            $app->response->setHTML(200, $picker);
        } else {
            $app->response->setHTML(200, $app->views->getLayout(true)->setContent($picker));
        }
    }
}