<?php

namespace SNOWGIRL_CORE\Http;

use SNOWGIRL_CORE\AbstractResponse;
use SNOWGIRL_CORE\Exception;

class HttpResponse extends AbstractResponse
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
     * @param bool $replace
     * @return HttpResponse
     * @throws Exception
     */
    public function setHeader(string $name, string $value, bool $replace = false): HttpResponse
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

    /**
     * @param string $url
     * @param int $code
     *
     * @return HttpResponse
     * @throws Exception
     */
    public function setRedirect(string $url, int $code = 302): HttpResponse
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

    public function clearHeaders(): HttpResponse
    {
        $this->headers = [];

        return $this;
    }

    public function clearHeader(string $name): HttpResponse
    {
        foreach ($this->headers as $index => $header) {
            if ($name == $header[0]) {
                unset($this->headers[$index]);
            }
        }

        return $this;
    }

    /**
     * @param string $value
     *
     * @return HttpResponse
     * @throws Exception
     */
    public function setRawHeader(string $value): HttpResponse
    {
        $this->canSendHeaders(true);
        $this->headersRaw[] = $value;

        return $this;
    }

    public function getRawHeaders(): array
    {
        return $this->headersRaw;
    }

    public function clearRawHeaders(): HttpResponse
    {
        $this->headersRaw = [];

        return $this;
    }

    public function clearRawHeader(string $headerRaw): HttpResponse
    {
        $key = array_search($headerRaw, $this->headersRaw);

        if ($key !== false) {
            unset($this->headersRaw[$key]);
        }

        return $this;
    }

    public function clearAllHeaders(): HttpResponse
    {
        return $this->clearHeaders()
            ->clearRawHeaders();
    }

    /**
     * @param int $code
     * @return HttpResponse
     * @throws Exception
     */
    public function setCode(int $code): HttpResponse
    {
        if ((100 > $code) || (599 < $code)) {
            throw new Exception('Invalid HTTP response code');
        }

        $this->code = $code;

        return $this;
    }

    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * @param bool $throw
     *
     * @return bool
     * @throws Exception
     */
    public function canSendHeaders(bool $throw = false): bool
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
     * @return HttpResponse
     * @throws Exception
     */
    public function sendHeaders(): HttpResponse
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

    public function outputBody()
    {
        echo $this->body;
    }

    /**
     * @param bool $die
     *
     * @return HttpResponse
     * @throws Exception
     */
    public function send(bool $die = false): HttpResponse
    {
        $this->sendHeaders();
        $this->outputBody();

        if ($die) {
            die;
        }

        return $this;
    }

    /**
     * @param string $v
     *
     * @return HttpResponse
     * @throws Exception
     */
    public function setContentType(string $v): HttpResponse
    {
        $this->setHeader('Content-Type', $v);

        return $this;
    }

    /**
     * @param int $code
     * @param string $body
     *
     * @return AbstractResponse|HttpResponse
     * @throws Exception
     */
    public function setHTML(int $code, string $body = ''): HttpResponse
    {
        return $this->setCode($code)
            ->setContentType('text/html')
            ->setBody($body);
    }

    /**
     * @param int $code
     * @param null $body
     *
     * @return AbstractResponse|HttpResponse
     * @throws Exception
     */
    public function setJSON(int $code, $body = null): HttpResponse
    {
        return $this->setCode($code)
            ->setContentType('application/json')
            ->setBody(json_encode(is_array($body) ? $body : ($body ? ['body' => $body] : [])));
    }

    /**
     * @return HttpResponse
     * @throws Exception
     */
    public function setNoIndexNoFollow(): HttpResponse
    {
        $this->setHeader('X-Robots-Tag', 'noindex,nofollow', true);

        return $this;
    }

    /**
     * @return string
     * @throws Exception
     */
    public function __toString(): string
    {
        ob_start();
        $this->send();
        return ob_get_clean();
    }
}