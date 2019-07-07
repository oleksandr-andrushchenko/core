<?php

namespace SNOWGIRL_CORE\Exception;

use SNOWGIRL_CORE\Exception;
use SNOWGIRL_CORE\Response;

abstract class HTTP extends Exception
{
    abstract public function getHttpCode(): int;

    abstract public function processResponse(Response $response);
}