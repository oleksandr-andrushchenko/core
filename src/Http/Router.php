<?php

namespace SNOWGIRL_CORE\Http;

use SNOWGIRL_CORE\Exception;
use SNOWGIRL_CORE\Http\Exception\NotFoundHttpException;

class Router
{
    /**
     * @var Route[]
     */
    private $routes = [];
    private $route;
    private $default;
    private $domains;

    public function __construct(HttpApp $app)
    {
        $this->domains = $app->config('domains', []);
    }

    public function addRoute(string $name, Route $route): Router
    {
        $this->routes[$name] = $route;

        return $this;
    }

    /**
     * @param string|null $name
     *
     * @return Route
     * @throws Exception
     */
    public function getRoute(string $name = null)
    {
        $name = $name ?: $this->getRouteName();

        if (!isset($this->routes[$name])) {
            throw new Exception("Route $name is not defined");
        }

        return $this->routes[$name];
    }

    /**
     * @param string $route
     * @param array $params
     * @param bool $domain
     * @param bool $encode
     *
     * @return string
     * @throws Exception
     */
    public function makeLink(string $route, $params = [], $domain = false, bool $encode = false): string
    {
        return ($domain ? $this->domains[$domain] : '') . '/' . $this->getRoute($route)
                ->makeLink(is_string($params) ? ['action' => $params] : $params, $encode);
    }

    public function setDefaultRoute(string $route): Router
    {
        $this->default = $route;

        return $this;
    }

    /**
     * @param HttpRequest $request
     *
     * @return HttpRequest
     * @throws NotFoundHttpException
     */
    public function route(HttpRequest $request): HttpRequest
    {
        $isOk = false;

        foreach ($this->routes as $name => $route) {
            if ($params = $route->match($request->getPathInfo())) {
                $this->setRouteName($name);

                foreach ($params as $param => $value) {
                    if ($param === HttpRequest::$controllerKey) {
                        $request->setController($value);
                    } elseif ($param === HttpRequest::$actionKey) {
                        $request->setAction($value);
                    } else {
                        $request->set($param, $value);
                    }
                }

                $isOk = true;
                break;
            }
        }

        if (!$isOk) {
            if ($this->default) {
                $this->setRouteName($this->default);

                $request->setController($this->routes[$this->default]->getDefault('controller'))
                    ->setAction($this->routes[$this->default]->getDefault('action'));
            } else {
                throw new NotFoundHttpException;
            }
        }

        return $request;
    }

    public function routeCycle(HttpRequest $request, callable $callback): bool
    {
        $path = $request->getPathInfo();

        foreach ($this->routes as $name => $route) {
            if ($params = $route->match($path)) {
                $requestClone = clone $request;

                $this->setRouteName($name);

                foreach ($params as $param => $value) {
                    if ($param === HttpRequest::$controllerKey) {
                        $requestClone->setController($value);
                    } elseif ($param === HttpRequest::$actionKey) {
                        $requestClone->setAction($value);
                    } else {
                        $requestClone->set($param, $value);
                    }
                }

                if (false !== $callback($requestClone)) {
                    return true;
                }
            }
        }

        if ($this->default) {
            $this->setRouteName($this->default);

            $request->setController($this->routes[$this->default]->getDefault('controller'))
                ->setAction($this->routes[$this->default]->getDefault('action'));

            if (false !== $callback($request)) {
                return true;
            }
        }

        return false;
    }

    public function setRouteName(string $route): Router
    {
        $this->route = $route;

        return $this;
    }

    public function getRouteName(): ?string
    {
        return $this->route;
    }

    public function getRoutePatterns(): array
    {
        $routes = $this->routes;

        foreach ($routes as &$route) {
            $route = $route->getRoute();
        }

        return $routes;
    }
}
