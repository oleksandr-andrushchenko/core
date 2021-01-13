<?php

namespace SNOWGIRL_CORE;

use GeoIp2\Database\Reader;
use GeoIp2\Entity\Country;
use GeoIp2\Entity\City;

class Geo
{
    public const CACHE_CITY_NAMES = 'city_names_%s_%s';
    public const CACHE_COUNTRY_NAMES = 'country_names_%s';

    /** @var AbstractApp */
    protected $app;

    protected $maxMindGeo2CountryDir;
    protected $maxMindGeo2CityDir;

    public function __construct(AbstractApp $app)
    {
        $this->app = $app;
        $this->maxMindGeo2CountryDir = $app->getServerDir($app->config('geo.maxmind.geo2.country'));
        $this->maxMindGeo2CityDir = $app->getServerDir($app->config('geo.maxmind.geo2.city'));
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
     *
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
     *
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
     *
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
        $cacheKey = sprintf(self::CACHE_CITY_NAMES, $lang = $this->app->trans->getLang(), $countryIso);

        if (!$this->app->container->memcache->has($cacheKey, $output)) {
            $query = new MysqlQuery(['params' => []]);
            $query->text = implode(' ', [
                'SELECT *',
                'FROM ' . $this->app->container->mysql->quote('city_name'),
                $this->app->container->mysql->makeWhereSQL(['country_iso' => $countryIso, 'lang_iso' => $lang], $query->params),
                $this->app->container->mysql->makeOrderSQL(['name' => SORT_ASC], $query->params)
            ]);

            $output = [];

            foreach ($this->app->container->mysql->reqToArrays($query) as $r) {
                $output[$r['city_id']] = $r['name'];
            }

            $this->app->container->memcache->set($cacheKey, $output);
        }

        return $output;
    }

    /**
     * @return array
     */
    public function getCountryNames()
    {
        $cacheKey = sprintf(self::CACHE_COUNTRY_NAMES, $lang = $this->app->trans->getLang());

        if (!$this->app->container->memcache->has($cacheKey, $output)) {
            $query = new MysqlQuery(['params'=>[]]);
            $query->text = implode(' ',[
                'SELECT *',
                'FROM ' . $this->app->container->mysql->quote('country_name'),
                $this->app->container->mysql->makeWhereSQL(array('lang_iso' => $lang), $query->params),
                $this->app->container->mysql->makeOrderSQL(array('name' => SORT_ASC), $query->params),
            ]);

            $output = [];

            foreach ($this->app->container->mysql->reqToArrays($query) as $r) {
                $output[$r['country_iso']] = $r['name'];
            }

            $this->app->container->memcache->set($cacheKey, $output);
        }

        return $output;
    }
}