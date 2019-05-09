<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 2/3/17
 * Time: 11:50 PM
 */

namespace SNOWGIRL_CORE;

use GeoIp2\Database\Reader;
use GeoIp2\Entity\Country;
use GeoIp2\Entity\City;

/**
 * Class Geo
 * @package SNOWGIRL_CORE
 */
class Geo
{
    public const CACHE_CITY_NAMES = 'city_names_%s_%s';
    public const CACHE_COUNTRY_NAMES = 'country_names_%s';

    /** @var App */
    protected $app;

    protected $maxMindGeo2CountryDir;
    protected $maxMindGeo2CityDir;

    public function __construct(App $app)
    {
        $this->app = $app;
        $this->maxMindGeo2CountryDir = $app->getServerDir($app->config->geo->{'maxmind.geo2.country'});
        $this->maxMindGeo2CityDir = $app->getServerDir($app->config->geo->{'maxmind.geo2.city'});
    }

    public function __destruct()
    {
        if ($this->maxMindGeo2CountryReader) {
            $this->maxMindGeo2CountryReader->close();
        }

        if ($this->maxMindGeo2CityReader) {
            $this->maxMindGeo2CityReader->close();
        }
    }

    /** @var Reader */
    protected $maxMindGeo2CountryReader;

    /**
     * @return Reader
     */
    protected function getMaxMindGeo2CountryReader()
    {
        if (null === $this->maxMindGeo2CountryReader) {
            if (file_exists($this->maxMindGeo2CountryDir) && is_readable($this->maxMindGeo2CountryDir)) {
                $this->maxMindGeo2CountryReader = new Reader($this->maxMindGeo2CountryDir);
            } else {
                $this->maxMindGeo2CountryReader = false;
            }
        }

        return $this->maxMindGeo2CountryReader;
    }

    /** @var Reader */
    protected $maxMindGeo2CityReader;

    /**
     * @return Reader
     */
    protected function getMaxMindGeo2CityReader()
    {
        if (null === $this->maxMindGeo2CityReader) {
            if (file_exists($this->maxMindGeo2CityDir) && is_readable($this->maxMindGeo2CityDir)) {
                $this->maxMindGeo2CityReader = new Reader($this->maxMindGeo2CityDir);
            } else {
                $this->maxMindGeo2CityReader = false;
            }
        }

        return $this->maxMindGeo2CityReader;
    }

    /**
     * @param $ip
     * @return Country|null
     */
    public function getMaxMindCountryByIp($ip)
    {
        if ($reader = $this->getMaxMindGeo2CountryReader()) {
            /** @var Country $tmp */
            return $reader->country($ip);
        }

        return null;
    }

    /**
     * @param $ip
     * @return array
     */
    public function getCountryByIp($ip)
    {
        if ($tmp = $this->getMaxMindCountryByIp($ip)) {
            $iso = $tmp->country->isoCode;
        } else {
            $iso = $this->getCountryByIpUseGeoPlugin($ip);
        }

        return array(
            'isoCode' => strtolower($iso)
        );
    }

    /**
     * @param $ip
     * @return City|null
     */
    public function getMaxMindCityByIp($ip)
    {
        if ($reader = $this->getMaxMindGeo2CityReader()) {
            /** @var City $tmp */
            return $reader->city($ip);
        }

        return null;
    }

    protected function getCountryByIpUseGeoPlugin($ip)
    {
        $country = @file_get_contents('http://www.geoplugin.net/json.gp?ip=' . $ip);
        $country = json_decode($country, true);
        $country = strtolower($country['geoplugin_countryCode']);
        return $country;
    }

    public function getCityNames($countryIso)
    {
        $key = sprintf(self::CACHE_CITY_NAMES, $lang = $this->app->translator->getLang(), $countryIso);

        return $this->app->services->mcms->call($key, function () use ($countryIso, $lang) {
            $bind = [];
            $sql = [];
            $sql[] = 'SELECT *';
            $sql[] = 'FROM ' . $this->app->services->rdbms->quote('city_name');
            $sql[] = $this->app->services->rdbms->makeWhereSQL(['country_iso' => $countryIso, 'lang_iso' => $lang], $bind);
            $sql[] = $this->app->services->rdbms->makeOrderSQL(['name' => SORT_ASC], $bind);
            $output = array();

            foreach ($this->app->services->rdbms->req(implode(' ', $sql), $bind)->reqToArrays() as $r) {
                $output[$r['city_id']] = $r['name'];
            }

            return $output;
        });
    }

    /**
     * @return array
     */
    public function getCountryNames()
    {
        return $this->app->services->mcms->call(sprintf(self::CACHE_COUNTRY_NAMES, $lang = $this->app->translator->getLang()), function () use ($lang) {
            $bind = array();
            $sql = array();
            $sql[] = 'SELECT *';
            $sql[] = 'FROM ' . $this->app->services->rdbms->quote('country_name');
            $sql[] = $this->app->services->rdbms->makeWhereSQL(array('lang_iso' => $lang), $bind);
            $sql[] = $this->app->services->rdbms->makeOrderSQL(array('name' => SORT_ASC), $bind);
            $output = array();

            foreach ($this->app->services->rdbms->req(implode(' ', $sql), $bind)->reqToArrays() as $r) {
                $output[$r['country_iso']] = $r['name'];
            }

            return $output;
        });
    }
}