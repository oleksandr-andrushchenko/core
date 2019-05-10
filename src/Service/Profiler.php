<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 6/18/17
 * Time: 1:33 PM
 */

namespace SNOWGIRL_CORE\Service;

use SNOWGIRL_CORE\App;
use SNOWGIRL_CORE\Service\Funcs\Log;
use SNOWGIRL_CORE\Service;

/**
 * Class Profiler
 * @package SNOWGIRL_CORE\Service
 */
abstract class Profiler extends Service
{
    use Log;

    public const FLAG_MEMORY = 1;
    public const FLAG_CPU = 2;
    public const FLAG_NO_BUILT_INS = 3;

    protected $flags = [];
    protected $data;
    protected $host;

    protected $reportId;
    protected $namespace = 'test';

    protected function initialize2(App $app = null)
    {
        parent::initialize2($app);

        $this->setOption('logger', $app->services->logger);
    }

    public function setNamespace($namespace)
    {
        $this->namespace = $namespace;
        return $this;
    }

    abstract protected function isCan();

    public function enable(\Closure $fn = null)
    {
        if (!$this->isCan()) {
            $this->log('Can\'t enable');
            return false;
        }

        $this->_enable();

        $this->log('Enabled');
        $fn && $fn();
        return $this;
    }

    abstract protected function _enable();

    public function disable()
    {
        if (!$this->isCan()) {
            $this->log('Can\'t disable');
            return false;
        }

        $this->data = $this->_disable();

        $this->log('Disabled');
        return true;
    }

    abstract protected function _disable();

    public function setFlags(array $flags)
    {
        $this->flags = array_filter($flags, function ($flag) {
            return in_array($flag, [
                self::FLAG_MEMORY,
                self::FLAG_CPU,
                self::FLAG_NO_BUILT_INS
            ]);
        });
    }

    public function getData()
    {
        return $this->data;
    }

    public function save()
    {
        if (!$this->isCan()) {
            $this->log('Can\'t save');
            return false;
        }

        $this->reportId = $this->_save();

        $this->log('Saved');
        return $this;
    }

    abstract protected function _save();

    /**
     * @return array
     */
    abstract protected function makeFlagsMap();

    protected function makeFlags()
    {
        $output = [];

        foreach ($this->makeFlagsMap() as $self => $specific) {
            if (in_array($self, $this->flags)) {
                $output[] = $specific;
            }
        }

        $output = array_unique($output);

        return $output;
    }

    public function call($namespace, \Closure $fn)
    {
        $old = $this->namespace;
        $uri = $this->setNamespace($namespace)->enable($fn)->save()->getReportUri();
        $this->setNamespace($old);
        return $uri;
    }

    public function getReportId()
    {
        return $this->reportId;
    }

    public function getReportUri()
    {
        return $this->makeReportUri($this->reportId);
    }

    public function makeReportUri($id)
    {
        return $this->host . '/' . $this->_makeReportUri($id);
    }

    abstract protected function _makeReportUri($id);

    abstract public function prepare();
}