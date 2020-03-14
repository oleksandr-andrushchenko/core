<?php

namespace SNOWGIRL_CORE\Http\Exception;

use SNOWGIRL_CORE\Http\HttpResponse;

class BadRequestHttpException extends HttpException
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

    public function processResponse(HttpResponse $response)
    {
    }
}