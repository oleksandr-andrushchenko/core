<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 10/31/15
 * Time: 9:07 PM
 */
namespace SNOWGIRL_CORE\Service\Logger;

use SNOWGIRL_CORE\Service\Logger;

/**
 * Class Disc
 * @package SNOWGIRL_CORE\Service\Logger
 */
class Disc extends Logger
{
    protected $dir = '@root/var/log';
    protected $ext = 'txt';

    protected function _setName($name)
    {
        return $this->dir . '/' . $name . '.' . $this->ext;
    }

    protected function _make($msg = '')
    {
        if ($this->name) {
            error_log($msg, 3, $this->name);
        }
    }

    protected function _setAsErrorLog()
    {
        if (file_exists($this->name) && is_writable($this->name)) {
            ini_set('error_log', $this->name);
        }
    }
}