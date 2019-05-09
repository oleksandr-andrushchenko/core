<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 1/5/17
 * Time: 12:03 AM
 */
namespace SNOWGIRL_CORE\Service\Transport;

use SNOWGIRL_CORE\Service\Transport;

/**
 * Class Phone
 * @package SNOWGIRL_CORE\Service\Transport
 */
class Phone extends Transport
{
    protected function getCarriers()
    {
        return [
//            'sms.umc.com.ua',
            'sms.kyivstar.net',
            'txt.att.net',
            'mobile.att.net',
            'sms.beemail.ru',
            't-mobile-sms.de',
            'o2.co.uk',
            'tmomail.net',
            'vtext.com',
            'tmomail.net'
        ];
    }

    /**
     * @todo...
     * @param $subject
     * @param null $body
     * @return bool
     */
    protected function _transfer($subject, $body = null)
    {
        foreach ($this->getCarriers() as $carrier) {
            $mailer = new Email($this->receiver . '@' . $carrier);
            $mailer->getDriver()->Encoding = '7bit';

            if ($mailer->transfer($subject, $body)) {
                return true;
            }
        }

        return false;
    }
}