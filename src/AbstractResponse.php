<?php

namespace SNOWGIRL_CORE;

abstract class AbstractResponse
{
    protected $body = '';

    public function setBody($content): AbstractResponse
    {
        $this->body = (string)$content;

        return $this;
    }

    public function addToBody($content): AbstractResponse
    {
        $this->body .= (string)$content;

        return $this;
    }

    public function getBody(): string
    {
        return $this->body;
    }
}