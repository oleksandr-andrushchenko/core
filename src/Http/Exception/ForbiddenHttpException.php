<?php

namespace SNOWGIRL_CORE\Http\Exception;

use SNOWGIRL_CORE\Http\HttpResponse;

class ForbiddenHttpException extends HttpException
{
    public function getHttpCode(): int
    {
        return 403;
    }

    public function processResponse(HttpResponse $response)
    {
    }
}