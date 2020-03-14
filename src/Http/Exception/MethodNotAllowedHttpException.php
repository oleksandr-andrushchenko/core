<?php

namespace SNOWGIRL_CORE\Http\Exception;

use SNOWGIRL_CORE\Response;

class MethodNotAllowedHttpException extends HttpException
{
    protected $validMethod;

    public function getHttpCode(): int
    {
        return 405;
    }

    /**
     * @todo...
     *
     * @param Response $response
     */
    public function processResponse(Response $response)
    {
    }

    public function setValidMethod($method)
    {
        $this->validMethod = $method;

        return $this;
    }
}