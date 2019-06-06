<?php
/**
 * Created by JetBrains PhpStorm.
 * User: snowgirl
 * Date: 05.04.15
 * Time: 18:31
 * To change this template use File | Settings | File Templates.
 */

namespace SNOWGIRL_CORE;

/**
 * Class Response
 *
 * @package SNOWGIRL_CORE
 */
class Response
{
    protected $codes = [
        200 => 'OK',
        204 => 'No Content',
        400 => 'Bad Request',
        403 => 'Forbidden',
        404 => 'Not Found',
        500 => 'Internal Server Error',
        503 => 'Service Unavailable'
    ];

    protected $code = 200;
    protected $body = '';

    protected $headers = [];
    protected $headersRaw = [];

    /**
     * @param string $name
     * @param string $value
     * @param bool   $replace
     *
     * @return Response
     * @throws Exception
     */
    public function setHeader(string $name, string $value, bool $replace = false): self
    {
        $this->canSendHeaders(true);

        $name = str_replace(['-', '_'], ' ', $name);
        $name = ucwords(strtolower($name));
        $name = str_replace(' ', '-', $name);

        if ($replace) {
            foreach ($this->headers as $index => $header) {
                if ($name == $header[0]) {
                    unset($this->headers[$index]);
                }
            }
        }

        $this->headers[] = [$name, $value, $replace];

        return $this;
    }

    public function setRedirect(string $url, int $code = 302): self
    {
        $this->canSendHeaders(true);
        $this->setHeader('Location', $url, true)
            ->setCode($code);

        return $this;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function clearHeaders(): self
    {
        $this->headers = [];

        return $this;
    }

    public function clearHeader(string $name): self
    {
        foreach ($this->headers as $index => $header) {
            if ($name == $header[0]) {
                unset($this->headers[$index]);
            }
        }

        return $this;
    }

    public function setRawHeader(string $value): self
    {
        $this->canSendHeaders(true);
        $this->headersRaw[] = $value;

        return $this;
    }

    public function getRawHeaders(): array
    {
        return $this->headersRaw;
    }

    public function clearRawHeaders(): self
    {
        $this->headersRaw = [];

        return $this;
    }

    public function clearRawHeader(string $headerRaw): self
    {
        $key = array_search($headerRaw, $this->headersRaw);

        if ($key !== false) {
            unset($this->headersRaw[$key]);
        }

        return $this;
    }

    public function clearAllHeaders(): self
    {
        return $this->clearHeaders()
            ->clearRawHeaders();
    }

    public function setCode(int $code): self
    {
        if (!is_int($code) || (100 > $code) || (599 < $code)) {
            throw new Exception('Invalid HTTP response code');
        }

        $this->code = $code;

        return $this;
    }

    public function getCode(): int
    {
        return $this->code;
    }

    public function canSendHeaders($throw = false): bool
    {
        $ok = headers_sent($file, $line);

        if ($ok && $throw) {
            throw new Exception('Cannot send headers; headers already sent in ' . $file . ', line ' . $line);
        }

        return !$ok;
    }

    public function getReasonByCode(int $code): ?string
    {
        return $this->codes[$code] ?? null;
    }

    /**
     * @return $this
     * @throws Exception
     */
    public function sendHeaders(): self
    {
        if (count($this->headersRaw) || count($this->headers) || (200 != $this->code)) {
            $this->canSendHeaders(true);
        } elseif (200 == $this->code) {
            return $this;
        }

        header(rtrim('HTTP/1.1 ' . $this->code . ' ' . $this->getReasonByCode($this->code)));
//        http_response_code($this->code);

        foreach ($this->headersRaw as $header) {
            header($header);
        }

        foreach ($this->headers as [$name, $value, $replace]) {
            header($name . ': ' . $value, $replace);
        }

        return $this;
    }

    public function setBody($content): self
    {
        $this->body = (string)$content;

        return $this;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function outputBody()
    {
        echo $this->body;
    }

    public function send($die = false): self
    {
        $this->sendHeaders();
        $this->outputBody();

        if ($die) {
            die;
        }

        return $this;
    }

    public function setContentType($v): self
    {
        $this->setHeader('Content-Type', $v);

        return $this;
    }

    public function setHTML(int $code, $body = ''): self
    {
        return $this->setCode($code)
            ->setContentType('text/html')
            ->setBody($body);
    }

    public function setJSON(int $code, $body = null): self
    {
        return $this->setCode($code)
            ->setContentType('application/json')
            ->setBody(json_encode(is_array($body) ? $body : ($body ? ['body' => $body] : [])));
    }

    public function setNoIndexNoFollow(): self
    {
        $this->setHeader('X-Robots-Tag', 'noindex,nofollow', true);

        return $this;
    }

    public function __toString(): string
    {
        ob_start();
        $this->send();
        return ob_get_clean();
    }
}