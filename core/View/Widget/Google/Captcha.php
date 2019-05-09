<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 9/10/17
 * Time: 7:04 PM
 */
namespace SNOWGIRL_CORE\View\Widget\Google;

use ReCaptcha\ReCaptcha;
use SNOWGIRL_CORE\Request;
use SNOWGIRL_CORE\View\Layout;
use SNOWGIRL_CORE\View\Widget\Google;

/**
 * Class Captcha
 * @package SNOWGIRL_CORE\View\Widget\Google
 */
class Captcha extends Google
{
    protected $key;
    protected $secret;

    protected function makeParams(array $params = [])
    {
        return array_merge(parent::makeParams($params), [
            'key' => $this->app->config->keys->google_recaptcha_key(false),
            'secret' => $this->app->config->keys->google_recaptcha_secret(false)
        ]);
    }

    protected function getNode()
    {
        return $this->makeNode('div', [
            'class' => $this->getDomClass(),
            'data-sitekey' => $this->key
        ]);
    }

    protected function addScripts()
    {
        return parent::addScripts()
            ->addJsScript('https://www.google.com/recaptcha/api.js');
    }

    protected function stringifyPrepare()
    {
        $this->addDomClass('g-recaptcha');
        return parent::stringifyPrepare();
    }

    public function verify(Request $request)
    {
        $captcha = new ReCaptcha($this->secret);

        $response = $captcha->verify(
            $request->get('g-recaptcha-response'),
            $request->getServer('REMOTE_ADDR')
        );

        return $response->isSuccess();
    }

    public function isOk()
    {
        return $this->key && $this->secret;
    }
}