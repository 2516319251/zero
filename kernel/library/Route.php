<?php

namespace kernel\library;

/**
 * Class Route
 * @package kernel\library
 */
class Route
{
    /**
     * @var string 控制器
     */
    public $controller;

    /**
     * @var string 方法
     */
    public $action;

    /**
     * 构造函数
     * Route constructor.
     */
    public function __construct()
    {
        // 如果 REQUEST_URI 存在
        if (isset($_SERVER['REQUEST_URI']) && $_SERVER['REQUEST_URI'] != '/') {
            // 解析 REQUEST_URI 字符串
            $uri_arr = explode('/', trim($_SERVER['REQUEST_URI'], '/'));
            $this->controller = $uri_arr[0] ?? Config::get('route.controller');
            $this->action = $uri_arr[1] ?? Config::get('route.action');
            // REQUEST_URI 多余部分转换成 GET 参数
            $i = 2;
            $count = count($uri_arr);
            while ($count > $i) {
                if (isset($uri_arr[$i + 1])) {
                    $_GET[$uri_arr[$i]] = $uri_arr[$i + 1];
                }
                $i += 2;
            }
            unset($_GET['s']);
        } else {
            $this->controller = Config::get('route.controller');
            $this->action = Config::get('route.action');
        }
    }
}
