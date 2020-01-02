<?php

namespace SNOWGIRL_CORE;

use SNOWGIRL_CORE\Exception\HTTP\NotFound;
use Closure;

class Router
{
    /** @var Route[] */
    protected $routes = [];
    protected $route;
    protected $default;
    protected $domains;

    public function __construct(App $app)
    {
        $this->domains = $app->config->domains;
    }

    public function addRoute($name, $route)
    {
        $this->routes[$name] = $route;
        return $this;
    }

    public function getRoute($name = null)
    {
        $name = $name ?: $this->getRouteName();

        if (!isset($this->routes[$name])) {
            throw new \Exception("Route $name is not defined");
        }

        return $this->routes[$name];
    }

    public function makeLink($route, $params = [], $domain = false, $encode = false)
    {
        return ($domain ? $this->domains->$domain : '') . '/' . $this->getRoute($route)
                ->makeLink(is_string($params) ? ['action' => $params] : $params, $encode);
    }

    public function setDefaultRoute($route)
    {
        $this->default = $route;
        return $this;
    }

    public function route(Request $request)
    {
        $isOk = false;

        foreach ($this->routes as $name => $route) {
            if ($params = $route->match($request->getPathInfo())) {
                $this->setRouteName($name);

                foreach ($params as $param => $value) {
                    if ($param === Request::$controllerKey) {
                        $request->setController($value);
                    } elseif ($param === Request::$actionKey) {
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
                throw new NotFound;
            }
        }

        return $request;
    }

    public function routeCycle(Request $request, Closure $callback)
    {
        $path = $request->getPathInfo();

        foreach ($this->routes as $name => $route) {
            if ($params = $route->match($path)) {
                $requestClone = clone $request;

                $this->setRouteName($name);

                foreach ($params as $param => $value) {
                    if ($param === Request::$controllerKey) {
                        $requestClone->setController($value);
                    } elseif ($param === Request::$actionKey) {
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

    public function setRouteName($route)
    {
        $this->route = $route;
        return $this;
    }

    public function getRouteName()
    {
        return $this->route;
    }

    public function getRoutePatterns()
    {
        $routes = $this->routes;

        foreach ($routes as &$route) {
            $route = $route->getRoute();
        }

        return $routes;
    }
}
