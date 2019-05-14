<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 5/10/19
 * Time: 10:09 PM
 */

namespace SNOWGIRL_CORE\Controller\Outer;

use SNOWGIRL_CORE\App\Web as App;
use SNOWGIRL_CORE\Exception\HTTP\MethodNotAllowed;
use SNOWGIRL_CORE\View\Layout;
use SNOWGIRL_CORE\View\Widget\Form\Contact;

class ContactsAction
{
    use PrepareServicesTrait;
    use ProcessTypicalPageTrait;

    /**
     * @param App $app
     *
     * @return bool
     * @throws \SNOWGIRL_CORE\Exception
     */
    public function __invoke(App $app)
    {
        /** @var Layout $view */
        /** @var Contact $form */

        $this->prepareServices($app);

        if ($app->request->isGet()) {

        } elseif ($app->request->isPost()) {
            $view = $app->views->getLayout();
            $form = $app->views->contactForm([], $view);

            $isOk = $form->process($app->request, $msg);
            $view->addMessage($msg, $isOk ? Layout::MESSAGE_SUCCESS : Layout::MESSAGE_ERROR);
            return $app->request->redirect($app->request->getReferer());
        } else {
            throw (new MethodNotAllowed)->setValidMethod(['get', 'post']);
        }

        if ($app->request->isJSON()) {
            $form = $app->views->contactForm();
            $app->response->setJSON(200, ['isOk' => true, 'body' => $form->stringify()]);
        } elseif ($app->request->isAjax()) {
            $form = $app->views->contactForm();
            $app->response->setHTML(200, ['body' => $form->stringify()]);
        } else {
            $view = $this->processTypicalPage($app, 'contacts');
            $form = $app->views->contactForm([], $view);
            $view->getContent()->setParam('form', $form->stringify());
            $app->response->setHTML(200, $view);
        }
    }
}