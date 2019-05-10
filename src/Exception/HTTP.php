<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 6/3/17
 * Time: 12:14 PM
 */
namespace SNOWGIRL_CORE\Exception;

use SNOWGIRL_CORE\Exception;
use SNOWGIRL_CORE\Response;

/**
 * Class HTTP
 * @package SNOWGIRL_CORE\Exception
 */
abstract class HTTP extends Exception
{
    abstract public function getHttpCode() : int;

    abstract public function processResponse(Response $response);
}