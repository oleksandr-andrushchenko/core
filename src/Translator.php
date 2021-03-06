<?php

namespace SNOWGIRL_CORE;

use stdClass;

class Translator extends stdClass
{
    private $namespaces;
    private $dirs;
    private $vocabularies = [];
    private $locales;
    private $locale;
    private $lang;
    private $countries;
    private $cities = [];

    public function __construct(AbstractApp $app)
    {
        $this->namespaces = $app->namespaces;
        $this->dirs = $app->dirs;
        $this->setLocales($app->config('site.locale', ['default' => 'en_EN']));
    }

    public function setLocales(array $locales): Translator
    {
        $this->locales = $locales;
        return $this;
    }

    public function addVocabulary(string $name): Translator
    {
        if (!in_array($name, $this->vocabularies)) {
            if ($files = $this->findFiles($name)) {
                foreach ($files as $file) {
                    /** @noinspection PhpIncludeInspection */
                    foreach ((include $file) as $k => $v) {
                        $this->{$name . '.' . $k} = $v;
                    }
                }
            }

            $this->vocabularies[] = $name;
        }

        return $this;
    }

    public function getVocabulary(string $name): array
    {
        $output = [];

        if (in_array($name, $this->vocabularies)) {
            if ($files = $this->findFiles($name)) {
                foreach ($files as $file) {
                    /** @noinspection PhpIncludeInspection */
                    $output = array_merge($output, include $file);
                }
            }
        } else {
            if ($files = $this->findFiles($name)) {
                foreach ($files as $file) {
                    /** @noinspection PhpIncludeInspection */
                    $tmp = include $file;

                    foreach ($tmp as $k => $v) {
                        $this->{$name . '.' . $k} = $v;
                    }

                    $output = array_merge($output, $tmp);
                }
            }

            $this->vocabularies[] = $name;
        }

        return $output;
    }

    public function findFiles(string $name): array
    {
        $output = [];

        foreach (array_keys($this->namespaces) as $alias) {
            if (file_exists($tmp = $this->dirs[$alias] . '/trans/' . $this->getLang() . '/' . $name . '.php')) {
                $output[] = $tmp;
            }
        }

        $output = array_reverse($output);

        return $output;
    }

    public function setLocale(string $locale, string $default = 'en_EN'): Translator
    {
        if (in_array($locale, $this->locales)) {
            $this->locale = $locale;
        } elseif (isset($this->locales['default'])) {
            $this->locale = $this->locales['default'];
        } elseif ($this->locales) {
            $this->locale = array_values($this->locales)[0];
        } else {
            $this->locale = $default;
        }

        $this->lang = explode('_', $this->getLocale())[0];
        setlocale(LC_TIME, $this->getLocale() . '.utf8');

        return $this;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function getLang(): ?string
    {
        return $this->lang;
    }

    public function makeText(string $key): ?string
    {
        //auto-vocabulary-adding
//        if (true) {
        $exp = explode('.', $key);
        array_pop($exp);
        $vocabulary = implode('.', $exp);
        $this->addVocabulary($vocabulary);
//        }

        $v = $this->$key;

        if (func_num_args() > 1 && $v) {
            $args = func_get_args();
            $args[0] = $v;
            $v = call_user_func_array('sprintf', $args);
        }

        return $v;
    }

    public function __get($k)
    {
        return $k;
    }

    public function __isset($k)
    {
        return property_exists($this, $k);
    }

    public function __call($fn, $args)
    {
        return call_user_func_array([$this, '_'], array_merge([$fn], $args));
    }

    public function getCountry($iso, Geo $geo): ?string
    {
        $countries = $this->countries ?: ($this->countries = $geo->getCountryNames());
        return $countries[$iso];
    }

    public function getCity($countryIso, $cityId, Geo $geo): ?string
    {
        $cities = $this->cities[$countryIso] ?? ($this->cities[$countryIso] = $geo->getCityNames($countryIso));
        return $cities[$cityId];
    }
}