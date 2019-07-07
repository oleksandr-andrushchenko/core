<?php

namespace SNOWGIRL_CORE\Exception\HTTP;

use SNOWGIRL_CORE\Exception\HTTP;
use SNOWGIRL_CORE\Response;

class InternalServerError extends HTTP
{
    public function getHttpCode(): int
    {
        return 500;
    }

    public function processResponse(Response $response)
    {
    }
}