<?php

namespace SNOWGIRL_CORE;

use SNOWGIRL_CORE\View\Layout;

class SEO
{
    /**
     * @var AbstractApp
     */
    protected $app;

    public function __construct(AbstractApp $app)
    {
        $this->app = $app;
    }

    public function setNoIndexNoFollow(Layout $view = null)
    {
        $this->app->response->setNoIndexNoFollow();

        if ($view) {
            $view->setNoIndexNoFollow();
        }

        return $this;
    }

    public function addMeta($title, $description, $keywords, $ogType, $ogUrl, $ogTitle, $ogDescription, $ogImage, $properties, Layout $view)
    {
        if ('website' == $ogType) {
            $prefix = 'og: http://ogp.me/ns# fb: http://ogp.me/ns/fb# website: http://ogp.me/ns/website#';
        } elseif ('article' == $ogType) {
            $prefix = 'og: http://ogp.me/ns# fb: http://ogp.me/ns/fb# article: http://ogp.me/ns/article#';
        } else {
            $prefix = null;
        }

        $view->setTitle($title)
            ->setHeadPrefix($prefix)
            ->addMetaProperty('fb:app_id', $this->app->config('keys.facebook_app_id'))
            ->addMetaProperty('og:site_name', $this->app->getSite())
//            ->addMetaProperty('og:locale:locale', strtolower($this->app->trans->getLocale()))
            ->addMetaProperty('og:type', $ogType)
            ->addMetaProperty('og:url', $ogUrl)
            ->addMetaProperty('og:title', $ogTitle);

        if ($ogImage) {
            $image = $this->app->images->get($ogImage);

            $view->addMetaProperty('og:image', $imageLink = $this->app->images->getLink($image))
                ->addMetaProperty('og:image:secure_url', $imageLink)
                ->addMetaProperty('og:image:type', $image->getMime())
                ->addMetaProperty('og:image:width', $image->getWidth())
                ->addMetaProperty('og:image:height', $image->getHeight())
                ->addMetaProperty('og:image:alt', 'Фото ' . $title)
                ->addMetaProperty('og:image:user_generated', 'false')
                ->addHeadLink('image', $imageLink);
        }

        if (is_array($properties)) {
            foreach ($properties as $prop => $value) {
                if (is_array($value)) {
                    foreach ($value as $value2) {
                        $view->addMetaProperty($prop, $value2);
                    }
                } else {
                    $view->addMetaProperty($prop, $value);
                }
            }
        }

        $view->addMeta('description', $description)
            ->addMetaProperty('og:description', $ogDescription)
            ->addMeta('keywords', $keywords);
    }

    /**
     * @todo process og properties... add columns to the pages table
     *
     * @param        $key
     * @param Layout $view
     * @param array  $params
     *
     * @throws Exception
     */
    public function managePage($key, Layout $view, array $params = [])
    {
        $page = $this->app->managers->pages->findByKey($key);

//        $reqUri = $view->makeLink('default', $index ? [] : $key);
        $reqUri = $this->app->managers->pages->getLink($page);
        $rawReqUri = $this->app->request->getLink();

        if ($reqUri != $rawReqUri) {
            $view->setCanonical($reqUri);
        }

        $params['site'] = $this->app->getSite();

        $this->addMeta(
            $title = $page->make('meta_title', $params['meta_title'] ?? null, $params),
            $description = $page->make('meta_description', $params['meta_description'] ?? null, $params),
            $page->make('meta_keywords', $params['meta_keywords'] ?? null, $params),
            $params['meta_og_type'] ?? 'index' == $key ? 'website' : 'article',
            $reqUri,
            $page->make('meta_og_title', $params['meta_og_title'] ?? $title, $params),
            $page->make('meta_og_description', $params['meta_og_description'] ?? $description, $params),
            $params['meta_og_image'] ?? $params['image'] ?? null,
            $params['meta_properties'] ?? null,
            $view
        );

        $view->setContentByTemplate($key . '.phtml', [
            'h1' => $page->make('h1', $params['h1'] ?? null, $params),
            'description' => $page->make('description', $params['description'] ?? null, $params)
        ]);
    }
}