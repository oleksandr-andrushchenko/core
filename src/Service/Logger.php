<?php

namespace SNOWGIRL_CORE\Service;

use SNOWGIRL_CORE\App;
use SNOWGIRL_CORE\Exception;
use SNOWGIRL_CORE\Service;

abstract class Logger extends Service
{
    use ToggleTrait;

    //@todo write everytime...
    public const TYPE_ERROR = 'ERR';
    //@todo write everytime...
    public const TYPE_WARN = 'WARN';
    public const TYPE_INFO = 'INFO';
    //@todo write in case of $app->config->app->debug=1 only...
    public const TYPE_DEBUG = 'DEBUG';

    protected $length = false;
    protected $paramToLog = [];

    protected static $instances = [];

    protected $time;

    protected function initialize2(App $app = null)
    {
        parent::initialize2($app);
        $this->time = time();
    }

    /**
     * @param $name
     *
     * @return Logger
     */
    public function get($name)
    {
        if (isset(self::$instances[$name])) {
            return self::$instances[$name];
        }

        $logger = clone $this;
        $logger->setName($name);

        self::$instances[$name] = $logger;

        return $logger;
    }

    public function setLength($limit)
    {
        $this->length = $limit;
        return $this;
    }

    protected $name;

    public function setName($name, $raw = false)
    {
        $this->name = $raw ? $name : $this->_setName($name);
        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return string
     */
    abstract protected function _setName($name);

    public function addParamToLog($name, $value)
    {
        $this->paramToLog[$name] = $value;
        return $this;
    }

    /**
     * @param        $msg
     * @param string $type
     *
     * @return $this
     */
    public function makeForce($msg, $type = self::TYPE_DEBUG)
    {
        $isEnabled = $this->enabled;

        $this->enable()->make($msg, $type);

        if (!$isEnabled) {
            $this->disable();
        }

        return $this;
    }

    protected $onErrorMade;

    public function setOnErrorMade(\Closure $onErrorMade)
    {
        $this->onErrorMade = $onErrorMade;
        return $this;
    }

    public function make($msg, $type = self::TYPE_DEBUG, $raw = false)
    {
        if (!$this->enabled && self::TYPE_ERROR != $type) {
            return $this;
        }

        if (self::TYPE_ERROR == $type) {
            $length = $this->length;
            $this->length = null;
        }

        if (self::TYPE_INFO == $type) {
            $raw = true;
        }

        if (!$raw) {
            $tmp = [];
//            $tmp[] = $this->time;
            $tmp[] = $type;
            $tmp[] = $msg;
//            $tmp = array_merge($tmp, $this->paramToLog);

            $msg = implode(' ', $tmp);

            if ($this->length && (strlen($msg) > $this->length)) {
                $msg = substr($msg, 0, $this->length) . '...';
            }
        }

        $msg = $msg . "\n";

        $this->_make($msg);

        if (isset($length)) {
            $this->length = $length;
        }

        if (self::TYPE_ERROR == $type) {
            $this->onErrorMade && is_callable($this->onErrorMade) && call_user_func($this->onErrorMade, $msg);
        }

        return $this;
    }

    public function makeException(\Exception $ex, $type = self::TYPE_ERROR)
    {
        if ($ex instanceof Exception) {
            if ($ex->isLogged()) {
                return $this;
            }

            $ex->setLogged();
        }

        $tmp = [
            'File: ' . $ex->getFile(),
            'Line: ' . $ex->getLine(),
            'Code: ' . $ex->getCode(),
            'Body: ' . $ex->getMessage(),
            'Trace: ' . $ex->getTraceAsString()
        ];

        if ($ex = $ex->getPrevious()) {
            $tmp = array_merge($tmp, [
                '-------------Previous-------------',
                'File: ' . $ex->getFile(),
                'Line: ' . $ex->getLine(),
                'Code: ' . $ex->getCode(),
                'Body: ' . $ex->getMessage(),
                'Trace: ' . $ex->getTraceAsString()
            ]);
        }

        return $this->make(implode("\r\n", $tmp), $type);
    }

    public function makeEmpty()
    {
        if (!$this->enabled) {
            return $this;
        }

        $this->_make();

        return $this;
    }

    abstract protected function _make($msg = '');

    public function setAsErrorLog()
    {
        $this->_setAsErrorLog();
        return $this;
    }

    abstract protected function _setAsErrorLog();

    protected function log($msg, $type = Logger::TYPE_DEBUG, $raw = false)
    {
        $this->make($msg, $type, $raw);
        return $this;
    }
}