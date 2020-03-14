<?php

namespace SNOWGIRL_CORE\Http\Exception;

use SNOWGIRL_CORE\Http\HttpResponse;

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

    public function processResponse(HttpResponse $response)
    {
    }
}