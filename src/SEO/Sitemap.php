<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 3/2/18
 * Time: 3:50 PM
 */
namespace SNOWGIRL_CORE\SEO;

use SNOWGIRL_CORE\SEO;
use SNOWGIRL_CORE\Service\Logger;
use SNOWGIRL_CORE\Sitemap as Generator;
use SNOWGIRL_CORE\Image;
use SNOWGIRL_CORE\Image\Builder as Images;

/**
 * Class Sitemap
 * @package SNOWGIRL_CORE\SEO
 */
class Sitemap
{
    /** @var SEO */
    protected $seo;

    protected $deleteOldFiles = true;

    protected $defaultPubName;
    protected $defaultPubLang;
    /** @var Images */
    protected $images;

    public function __construct(SEO $seo)
    {
        $this->seo = $seo;

        $this->defaultPubName = $seo->getApp()->getSite();
        $this->defaultPubLang = $seo->getApp()->trans->getLang();
        $this->images = $this->seo->getApp()->images;

        $this->initialize();
    }

    /**
     * @return Sitemap
     */
    protected function initialize()
    {
        return $this;
    }

    /**
     * @return array
     */
    protected function getGenerators()
    {
        return [
            'core' => $this->getCoreGenerator(),
            'pages' => $this->getPagesGenerator()
        ];
    }

    protected function getCoreGenerator()
    {
        return function (Generator $sitemap) {
            $pages = $this->seo->getApp()->managers->pages;

            foreach ($pages->getMenu() as $key => $page) {
                //@todo change priority when page_regular has such column...
                $sitemap->add($pages->getLink($page), '1.0', 'weekly');
            }
        };
    }

    protected function getPagesGenerator()
    {
        return function (Generator $sitemap) {
            $pages = $this->seo->getApp()->managers->pages;

            foreach ($pages->setWhere(['is_active' => 1])->getObjects() as $page) {
                //@todo change priority when page_custom has such column...
                $sitemap->add($pages->getLink($page), '1.0', 'weekly');
            }
        };
    }

    public function update($names = null)
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
            $this->log('no items to put');
            return null;
        }

        $sitemap = new Generator(
            $this->seo->getApp()->config->domains->master,
            $this->seo->getApp()->dirs['@public'],
            $this->seo->getApp()->config->server->web_server_user,
            50000, true,
            function ($msg) {
                $this->log($msg, Logger::TYPE_ERROR);
            }
        );

        foreach ($generators as $name => $callback) {
            $sitemap->runWithName($name, $callback);
        }

        if (!$sitemap->create()) {
            return false;
        }

        if ($this->deleteOldFiles) {
            $sitemap->deleteOldFiles();
        }

        $this->seo->getRobotsTxt()->appendSitemap($sitemap->getHttpIndexFile());

        if (!$this->seo->getApp()->isDev()) {
            $tmp = $sitemap->submit();
            $this->log(var_export($tmp, true));
        }

        return true;
    }

    protected function getAddLastModParamByDateTimes()
    {
        foreach (func_get_args() as $datetime) {
            if ($datetime) {
                return date('c', strtotime($datetime));
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
                $output['loc'] = $image->getLink(Image::FORMAT_NONE, 0, 'static');

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
            'language' => $pubLang ?: $this->defaultPubLang
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

    protected function log($msg, $type = Logger::TYPE_DEBUG, $raw = false)
    {
        $this->seo->getApp()->services->logger->make('seo-sitemap: ' . $msg, $type, $raw);
    }
}