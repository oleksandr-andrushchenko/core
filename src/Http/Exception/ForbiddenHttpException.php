<?php

namespace SNOWGIRL_CORE\Http\Exception;

use SNOWGIRL_CORE\Response;

class ForbiddenHttpException extends HttpException
{
    public function getHttpCode(): int
    {
        return 403;
    }

    public function processResponse(Response $response)
    {
    }
}