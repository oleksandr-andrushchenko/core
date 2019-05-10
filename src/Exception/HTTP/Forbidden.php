<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 01/11/16
 * Time: 12:26 PM
 */

namespace SNOWGIRL_CORE\Exception\HTTP;

use SNOWGIRL_CORE\Exception\HTTP;
use SNOWGIRL_CORE\Response;

/**
 * Class Forbidden
 * @package SNOWGIRL_CORE\Exception\HTTP
 */
class Forbidden extends HTTP
{
    public function getHttpCode() : int
    {
        return 403;
    }

    public function processResponse(Response $response)
    {
    }
}