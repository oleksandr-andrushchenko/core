<?php

namespace SNOWGIRL_CORE\Exception\HTTP;

use SNOWGIRL_CORE\Exception\HTTP;
use SNOWGIRL_CORE\Response;

class Forbidden extends HTTP
{
    public function getHttpCode(): int
    {
        return 403;
    }

    public function processResponse(Response $response)
    {
    }
}