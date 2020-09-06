<?php

namespace SNOWGIRL_CORE\Util;

use Monolog\Logger;
use SNOWGIRL_CORE\Util;

/**
 * Class RobotsTxt
 * @package SNOWGIRL_CORE\Util
 */
class RobotsTxt extends Util
{
    public function doGenerate()
    {
        $tmp = [];

        $tmp['host'] = $this->app->config('domains.master');

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

        $template = file_get_contents($this->app->dirs['@core'] . '/dist/config.robots.txt.txt');

        $config = str_replace(array_map(function ($k) {
            return '{{' . $k . '}}';
        }, array_keys($tmp)), $tmp, $template);

        if (false === file_put_contents($this->getServerFile(), $config)) {
            $this->output('update: can\'t save');
            return false;
        }

        return true;
    }

    public function doAppendSitemap($sitemap)
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
            $this->output('can\'t save', Logger::ERROR);
            return false;
        }

        return true;
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
            'q',
        ];
    }

    protected function getServerFile()
    {
        return $this->app->dirs['@public'] . '/robots.txt';
    }
}