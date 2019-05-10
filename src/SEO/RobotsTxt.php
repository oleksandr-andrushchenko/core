<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 3/2/18
 * Time: 3:59 PM
 */

namespace SNOWGIRL_CORE\SEO;

use SNOWGIRL_CORE\SEO;
use SNOWGIRL_CORE\Service\Logger;

/**
 * Class RobotsTxt
 * @package SNOWGIRL_CORE\SEO
 */
class RobotsTxt
{
    /** @var SEO */
    protected $seo;

    public function __construct(SEO $seo)
    {
        $this->seo = $seo;

        $this->initialize();
    }

    /**
     * @return RobotsTxt
     */
    protected function initialize()
    {
        return $this;
    }

    /**
     * @return array
     */
    protected function getDisallows()
    {
        return [
            '*?*',
            '*&*',
            '/admin/',
            '/css/',
            '/js/',
            '/get-search-suggestions*',
            '/get-ip-info*',
//            '/contact*',
            '/subscribe*',
            '/sync-session-data*',
        ];
    }

    protected function getCleanParams()
    {
        return [
            'q'
        ];
    }

    protected function getServerFile()
    {
        return $this->seo->getApp()->dirs['@web'] . '/robots.txt';
    }

    public function update()
    {
        $tmp = [];

        $tmp['host'] = $this->seo->getApp()->config->domains->master;

        if ($disallows = $this->getDisallows()) {
            $tmp['disallows'] = PHP_EOL . implode(PHP_EOL, array_map(function ($disallow) {
                    return 'Disallow: ' . $disallow;
                }, $disallows));
        } else {
            $tmp['disallows'] = '';
        }

        if ($cleanParams = $this->getCleanParams()) {
            $tmp['clean_params'] = PHP_EOL . 'Clean-param: ' . implode('&', $cleanParams);
        } else {
            $tmp['clean_params'] = '';
        }

        $template = file_get_contents($this->seo->getApp()->dirs['@core'] . '/config.robots.txt.txt');

        $config = str_replace(array_map(function ($k) {
            return '{{' . $k . '}}';
        }, array_keys($tmp)), $tmp, $template);

        if (false === file_put_contents($this->getServerFile(), $config)) {
            $this->log('update: can\'t save');
            return false;
        }

        return true;
    }

    public function appendSitemap($sitemap)
    {
        $file = $this->getServerFile();

        $content = '';

        if (file_exists($file)) {
            foreach (explode("\n", file_get_contents($file)) as $key => $value) {
                if (substr($value, 0, 8) != 'Sitemap:') {
                    $content .= $value . "\n";
                }
            }
        } else {
            $content .= "User-agent: *\nAllow: /\n\n";
        }

        $content .= 'Sitemap: ' . $sitemap;

        if (false === file_put_contents($file, $content)) {
            $this->log('can\'t save', Logger::TYPE_ERROR);
            return false;
        }

        return true;
    }

    protected function log($msg, $type = Logger::TYPE_DEBUG)
    {
        $this->seo->getApp()->services->logger->make('seo-robots-txt: ' . $msg, $type);
    }
}