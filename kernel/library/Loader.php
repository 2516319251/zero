<?php

namespace kernel\library;

/**
 * Class Loader
 * @package kernel\library
 */
class Loader
{
    /**
     * @var array 类名映射
     */
    protected static $class_map = [];

    /**
     * 注册自动加载机制
     * @param string $autoload
     */
    public static function register($autoload = '')
    {
        // 注册自动加载
        spl_autoload_register($autoload ?: 'kernel\\library\\Loader::autoload', true, true);

        // 加载通过 composer 安装的插件
        $composer_autoload_file = str_replace('\\', '/', self::getRootPath() . 'vendor/autoload.php');
        require_once $composer_autoload_file;
    }

    /**
     * 自动加载
     * @param $class
     * @return bool
     */
    public static function autoload($class)
    {
        if (isset(self::$class_map[$class])) {
            return true;
        }

        $file = str_replace('\\', '/', self::getRootPath() . $class . '.php');
        if (is_file($file)) {
            require_once $file;
            self::$class_map[$class] = $file;
            return true;
        }

        return false;
    }

    /**
     * 获取应用根目录
     * @return string
     */
    public static function getRootPath()
    {
        if ('cli' == PHP_SAPI) {
            $script_name = realpath($_SERVER['argv'][0]);
        } else {
            $script_name = $_SERVER['SCRIPT_FILENAME'];
        }

        $path = realpath(dirname($script_name));
        if (!is_file($path . DIRECTORY_SEPARATOR . 'kernel')) {
            $path = dirname($path);
        }

        return $path . DIRECTORY_SEPARATOR;
    }

}
