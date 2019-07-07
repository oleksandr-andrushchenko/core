<?php

namespace SNOWGIRL_CORE\Exception\HTTP;

use SNOWGIRL_CORE\Exception\HTTP;
use SNOWGIRL_CORE\Response;

class ServiceUnavailable extends HTTP
{
    public function getHttpCode(): int
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