<?php

namespace kernel\library;

/**
 * Class Error
 * @package kernel\library
 */
class Error
{
    // 注册异常处理
    public static function register()
    {
        error_reporting(E_ALL);
        set_error_handler([__CLASS__, 'errorHandler']);
        set_exception_handler([__CLASS__, 'exceptionHandler']);
        // register_shutdown_function([__CLASS__, 'shutdown']);
    }

    public static function exceptionHandler($e)
    {
        echo "Exception: {$e->getMessage()} in {$e->getFile()} line {$e->getLine()}" . PHP_EOL;
    }

    public static function errorHandler($err_level, $err_str, $err_file = '', $err_line = 0)
    {
        echo "Error (level {$err_level}): {$err_str} in {$err_file} line {$err_line}" . PHP_EOL;
    }

//    public static function shutdown()
//    {
//        if ($error = error_get_last()) {
//            var_dump($error);
//        }
//    }

}
