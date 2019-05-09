<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 6/3/17
 * Time: 12:09 PM
 */

namespace SNOWGIRL_CORE\Exception\HTTP;

use SNOWGIRL_CORE\Exception\HTTP;
use SNOWGIRL_CORE\Response;

/**
 * Class MethodNotAllowed
 * @package SNOWGIRL_CORE\Exception\HTTP
 */
class MethodNotAllowed extends HTTP
{
    public function getHttpCode() : int
    {
        return 405;
    }

    /**
     * @todo...
     * @param Response $response
     */
    public function processResponse(Response $response)
    {
    }

    protected $validMethod;

    public function setValidMethod($method)
    {
        $this->validMethod = $method;
        return $this;
    }
}