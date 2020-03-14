<?php

namespace SNOWGIRL_CORE\Http\Exception;

use SNOWGIRL_CORE\Response;

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

    /**
     * @todo... invalid param
     *
     * @param Response $response
     */
    public function processResponse(Response $response)
    {
    }
}