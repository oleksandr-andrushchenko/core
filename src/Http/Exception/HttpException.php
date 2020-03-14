<?php

namespace SNOWGIRL_CORE\Http\Exception;

use SNOWGIRL_CORE\Exception;
use SNOWGIRL_CORE\Http\HttpResponse;

abstract class HttpException extends Exception
{
    abstract public function getHttpCode(): int;

    abstract public function processResponse(HttpResponse $response);
}