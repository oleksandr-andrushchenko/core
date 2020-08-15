<?php

namespace SNOWGIRL_CORE\View\Widget\Form;

use SNOWGIRL_CORE\AbstractRequest;
use SNOWGIRL_CORE\View\Widget;
use SNOWGIRL_CORE\View\Widget\Form;

use SNOWGIRL_CORE\Entity\Subscribe as SubscribeEntity;
use Throwable;

class Subscribe extends Form
{
    protected $action = '/subscribe';
    protected $method = 'post';
    protected $inline = true;
    protected $captcha = false;
    protected $name;
    protected $email;

    protected function makeTemplate(): string
    {
        return '@core/widget/form/subscribe.phtml';
    }

    protected function makeParams(array $params = []): array
    {
        return array_merge(parent::makeParams($params), [
            'name' => $this->app->request->getPostParam('name'),
            'email' => $this->app->request->getPostParam('email'),
        ]);
    }

    protected function addTexts(): Widget
    {
        return parent::addTexts()->addText('widget.form.subscribe');
    }

    protected function addScripts(): Widget
    {
        return parent::addScripts()
            ->addCoreScripts()
            ->addJsScript('@core/widget/subscribe.js')
            ->addClientScript('subscribe');
    }

    /**
     * @param AbstractRequest $request
     * @param null $msg
     * @return bool
     */
    public function process(AbstractRequest $request, &$msg = null)
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

            $subscribe = (new SubscribeEntity)
                ->setName($this->name)
                ->setEmail($this->email);

            $referer = $this->app->request->getReferer();

            $subscribe->addFilter('page', $referer);

            $this->app->managers->subscribes->insertOne($subscribe, ['ignore' => true]);

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

            $this->app->container->logger->error('can\'t submit subscribe', [
                'name' => $this->name,
                'email' => $this->email,
            ]);
        } catch (Throwable $e) {
            $this->app->container->logger->error($e);
        }

        $msg = $this->texts['submitError'];
        return false;
    }

    public function confirm(AbstractRequest $request, string &$msg = null): bool
    {
        try {
            if (!$code = $request->get('code')) {
                $msg = $this->texts['confirmErrorEmptyCode'];
                return false;
            }

            if (!$subscribe = $this->app->managers->subscribes->getByCode($code)) {
//            throw new NotFoundHttpException;
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

            $this->app->container->logger->error('can\'t confirm subscribe: ' . var_export($subscribe, true));
        } catch (Throwable $e) {
            $this->app->container->logger->error($e);
        }

        $msg = $this->texts['confirmError'];
        return false;
    }
}