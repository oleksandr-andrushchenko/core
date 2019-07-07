<?php

namespace SNOWGIRL_CORE\Exception\HTTP;

use SNOWGIRL_CORE\Exception\HTTP;
use SNOWGIRL_CORE\Response;

class NotFound extends HTTP
{
    public function getHttpCode(): int
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
     *
     * @param Response $response
     */
    public function processResponse(Response $response)
    {
    }
}