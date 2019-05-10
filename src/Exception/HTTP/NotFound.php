<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 12/04/15
 * Time: 14:01 PM
 */

namespace SNOWGIRL_CORE\Exception\HTTP;

use SNOWGIRL_CORE\Exception\HTTP;
use SNOWGIRL_CORE\Response;

/**
 * Class NotFound
 * @package SNOWGIRL_CORE\Exception\HTTP
 */
class NotFound extends HTTP
{
    public function getHttpCode() : int
    {
        return 404;
    }

    protected $nonExisting;

    public function setNonExisting($nonExisting)
    {
        $this->nonExisting = $nonExisting;
        return $this;
    }

    /**
     * @todo nonExisting...
     * @param Response $response
     */
    public function processResponse(Response $response)
    {
    }
}