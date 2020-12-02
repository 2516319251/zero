<?php

namespace kernel\library;

/**
 * Class Application
 * @package kernel\library
 */
class Application
{
    /**
     * 启动
     * @throws \Exception
     */
    public static function run()
    {
        $route = new Route();
        $ctrl_file = str_replace('\\', '/', Loader::getRootPath() . 'application/Http/Controller/' . $route->controller . '.php');
        if (is_file($ctrl_file)) {
            $ctrl_namespace = '\\application\\Http\\Controller\\' . $route->controller;
            $ctrl_class = new $ctrl_namespace();
            $action = $route->action;
            if (in_array($action, get_class_methods(get_class($ctrl_class)))) {
                $ctrl_class->$action();
            } else {
                throw new \RuntimeException('action not found: ' . $action);
            }
        } else {
            throw new \RuntimeException('controller not found: ' . $route->controller);
        }
    }
}
