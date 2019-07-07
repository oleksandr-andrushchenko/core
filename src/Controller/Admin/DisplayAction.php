<?php

namespace SNOWGIRL_CORE\Controller\Admin;

use SNOWGIRL_CORE\App\Web as App;
use SNOWGIRL_CORE\Exception\HTTP\BadRequest;
use SNOWGIRL_CORE\Exception\HTTP\MethodNotAllowed;

class DisplayAction
{
    use PrepareServicesTrait;

    public function __invoke(App $app)
    {
        $this->prepareServices($app);

        if (!$app->request->isGet()) {
            throw (new MethodNotAllowed)->setValidMethod('get');
        }

        if (!$addr = $app->request->get('addr')) {
            throw (new BadRequest)->setInvalidParam('addr');
        }

        $content = file_get_contents($addr);

//        $content = str_replace('<head>', '<head><base href="' . str_replace('http','https',$addr) . '"/>', $content);
        $content = str_replace('<head>', '<head><base href="' . $addr . '"/>', $content);

        $app->response->setHTML(200, $content);
    }
}