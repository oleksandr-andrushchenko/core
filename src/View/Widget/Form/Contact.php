<?php

namespace SNOWGIRL_CORE\View\Widget\Form;

use SNOWGIRL_CORE\AbstractRequest;
use SNOWGIRL_CORE\View\Widget;
use SNOWGIRL_CORE\View\Widget\Form;

use SNOWGIRL_CORE\Entity\Contact as ContactEntity;
use Throwable;

class Contact extends Form
{
    protected $method = 'post';
    protected $captcha = true;
    protected $name;
    protected $email;
    protected $body;

    protected $classColOffset = 'col-sm-offset-4 col-sm-6';
    protected $classColLabel = 'col-sm-4';
    protected $classColInput = 'col-sm-6 col-md-4';

    protected function makeTemplate(): string
    {
        return '@core/widget/form/contact.phtml';
    }

    protected function makeParams(array $params = []): array
    {
        return array_merge(parent::makeParams($params), [
            'name' => $this->app->request->getPostParam('name'),
            'email' => $this->app->request->getPostParam('email'),
            'body' => $this->app->request->getPostParam('body')
        ]);
    }

    protected function addTexts(): Widget
    {
        return parent::addTexts()->addText('widget.form.contact');
    }

    public function process(AbstractRequest $request, string &$msg = null): bool
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

            if (!$this->body) {
                $msg = $this->texts['submitErrorEmptyBody'];
                return false;
            }

            /** @var ContactEntity $contact */
            $contact = $this->app->container->getObject('Entity\\Contact');

            foreach ($contact->getColumns() as $k => $v) {
                if ($vv = $request->getPostParam($k)) {
                    $contact->set($k, $vv);
                } elseif (isset($_FILES[$k])) {
                    $contact->set($k, $_FILES[$k]);
                }
            }

            $this->app->managers->contacts->insertOne($contact);

            $this->app->views->contactEmail(['user' => $contact->getName(), 'body' => $contact->getBody()])
                ->process($contact->getEmail());

            try {
                $this->app->views->contactNotifyEmail($contact->getAttrs())
                    ->processNotifiers();
            } catch (Throwable $e) {
                $this->app->container->logger->error($e);
            }

            if ($contact && $contact->getId()) {
                foreach ($contact->getColumns() as $k => $v) {
                    if (property_exists($this, $k)) {
                        $this->$k = null;
                    }
                }

                $msg = sprintf($this->texts['submitOk'], $contact->getName());
                return true;
            }

            $this->app->container->logger->error('can\'t submit contact: ' . var_export($contact, true));
        } catch (Throwable $e) {
            $this->app->container->logger->error($e);
        }

        $msg = $this->texts['submitError'];
        return false;
    }
}