<?php

namespace SNOWGIRL_CORE\Exception\HTTP;

use SNOWGIRL_CORE\Exception\HTTP;
use SNOWGIRL_CORE\Response;

class MethodNotAllowed extends HTTP
{
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

    protected $validMethod;

    public function setValidMethod($method)
    {
        $this->validMethod = $method;
        return $this;
    }
}