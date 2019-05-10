<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 1/4/17
 * Time: 6:43 AM
 */

namespace SNOWGIRL_CORE\Service\Transport;

use SNOWGIRL_CORE\App;
use SNOWGIRL_CORE\Service\Transport;

/**
 * Class Email
 * @package SNOWGIRL_CORE\Service\Transport
 */
class Email extends Transport
{
    protected $smtp = [];
    protected $driver;

    protected function initialize2(App $app = null)
    {
        parent::initialize2($app);

        if (!isset($this->smtp['user']) && ($email = $app->config->site->email(false))) {
            $this->smtp['user'] = $email;
        }
    }

    /**
     * @return \PHPMailer
     */
    public function getDriver()
    {
        if (null == $this->driver) {
            $this->driver = new \PHPMailer();

            if ($this->smtp) {
                $this->driver->isSMTP();

                $this->driver->Host = $this->smtp['host'];
                $this->driver->Port = $this->smtp['port'];

                if ($this->smtp['auth']) {
                    $this->driver->SMTPAuth = true;
                    $this->driver->SMTPSecure = $this->smtp['secure'];
                    $this->driver->Username = $this->smtp['user'];
                    $this->driver->Password = $this->smtp['password'];
                }
            }

            $this->driver->CharSet = 'utf-8';
            $this->driver->isHTML(true);

            $this->driver->SMTPDebug = 3;
            $this->driver->Debugoutput = function ($str) {
//                $this->log($str);
            };
        }

        return $this->driver;
    }

    protected function _transfer($subject, $body = null)
    {
        $this->getDriver()->setFrom($this->sender, $this->site);
        $this->getDriver()->addAddress($this->receiver);
        $this->getDriver()->Subject = $subject;

        if ($body) {
            $this->getDriver()->Body = $body;
        }

        $isOk = $this->getDriver()->send();

        if (!$isOk) {
            $this->log('Not sent! Details:');
            $this->log($this->getDriver()->ErrorInfo);
        }

        return $isOk;
    }
}