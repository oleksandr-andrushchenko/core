<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 7/15/17
 * Time: 12:22 PM
 */

namespace SNOWGIRL_CORE;

use SNOWGIRL_CORE\Service\Funcs\Log;

/**
 * Class Service
 * @package SNOWGIRL_CORE
 */
abstract class Service extends Configurable
{
    use Log;

    public function __construct($config, App $app = null)
    {
        $this->initialize2($app);
        parent::__construct($config);
    }

    protected function initialize2(App $app = null)
    {
    }
}