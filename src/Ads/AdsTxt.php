<?php

namespace SNOWGIRL_CORE\Ads;

use SNOWGIRL_CORE\Ads;
use SNOWGIRL_CORE\Service\Logger;
use SNOWGIRL_CORE\Helper\Classes;

class AdsTxt
{
    /**
     * @var Ads
     */
    protected $ads;

    public function __construct(Ads $ads)
    {
        $this->ads = $ads;

        $this->initialize();
    }

    /**
     * @return AdsTxt
     */
    protected function initialize()
    {
        return $this;
    }

    /**
     * @see http://www.vesti.ru/ads.txt
     * @see https://www.ukr.net/ads.txt
     * @see https://www.crimea.kp.ru/ads.txt
     * @see https://support.google.com/dfp_premium/answer/7441288?hl=ru
     * @return bool
     */
    public function update()
    {
        $tmp = [];

        foreach (Classes::getInNsCheckAppNs('Ad', $this->ads->getApp()) as $ad) {
            if ($ad = $this->ads->createAd($ad)) {
                $tmp2 = implode(', ', array_filter([
                    $ad->getAdsTxtDomain(),
                    $ad->getAdsTxtAccountId(),
                    $ad->getAdsTxtRelationshipType(),
                    $ad->getAdsTxtTagId()
                ], function ($v) {
                    return null !== $v;
                }));

                if (2 < count(explode(',', $tmp2))) {
                    $tmp[] = $tmp2;
                }
            }
        }

        $file = $this->ads->getApp()->dirs['@public'] . '/ads.txt';

        if ($tmp) {
            if (false === file_put_contents($file, implode("\r\n", $tmp))) {
                $this->log('can\'t save', Logger::TYPE_ERROR);
                return false;
            }

            $this->giveFilePermissions($file);
        } elseif (file_exists($file) && !unlink($file)) {
            $this->log('can\'t delete', Logger::TYPE_ERROR);
        }

        return true;
    }

    protected function giveFilePermissions($target)
    {
        if (chmod($target, 0775) && chown($target, $this->ads->getApp()->config->server->web_server_user)) {
            return true;
        }

        $this->log('can\'t give permissions to "' . $target . '"');
        return false;
    }

    protected function log($msg, $type = Logger::TYPE_DEBUG, $raw = false)
    {
        $this->ads->getApp()->logger->make('Ads-ads-txt: ' . $msg, $type, $raw);
    }
}