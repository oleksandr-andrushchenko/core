<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 1/25/18
 * Time: 12:48 PM
 */
namespace SNOWGIRL_CORE;

use SNOWGIRL_CORE\Ads\AdsTxt;
use SNOWGIRL_CORE\Helper\Classes;

/**
 * @todo...
 * Class Ads
 * @package SNOWGIRL_CORE
 */
class Ads
{
    public const GOOGLE = 'Google';
    public const YANDEX = 'Yandex';

    public const PROVIDERS = [
        self::GOOGLE,
        self::YANDEX
    ];

    /** @var App */
    protected $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    public function getApp()
    {
        return $this->app;
    }

    protected $providers;

    protected function getDefaultProviders()
    {
        if (null === $this->providers) {
            $this->providers = $this->app->config->ads->provider([]);
        }

        return $this->providers;
    }

    protected function normalizeProviders($providers)
    {
        $providers = is_array($providers) ? $providers : [$providers];

        $providers = array_map(function ($v) {
            return ucfirst(strtolower($v));
        }, $providers);

        return array_filter($providers, function ($v) {
            return in_array($v, self::PROVIDERS);
        });
    }

    protected function getProviders($providers = [])
    {
        $output = $this->normalizeProviders($providers);

        if (0 < count($output)) {
            return $output;
        }

        $output = $this->normalizeProviders($this->getDefaultProviders());

        if (0 < count($output)) {
            return $output;
        }

        return [
            self::GOOGLE,
            self::YANDEX
        ];
    }

    /**
     * @param $class
     * @param null $key
     * @return Ad|null
     */
    public function createAd($class, $key = null)
    {
        $name = Classes::getShortName($class);
        $name = strtolower($name);

        if (!$clientId = $this->app->config->ads->{$name . '_client_id'}) {
            return null;
        }

        if ($key) {
            $ads = $this->app->config->ads->{$name . '_ad_id'};

            if (!is_array($ads)) {
                return null;
            }

            if (!isset($ads[$key])) {
                return null;
            }

            $adId = $ads[$key];
        } else {
            $adId = null;
        }

        return new $class($clientId, $adId);
    }

    public function findBanner($widgetClass, $adKey, $adClass = [], View $parent = null)
    {
        foreach ($this->getProviders($adClass) as $adClass) {
            $adClass = 'SNOWGIRL_CORE\\Ad\\' . $adClass;

            if ($ad = $this->createAd($adClass, $adKey)) {
                return new $widgetClass($this->app, $ad, $parent);
            }
        }

        return null;
    }

    /**
     * @return AdsTxt
     */
    public function getAdsTxt()
    {
        return $this->app->getObject('Ads\\AdsTxt', $this);
    }
}