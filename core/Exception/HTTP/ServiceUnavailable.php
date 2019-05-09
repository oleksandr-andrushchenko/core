<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 23.01.16
 * Time: 14:18 PM
 */

namespace SNOWGIRL_CORE\Exception\HTTP;

use SNOWGIRL_CORE\Exception\HTTP;
use SNOWGIRL_CORE\Response;

/**
 * Class ServiceUnavailable
 * @package SNOWGIRL_CORE\Exception\HTTP
 */
class ServiceUnavailable extends HTTP
{
    public function getHttpCode() : int
    {
        return 503;
    }

    protected $retryAfter;

    public function setRetryAfter($seconds)
    {
        $this->retryAfter = (int)$seconds;
        return $this;
    }

    /**
     * @param Response $response
     */
    public function processResponse(Response $response)
    {
        if ($this->retryAfter) {
            $response->setHeader('Retry-After', $this->retryAfter);
        }
    }
}