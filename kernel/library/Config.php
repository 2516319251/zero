<?php

namespace kernel\library;

/**
 * Class Config
 * @package kernel\library
 */
class Config
{
    /**
     * @var array 配置信息
     */
    private static $conf = [];

    /**
     * 获取对应配置信息
     * @param $key
     * @return mixed|null
     */
    public static function get($key)
    {
        // 如果已经加载过
        $arr = explode('.', $key);
        if (isset(self::$conf[$arr[0]])) {
            return self::loopConfValue(self::$conf[$arr[0]], $arr);
        }

        // 查找配置文件获取配置信息
        $file = str_replace('\\', '/', Loader::getRootPath() . 'config/' . $arr[0] . '.php');
        if (is_file($file)) {
            $conf = require_once $file;
            self::$conf[$arr[0]] = $conf;
            return self::loopConfValue($conf, $arr);
        }
        return null;
    }

    /**
     * 循环获取配置信息
     * @param $conf
     * @param $array
     * @return mixed|null
     */
    private static function loopConfValue($conf, $array)
    {
        $tmp_conf = $conf;
        foreach ($array as $key => $value) {
            if (0 == $key) {continue;}
            if (null === ($tmp_conf = $tmp_conf[$value] ?? null)) {
                return null;
            }
        }
        return $tmp_conf;
    }

}
