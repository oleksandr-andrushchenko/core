<?php
/**
 * Created by JetBrains PhpStorm.
 * User: snowgirl
 * Date: 09.09.13
 * Time: 8:57
 * To change this template use File | Settings | File Templates.
 */
namespace SNOWGIRL_CORE;

/**
 * @method seo_title
 * @method seo_description
 * @method seo_keywords
 * @method user_rate
 * @method event_rate
 * @method load_x_more_from_y
 * @method subject
 * Class Sv_Translator
 */
class Translator extends \stdClass
{
    protected $namespaces;
    protected $dirs;
    protected $locales;

    public function __construct(App $app)
    {
        $this->namespaces = $app->namespaces;
        $this->dirs = $app->dirs;
        $this->locales = $app->config->site->locale(['default' => 'en_EN']);
    }

    protected $vocabularies = [];

    /**
     * @param $name
     * @return $this
     */
    public function addVocabulary($name)
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

    public function getVocabulary($name)
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

    /**
     * @param $name
     * @return array
     */
    public function findFiles($name)
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

    protected $locale;

    public function setLocale($locale, $default = 'en_EN')
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

    public function getLocale()
    {
        return $this->locale;
    }

    protected $lang;

    public function getLang()
    {
        return $this->lang;
    }

    public function makeText($k)
    {
        //auto-vocabulary-adding
//        if (true) {
        $exp = explode('.', $k);
        array_pop($exp);
        $vocabulary = implode('.', $exp);
        $this->addVocabulary($vocabulary);
//        }

        $v = $this->$k;

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

    protected $countries;

    public function getCountry($iso, Geo $geo)
    {
        $countries = $this->countries ?: ($this->countries = $geo->getCountryNames());
        return $countries[$iso];
    }

    protected $cities = [];

    public function getCity($countryIso, $cityId, Geo $geo)
    {
        $cities = $this->cities[$countryIso] ?? ($this->cities[$countryIso] = $geo->getCityNames($countryIso));
        return $cities[$cityId];
    }
}