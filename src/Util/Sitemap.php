<?php

namespace SNOWGIRL_CORE\Util;

use Monolog\Logger;
use SNOWGIRL_CORE\Images;
use SNOWGIRL_CORE\Sitemap as Generator;
use SNOWGIRL_CORE\Util;

class Sitemap extends Util
{
    private $deleteOldFiles = true;

    private $defaultPubName;
    private $defaultPubLang;

    /**
     * @var Images
     */
    private $images;

    public function doGenerate($names = null): ?int
    {
        $generators = $this->getGenerators();

        if ($names) {
            if (!is_array($names)) {
                $names = [$names];
            }

            $generators = array_filter($generators, function ($name) use ($names) {
                return in_array($name, $names);
            }, ARRAY_FILTER_USE_KEY);
        }

        if (!$generators) {
            $this->output('no items to generate');
            return null;
        }

        $sitemap = new Generator(
            $this->app->config('domains.master'),
            $this->app->dirs['@public'],
            $this->app->config('server.web_server_user'),
            50000, true,
            function ($msg) {
                $this->output($msg, Logger::ERROR);
            }
        );

        foreach ($generators as $name => $callback) {
            $sitemap->runWithName($name, $callback);
        }

        if (!$sitemap->create()) {
            $this->output('error on creation');
            return null;
        }

        if ($this->deleteOldFiles && !$sitemap->deleteOldFiles()) {
            $this->output('error on delete', Logger::WARNING);
        }

        $this->app->utils->robotsTxt->doAppendSitemap($sitemap->getHttpIndexFile());

        $tmp = $sitemap->submit();
        $this->output(var_export($tmp, true));

        return $sitemap->getAddedCount();
    }
    
    protected function initialize()
    {
        parent::initialize();
        
        $this->defaultPubName = $this->app->getSite();
        $this->defaultPubLang = $this->app->trans->getLang();
        $this->images = $this->app->images;
    }

    /**
     * @return array
     */
    protected function getGenerators()
    {
        return [
            'core' => $this->getCoreGenerator(),
            'pages' => $this->getPagesGenerator(),
        ];
    }

    protected function getCoreGenerator()
    {
        return function (Generator $sitemap) {
            $pages = $this->app->managers->pages;

            foreach ($pages->getMenu() as $key => $page) {
                //@todo change priority when page_regular has such column...
                $sitemap->add($pages->getLink($page), '1.0', 'weekly');
            }
        };
    }

    protected function getAddLastModParamByTimes()
    {
        foreach (func_get_args() as $time) {
            if ($time) {
                return date('c', is_numeric($time) ? $time : strtotime($time));
            }
        }

        return null;
    }

    protected function getAddImageParam($file, $title = null, $caption = null, $geo = null)
    {
        if ($file) {
            $image = $this->images->get($file);

            if ($image->isLocal()) {
                $output = [];
                $output['loc'] = $this->images->getLink($image, Images::FORMAT_NONE, 0, 'static');

                if ($title) {
                    $output['title'] = $title;
                }

                if ($caption) {
                    $output['caption'] = $caption;
                }

                if ($geo) {
                    $output['geo_location'] = $geo;
                }

                return $output;
            }
        }

        return null;
    }

    protected function getAddNewsParam($title, $date, $keywords = null, $genres = null, $pubName = null, $pubLang = null)
    {
        $output = [];

        $output['publication'] = [
            'name' => $pubName ?: $this->defaultPubName,
            'language' => $pubLang ?: $this->defaultPubLang,
        ];

        $output['title'] = $title;
        $output['publication_date'] = $date;

        if ($keywords) {
            $output['keywords'] = $keywords;
        }

        if ($genres) {
            $output['genres'] = $genres;
        }

        return $output;
    }

    private function getPagesGenerator()
    {
        return function (Generator $sitemap) {
            $pages = $this->app->managers->pages;

            foreach ($pages->setWhere(['is_active' => 1])->getObjects() as $page) {
                //@todo change priority when page_custom has such column...
                $sitemap->add($pages->getLink($page), '1.0', 'weekly');
            }
        };
    }
}