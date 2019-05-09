<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 12/21/17
 * Time: 12:18 AM
 */
namespace SNOWGIRL_CORE\View\Widget\Form;

use SNOWGIRL_CORE\Request;
use SNOWGIRL_CORE\Service\Logger;
use SNOWGIRL_CORE\View\Layout;
use SNOWGIRL_CORE\View\Widget\Form;

use SNOWGIRL_CORE\Entity\Subscribe as SubscribeEntity;

/**
 * Class Subscribe
 * @package SNOWGIRL_CORE\View\Widget\Form
 */
class Subscribe extends Form
{
    protected $action = '/subscribe';
    protected $method = 'post';
    protected $inline = true;
    protected $captcha = false;
    protected $name;
    protected $email;

    protected function makeTemplate()
    {
        return '@snowgirl-core/widget/form/subscribe.phtml';
    }

    protected function makeParams(array $params = [])
    {
        return array_merge(parent::makeParams($params), [
            'name' => $this->app->request->getPostParam('name'),
            'email' => $this->app->request->getPostParam('email')
        ]);
    }

    protected function addTexts()
    {
        return parent::addTexts()
            ->addText('widget.form.subscribe');
    }

    protected function addScripts()
    {
        return parent::addScripts()
            ->addCoreScripts()
            ->addJsScript('@snowgirl-core/widget/subscribe.js')
            ->addClientScript('subscribe');
    }

    /**
     * @param Request $request
     * @param null $msg
     * @return bool
     */
    public function process(Request $request, &$msg = null)
    {
        try {
            $captcha = $this->getCaptcha();

            if ($captcha && $captcha->isOk() && !$captcha->verify($request)) {
                $msg = $this->texts['submitErrorCaptcha'];
                return false;
            }

            if (!$this->name) {
                $msg = $this->texts['submitErrorEmptyName'];
                return false;
            }

            if (!$this->email) {
                $msg = $this->texts['submitErrorEmptyEmail'];
                return false;
            }

            $subscribe = (new SubscribeEntity)->setName($this->name)
                ->setEmail($this->email);

            $referer = $this->app->request->getReferer();

            $subscribe->addFilter('page', $referer);

            $this->app->managers->subscribes->insertOne($subscribe, true);

            if (!$subscribe->getId()) {
                if ($subscribe = $this->app->managers->subscribes->getByEmail($subscribe->getEmail())) {
                    if ($subscribe->isConfirmed()) {
                        $msg = sprintf($this->texts['submitOkAlready'], $subscribe->getName());
                        return true;
                    }

                    $this->app->managers->subscribes->sendConfirmationEmail($subscribe);
                }
            }

            if ($subscribe && $subscribe->getId()) {
                foreach ($subscribe->getColumns() as $k => $v) {
                    if (property_exists($this, $k)) {
                        $this->$k = null;
                    }
                }

                $msg = sprintf($this->texts['submitOk'], $subscribe->getName());
                return true;
            }

            $this->app->services->logger->make('can\'t submit subscribe: ' . var_export($subscribe, true), Logger::TYPE_ERROR);
        } catch (\Exception $ex) {
            $this->app->services->logger->makeException($ex);
        }

        $msg = $this->texts['submitError'];
        return false;
    }

    /**
     * @param Request $request
     * @param null $msg
     * @return bool
     */
    public function confirm(Request $request, &$msg = null)
    {
        try {
            if (!$code = $request->get('code')) {
                $msg = $this->texts['confirmErrorEmptyCode'];
                return false;
            }

            if (!$subscribe = $this->app->managers->subscribes->getByCode($code)) {
//            throw new NotFound;
                $msg = $this->texts['confirmErrorGetByCode'];
                return false;
            }

            if ($subscribe->isConfirmed()) {
                $msg = sprintf($this->texts['confirmOkAlready'], $subscribe->getName());
                return true;
            }

            $subscribe->setIsConfirmed(true)
                ->setIsActive(true);

            if ($this->app->managers->subscribes->updateOne($subscribe)) {
                $msg = sprintf($this->texts['confirmOk'], $subscribe->getName());
                return true;
            }

            $this->app->services->logger->make('can\'t confirm subscribe: ' . var_export($subscribe, true), Logger::TYPE_ERROR);
        } catch (\Exception $ex) {
            $this->app->services->logger->makeException($ex);
        }

        $msg = $this->texts['confirmError'];
        return false;
    }
}