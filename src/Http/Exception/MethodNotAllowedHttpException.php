<?php

namespace SNOWGIRL_CORE\Http\Exception;

use SNOWGIRL_CORE\Http\HttpResponse;

class MethodNotAllowedHttpException extends HttpException
{
    protected $validMethod;

    public function getHttpCode(): int
    {
        return 405;
    }

    public function processResponse(HttpResponse $response)
    {
    }

    public function setValidMethod($method)
    {
        $this->validMethod = $method;

        return $this;
    }
}