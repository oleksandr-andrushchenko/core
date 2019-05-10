<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 07.03.16
 * Time: 07:35
 */
namespace SNOWGIRL_CORE;

/**
 * Class Controller
 * @package SNOWGIRL_CORE
 */
abstract class Controller
{
    /** @var App */
    protected $app;

    public function __construct(App $app)
    {
        $this->app = $app;
        $this->initialize();
    }

    protected function initialize()
    {

    }

    public function actionDefault()
    {
        return false;
    }

    public function __call($fn, $args)
    {
        return $this->actionDefault();
    }

    public static function runCustom(App $app, $controller, $action)
    {
        /** @var Controller $controller */
        $controller = $app->getObject('Controller\\' . ucfirst($controller), $app);

        $action = 'action' . implode('', array_map(function ($i) {
                return ucfirst($i);
            }, explode('-', $action)));


        return call_user_func_array([$controller, $action], array_slice(func_get_args(), 3));
    }

    public static function run(App $app)
    {
        $controller = $app->request->getController();
        $action = $app->request->getAction();

        return static::runCustom($app, $controller, $action);
    }
}
