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
 * @package SNOWGIRL_CORE\Helper
 */
class Response
{
    public static $codes = [
        200 => 'OK',
        204 => 'No Content',
        400 => 'Bad Request',
        403 => 'Forbidden',
        404 => 'Not Found',
        500 => 'Internal Server Error',
        503 => 'Service Unavailable'
    ];

    protected $body = '';
    protected $headers = [];
    protected $headersRaw = [];
    protected $httpResponseCode = 200;
    protected $isRedirect = false;

    protected function _normalizeHeader($name)
    {
        $filtered = str_replace(['-', '_'], ' ', (string)$name);
        $filtered = ucwords(strtolower($filtered));
        $filtered = str_replace(' ', '-', $filtered);

        return $filtered;
    }

    public function setHeader($name, $value, $replace = false)
    {
        $this->canSendHeaders(true);
        $name = $this->_normalizeHeader($name);
        $value = (string)$value;

        if ($replace) {
            foreach ($this->headers as $key => $header) {
                if ($name == $header['name']) {
                    unset($this->headers[$key]);
                }
            }
        }

        $this->headers[] = [
            'name' => $name,
            'value' => $value,
            'replace' => $replace
        ];

        return $this;
    }

    public function setRedirect($url, $code = 302)
    {
        $this->canSendHeaders(true);
        $this->setHeader('Location', $url, true)
            ->setHttpResponseCode($code);
        return $this;
    }

    public function isRedirect()
    {
        return $this->isRedirect;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function clearHeaders()
    {
        $this->headers = [];

        return $this;
    }

    public function clearHeader($name)
    {
        if (!$this->headers) {
            return $this;
        }

        foreach ($this->headers as $index => $header) {
            if ($name == $header['name']) {
                unset($this->headers[$index]);
            }
        }

        return $this;
    }

    public function setRawHeader($value)
    {
        $this->canSendHeaders(true);

        if ('Location' == substr($value, 0, 8)) {
            $this->isRedirect = true;
        }

        $this->headersRaw[] = (string)$value;
        return $this;
    }

    public function getRawHeaders()
    {
        return $this->headersRaw;
    }

    public function clearRawHeaders()
    {
        $this->headersRaw = [];
        return $this;
    }

    public function clearRawHeader($headerRaw)
    {
        if (!count($this->headersRaw)) {
            return $this;
        }

        $key = array_search($headerRaw, $this->headersRaw);

        if ($key !== false) {
            unset($this->headersRaw[$key]);
        }

        return $this;
    }

    public function clearAllHeaders()
    {
        return $this->clearHeaders()
            ->clearRawHeaders();
    }

    public function setHttpResponseCode($code)
    {
        if (!is_int($code) || (100 > $code) || (599 < $code)) {
            throw new Exception('Invalid HTTP response code');
        }

        if ((300 <= $code) && (307 >= $code)) {
            $this->isRedirect = true;
        } else {
            $this->isRedirect = false;
        }

        $this->httpResponseCode = $code;

        return $this;
    }

    public function getHttpResponseCode()
    {
        return $this->httpResponseCode;
    }

    public function canSendHeaders($throw = false)
    {
        $ok = headers_sent($file, $line);

        if ($ok && $throw) {
            throw new Exception('Cannot send headers; headers already sent in ' . $file . ', line ' . $line);
        }

        return !$ok;
    }

    public function sendHeaders()
    {
        if (count($this->headersRaw) || count($this->headers) || (200 != $this->httpResponseCode)) {
            $this->canSendHeaders(true);
        } elseif (200 == $this->httpResponseCode) {
            return $this;
        }

        $httpCodeSent = false;

        foreach ($this->headersRaw as $header) {
            if (!$httpCodeSent && $this->httpResponseCode) {
                header($header, true, $this->httpResponseCode);
                $httpCodeSent = true;
            } else {
                header($header);
            }
        }

        foreach ($this->headers as $header) {
            if (!$httpCodeSent && $this->httpResponseCode) {
                header($header['name'] . ': ' . $header['value'], $header['replace'], $this->httpResponseCode);
                $httpCodeSent = true;
            } else {
                header($header['name'] . ': ' . $header['value'], $header['replace']);
            }
        }

        if (!$httpCodeSent) {
            header('HTTP/1.1 ' . $this->httpResponseCode);
        }

        return $this;
    }

    public function setBody($content)
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

    public function send($die = false)
    {
        $this->sendHeaders();
        $this->outputBody();

        if ($die) {
            die;
        }

        return $this;
    }

    public function setContentType($v)
    {
        $this->setHeader('Content-Type', $v);
        return $this;
    }

    public function setHTML($code, $body = '')
    {
        return $this->setHttpResponseCode($code)
            ->setContentType('text/html')
            ->setBody($body);
    }

    public function setJSON($code, $body = null)
    {
        return $this->setHttpResponseCode($code)
            ->setContentType('application/json')
            ->setBody(json_encode(is_array($body) ? $body : ($body ? ['body' => $body] : [])));
    }

    public function setNoIndexNoFollow()
    {
        $this->setHeader('X-Robots-Tag', 'noindex,nofollow', true);
        return $this;
    }

    public function __toString()
    {
        ob_start();
        $this->send();
        return ob_get_clean();
    }
}