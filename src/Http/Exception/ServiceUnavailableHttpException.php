<?php

namespace SNOWGIRL_CORE\Http\Exception;

use SNOWGIRL_CORE\Response;

class ServiceUnavailableHttpException extends HttpException
{
    protected $retryAfter;

    public function getHttpCode(): int
    {
        return 503;
    }

    public function setRetryAfter($seconds)
    {
        $this->retryAfter = (int)$seconds;

        return $this;
    }

    /**
     * @param Response $response
     *
     * @throws \SNOWGIRL_CORE\Exception
     */
    public function processResponse(Response $response)
    {
        if ($this->retryAfter) {
            $response->setHeader('Retry-After', $this->retryAfter);
        }
    }
}