<?php

namespace SNOWGIRL_CORE\Http\Exception;

use SNOWGIRL_CORE\Http\HttpResponse;

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

    public function processResponse(HttpResponse $response)
    {
        if ($this->retryAfter) {
            $response->setHeader('Retry-After', $this->retryAfter);
        }
    }
}