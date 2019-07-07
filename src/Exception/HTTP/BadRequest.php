<?php

namespace SNOWGIRL_CORE\Exception\HTTP;

use SNOWGIRL_CORE\Exception\HTTP;
use SNOWGIRL_CORE\Response;

class BadRequest extends HTTP
{
    public function getHttpCode(): int
    {
        return 400;
    }

    protected $invalidParam;

    public function setInvalidParam($invalidParam)
    {
        $this->invalidParam = $invalidParam;
        return $this;
    }

    /**
     * @todo... invalid param
     *
     * @param Response $response
     */
    public function processResponse(Response $response)
    {
    }
}