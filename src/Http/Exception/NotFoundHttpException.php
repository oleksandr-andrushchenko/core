<?php

namespace SNOWGIRL_CORE\Http\Exception;

use SNOWGIRL_CORE\Response;

class NotFoundHttpException extends HttpException
{
    protected $nonExisting;

    public function getHttpCode(): int
    {
        return 404;
    }

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