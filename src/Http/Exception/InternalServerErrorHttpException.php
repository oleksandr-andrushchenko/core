<?php

namespace SNOWGIRL_CORE\Http\Exception;

use SNOWGIRL_CORE\Response;

class InternalServerErrorHttpException extends HttpException
{
    public function getHttpCode(): int
    {
        return 500;
    }

    public function processResponse(Response $response)
    {
    }
}