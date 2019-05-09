<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 6/3/17
 * Time: 12:28 PM
 */
namespace SNOWGIRL_CORE\Exception\HTTP;

use SNOWGIRL_CORE\Exception\HTTP;
use SNOWGIRL_CORE\Response;

/**
 * Class InternalServerError
 * @package SNOWGIRL_CORE\Exception\HTTP
 */
class InternalServerError extends HTTP
{
    public function getHttpCode() : int
    {
        return 500;
    }

    public function processResponse(Response $response)
    {
    }
}