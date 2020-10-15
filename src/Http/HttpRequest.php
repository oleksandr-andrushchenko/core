<?php

namespace SNOWGIRL_CORE\Http;

use SNOWGIRL_CORE\AbstractRequest;


/**
 * Class HttpRequest
 *
 * @property HttpApp app
 * @package SNOWGIRL_CORE\Http
 */
class HttpRequest extends AbstractRequest
{
    public const SCHEME_HTTP = 'http';
    public const SCHEME_HTTPS = 'https';

    private $isAdminIp;
    private $uri;
    private $baseUrl;
    private $pathInfo;
    private $streamParams;

    /**
     * @var HttpClient
     */
    private $client;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var Cookie
     */
    private $cookie;

    /**
     * @var Device
     */
    private $device;

    public function isAjax(): bool
    {
        return 'xmlhttprequest' == strtolower($this->getServer('HTTP_X_REQUESTED_WITH'));
    }

    public function isJSON(): bool
    {
        return preg_match("/^application\/json/", $this->getHeader('Accept'));
    }

    public function isOuter(): bool
    {
        return 'admin' != $this->getController();
    }

    public function isAdminIp(): bool
    {
        if (null === $this->isAdminIp) {
            if ($ips = $this->app->config('app.admin_ip', [])) {
                $clientIp = $this->getClientIp();

                $this->isAdminIp = false;

                foreach ($ips as $ip) {
                    if (0 === strpos($clientIp, $ip)) {
                        $this->isAdminIp = true;
                        break;
                    }
                }
            } else {
                $this->isAdminIp = false;
            }
        }

        return $this->isAdminIp;
    }

    public function isPathFile(): bool
    {
        return false !== strpos($this->getPathInfo(), '.');
    }

    public function get(string $key, $default = null)
    {
        switch (true) {
            case isset($this->params[$key]):
                return $this->params[$key];
            case isset($_GET[$key]):
                return $_GET[$key];
            case isset($_POST[$key]):
                return $_POST[$key];
            case isset($_COOKIE[$key]):
                return $_COOKIE[$key];
            case isset($_FILES[$key]):
                return $_FILES[$key];
            case ($key == 'REQUEST_URI'):
                return $this->getUri();
            case ($key == 'PATH_INFO'):
                return $this->getPathInfo();
            case isset($_SERVER[$key]):
                return $_SERVER[$key];
            case isset($_ENV[$key]):
                return $_ENV[$key];
            case isset($this->getStreamParams()[$key]):
                return $this->getStreamParams()[$key];
            default:
                return $default;
        }
    }

    public function has(string $key): bool
    {
        switch (true) {
            case isset($this->params[$key]):
                return true;
            case isset($_GET[$key]):
                return true;
            case isset($_POST[$key]):
                return true;
            case isset($_COOKIE[$key]):
                return true;
            case isset($_FILES[$key]):
                return true;
            case isset($_SERVER[$key]):
                return true;
            case isset($_ENV[$key]):
                return true;
            case isset($this->getStreamParams()[$key]):
                return true;
            default:
                return false;
        }
    }

    public function getServer(string $key = null, $default = null)
    {
        if (null === $key) {
            return $_SERVER;
        }

        return $_SERVER[$key] ?? $default;
    }

    public function setUri($uri): HttpRequest
    {
        $this->uri = $uri;

        return $this;
    }

    public function getUri(): string
    {
        if (null === $this->uri) {
            if (isset($_SERVER['REQUEST_URI'])) {
                $uri = $_SERVER['REQUEST_URI'];
                $schemeAndHttpHost = $this->getScheme() . '://' . $this->getHttpHost();

                if (0 === strpos($uri, $schemeAndHttpHost)) {
                    $uri = substr($uri, strlen($schemeAndHttpHost));
                }

                $this->uri = urldecode($uri);
            } else {
                $this->uri = null;
            }
        }

        return $this->uri;
    }

    public function getLink(bool $domain = false): string
    {
        if ($domain) {
            return $this->getScheme() . '://' . $this->getHttpHost() . $this->getUri();
        }

        return $this->getUri();
    }

    public function setBaseUrl($url): HttpRequest
    {
        $this->baseUrl = $url;

        return $this;
    }

    public function getBaseUrl(bool $raw = false): string
    {
        if (null === $this->baseUrl) {
            $filename = (isset($_SERVER['SCRIPT_FILENAME'])) ? basename($_SERVER['SCRIPT_FILENAME']) : '';

            if (isset($_SERVER['SCRIPT_NAME']) && basename($_SERVER['SCRIPT_NAME']) === $filename) {
                $baseUrl = $_SERVER['SCRIPT_NAME'];
            } elseif (isset($_SERVER['PHP_SELF']) && basename($_SERVER['PHP_SELF']) === $filename) {
                $baseUrl = $_SERVER['PHP_SELF'];
            } elseif (isset($_SERVER['ORIG_SCRIPT_NAME']) && basename($_SERVER['ORIG_SCRIPT_NAME']) === $filename) {
                $baseUrl = $_SERVER['ORIG_SCRIPT_NAME'];
            } else {
                $path = $_SERVER['PHP_SELF'] ?? '';
                $file = $_SERVER['SCRIPT_FILENAME'] ?? '';
                $segs = explode('/', trim($file, '/'));
                $segs = array_reverse($segs);
                $index = 0;
                $last = count($segs);
                $baseUrl = '';

                do {
                    $seg = $segs[$index];
                    $baseUrl = '/' . $seg . $baseUrl;
                    ++$index;
                } while (($last > $index) && (false !== ($pos = strpos($path, $baseUrl))) && (0 != $pos));
            }

            $requestUri = $this->getUri();

            while (true) {
                if (0 === strpos($requestUri, $baseUrl)) {
                    $this->baseUrl = $baseUrl;
                    break;
                }

                if (0 === strpos($requestUri, dirname($baseUrl))) {
                    $this->baseUrl = rtrim(dirname($baseUrl), '/');
                    break;
                }

                $truncatedRequestUri = $requestUri;

                if (($pos = strpos($requestUri, '?')) !== false) {
                    $truncatedRequestUri = substr($requestUri, 0, $pos);
                }

                $basename = basename($baseUrl);

                if (empty($basename) || !strpos($truncatedRequestUri, $basename)) {
                    $this->baseUrl = '';
                    break;
                }

                if ((strlen($requestUri) >= strlen($baseUrl)) && ((false !== ($pos = strpos($requestUri, $baseUrl))) && ($pos !== 0))) {
                    $baseUrl = substr($requestUri, 0, $pos + strlen($baseUrl));
                }

                $this->baseUrl = rtrim($baseUrl, '/');
                break;
            }
        }

        return $raw ? $this->baseUrl : urldecode($this->baseUrl);
    }

    public function setPathInfo($path): HttpRequest
    {
        $this->pathInfo = $path;

        return $this;
    }

    public function getPathInfo(): string
    {
        if (null === $this->pathInfo) {
            $this->pathInfo = $this->getPathInfoByUri($this->getUri());
        }

        return $this->pathInfo;
    }

    public function getPathInfoByUri(string $uri): string
    {
        $output = $uri;

        if ($tmp = parse_url($output, PHP_URL_PATH)) {
            $output = $tmp;
        }

        if ($pos = strpos($output, '?')) {
            $output = substr($output, 0, $pos);
        }

        $base = $this->getScheme() . '://' . $this->getHttpHost();

        if (0 === strpos($output, $base)) {
            $output = substr($output, strlen($base));
        }

        if (($base = $this->getBaseUrl()) && 0 === strpos($uri, $base)) {
            $output = substr($output, strlen($base));
        } elseif (($base = $this->getBaseUrl(true)) && 0 === strpos($uri, $base)) {
            $output = substr($output, strlen($base));
        }

        return $output;
    }

    public function getGetParams()
    {
        return isset($_GET) && is_array($_GET) ? $_GET : [];
    }

    public function getGetParam($key, $default = null)
    {
        $tmp = $this->getGetParams();

        return $tmp[$key] ?? $default;
    }

    public function getPostParams(): array
    {
        return isset($_POST) && is_array($_POST) ? $_POST : [];
    }

    public function getPostParam(string $key, $default = null)
    {
        $tmp = $this->getPostParams();

        return $tmp[$key] ?? $default;
    }

    public function getFileParams(): array
    {
        return isset($_FILES) && is_array($_FILES) ? $_FILES : [];
    }

    public function getFileParam(string $key, $default = null)
    {
        $tmp = $this->getFileParams();

        return $tmp[$key] ?? $default;
    }

    public function getStreamParams(): array
    {
        if (null === $this->streamParams) {
            parse_str(file_get_contents("php://input"), $this->streamParams);
        }

        return $this->streamParams;
    }

    /**
     * @param $spec
     * @param null $value
     * @return HttpRequest
     * @throws \Exception
     */
    public function setQuery($spec, $value = null): HttpRequest
    {
        if ((null === $value) && !is_array($spec)) {
            throw new \Exception('Invalid value passed to setQuery(); must be either array of values or key/value pair');
        }

        if ((null === $value) && is_array($spec)) {
            foreach ($spec as $key => $value) {
                $this->setQuery($key, $value);
            }

            return $this;
        }

        $_GET[(string) $spec] = $_REQUEST[(string) $spec] = $value;

        return $this;
    }

    public function getQuery($key = null, $default = null)
    {
        if (null === $key) {
            return $_GET;
        }

        return $_GET[$key] ?? $default;
    }

    public function getParams(): array
    {
        $return = parent::getParams();

        $return += $this->getGetParams();

        if (isset($_POST) && is_array($_POST)) {
            $return += $_POST;
        }

        return $return;
    }


    public function getMethod(): string
    {
        return $this->getServer('REQUEST_METHOD', 'GET');
    }

    public function isPost(): bool
    {
        return 'POST' == $this->getMethod();
    }

    public function isPatch(): bool
    {
        return 'PATCH' == $this->getMethod();
    }

    public function isGet(): bool
    {
        return 'GET' == $this->getMethod();
    }

    public function isDelete(): bool
    {
        return 'DELETE' == $this->getMethod();
    }

    public function getHeader(string $header): ?string
    {
        $temp = strtoupper(str_replace('-', '_', $header));

        if (isset($_SERVER['HTTP_' . $temp])) {
            return $_SERVER['HTTP_' . $temp];
        }

        if (isset($_SERVER[$temp]) && in_array($temp, ['CONTENT_TYPE', 'CONTENT_LENGTH'])) {
            return $_SERVER[$temp];
        }

        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();

            if (isset($headers[$header])) {
                return $headers[$header];
            }

            $header = strtolower($header);

            foreach ($headers as $key => $value) {
                if (strtolower($key) == $header) {
                    return $value;
                }
            }
        }

        return null;
    }

    public function getScheme(): string
    {
        return 'on' == $this->getServer('HTTPS') ? self::SCHEME_HTTPS : self::SCHEME_HTTP;
    }

    public function getHttpHost(): string
    {
        $host = $this->getServer('HTTP_HOST');

        if (!empty($host)) {
            return $host;
        }

        $scheme = $this->getScheme();
        $name = $this->getServer('SERVER_NAME');
        $port = $this->getServer('SERVER_PORT');

        if (null === $name) {
            return '';
        }

        if (($scheme == self::SCHEME_HTTP && $port == 80) || ($scheme == self::SCHEME_HTTPS && $port == 443)) {
            return $name;
        }

        return $name . ':' . $port;
    }

    public function getClient(): HttpClient
    {
        if (null === $this->client) {
            $this->client = $this->app->container->getObject('Http\HttpClient', $this, $this->app->managers->users);
        }

        return $this->client;
    }

    public function getSession(): Session
    {
        if (null === $this->session) {
            $this->session = new Session($this);
        }

        return $this->session;
    }

    public function getCookie(): Cookie
    {
        if (null === $this->cookie) {
            $this->cookie = new Cookie($this);
        }

        return $this->cookie;
    }

    public function getClientIp(bool $checkProxy = false)
    {
        return $this->getClient()->getIp($checkProxy);
    }

    /**
     * @todo log into separate file...
     * @todo if relative?
     * @todo is close session?
     * @param $url
     * @param int $code
     * @throws \SNOWGIRL_CORE\Exception
     */
    public function redirect($url, $code = 302)
    {
        $this->app->container->logger->info('redirect', [
            'from' => $this->getServer('REQUEST_URI'),
            'to' => $url,
            'code' => $code,
        ]);

        $this->app->response->setRedirect($url, $code);
        $this->app->response->sendHeaders();

        die;
//        return true;
    }

    /**
     * @param string $route
     * @param array $params
     * @param int $code
     * @throws \SNOWGIRL_CORE\Exception
     */
    public function redirectToRoute(string $route, $params = [], $code = 302)
    {
        return $this->redirect($this->app->router->makeLink($route, $params), $code);
    }

    /**
     * @param int $code
     * @throws \SNOWGIRL_CORE\Exception
     */
    public function redirectBack($code = 302)
    {
        return $this->redirect($this->getReferer(), $code);
    }

    public function isCrawlerOrBot(): bool
    {
        return $this->getDevice()->isRobot();
    }

    public function isWeAreReferer(&$referer = null): bool
    {
        if ($referer = $this->getReferer()) {
            $tmp2 = parse_url($referer);

            if (empty($tmp2['scheme']) || empty($tmp2['host'])) {
                return false;
            }

            if ($tmp2['scheme'] . '://' . $tmp2['host'] == $this->app->config('domain.master')) {
                return true;
            }
        }

        return false;
    }

    public function getDevice(): Device
    {
        if (null === $this->device) {
            $this->device = new Device($this);
        }

        return $this->device;
    }

    public function getReferer(): ?string
    {
        return $this->getServer('HTTP_REFERER');
    }

    public function getUserAgent(): ?string
    {
        return $this->getServer('HTTP_USER_AGENT');
    }

    public function getBrowser(): ?string
    {
        $ua = $this->getUserAgent();

        if (preg_match('/MSIE/i', $ua)) {
            return 'ie';
        }

        if (preg_match('/Firefox/i', $ua)) {
            return 'firefox';
        }

        if (preg_match('/Chrome/i', $ua)) {
            return 'chrome';
        }

        if (preg_match('/Safari/i', $ua)) {
            return 'safari';
        }

        if (preg_match('/Opera/i', $ua)) {
            return 'opera';
        }

        return null;
    }
}