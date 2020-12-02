<?php

namespace kernel\library;

/**
 * Class Container
 * @package kernel\library
 */
class Container
{
    /**
     * @var Container 容器对象实例
     */
    protected static $instance;

    /**
     * @var array 容器中存放的对象实例
     */
    protected $instanceObjects = [];

    /**
     * @var array 容器绑定标识
     */
    protected $bind = [
        'app'       => Application::class,
        'config'    => Config::class,
        'route'     => Route::class
    ];

    /**
     * @var array 容器标识别名
     */
    protected $name = [];

    /**
     * Container constructor.
     */
    private function __construct(){}

    /**
     * 获取当前容器实例
     * @return static
     */
    public static function getInstance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static;
        }
        return static::$instance;
    }

    /**
     * 设置当前容器的实例
     * @param $instance
     */
    public static function setInstance($instance)
    {
        static::$instance = $instance;
    }

    /**
     * 获取容器中的实例对象
     * @param string $classTag 类名或标识
     * @param array $vars 变量
     * @param false $isNewInstance 是否每次创建新的实例
     * @return mixed|object
     */
    public static function get($classTag, $vars = [], $isNewInstance = false)
    {
        return static::getInstance()->make($classTag, $vars, $isNewInstance);
    }

    public static function set()
    {}

    /**
     * 创建类的实例
     * @param string $classTag 类名或标识
     * @param array $vars 变量
     * @param false $isNewInstance 是否每次创建新的实例
     * @return mixed|object
     */
    public function make($classTag, $vars = [], $isNewInstance = false)
    {
        if (true === $vars) {
            $vars = [];
            $isNewInstance = true;
        }

        $classTag = $this->name[$classTag] ?? $classTag;
        if (isset($this->instanceObjects[$classTag]) && !$isNewInstance) {
            return $this->instanceObjects[$classTag];
        }

        if (isset($this->bind[$classTag])) {
            $concrete = $this->bind[$classTag];

            if ($concrete instanceof \Closure) {
                $object = $this->invokeFunction($classTag, $vars);
            } else {
                $this->name[$classTag] = $concrete;
                return $this->make($concrete, $vars, $isNewInstance);
            }
        } else {
            $object = $this->invokeClass($classTag, $vars);
        }

        if (!$isNewInstance) {
            $this->instanceObjects[$classTag] = $object;
        }

        return $object;
    }

    /**
     * @param $function
     * @param array $vars
     * @return mixed
     */
    public function invokeFunction($function, $vars = [])
    {
        try {
            $reflect = new \ReflectionFunction($function);
            $args = $this->bindParams($reflect, $vars);

            return call_user_func_array($function, $args);
        } catch (\ReflectionException $e) {
            throw new \RuntimeException('function not exists: ' . $function . '()');
        }
    }

    /**
     * @param $reflect
     * @param $vars
     * @return array
     */
    protected function bindParams($reflect, $vars)
    {
        if (0 == $reflect->getNumberOfParameters()) {
            return [];
        }
    }

    /**
     * @param $class
     * @param array $vars
     * @return mixed|object
     */
    public function invokeClass($class, $vars = [])
    {
        try {
            $reflect = new \ReflectionClass($class);

            if ($reflect->hasMethod('__make')) {
                $method = new \ReflectionMethod($class, '__make');
                if ($method->isPublic() && $method->isStatic()) {
                    $args = $this->bindParams($method, $vars);
                    return $method->invokeArgs(null, $args);
                }
            }

            $constructor = $reflect->getConstructor();
            $args = $constructor ? $this->bindParams($constructor, $vars) : [];

            return $reflect->newInstanceArgs($args);
        } catch (\ReflectionException $e) {
            throw new \RuntimeException('class not exists: ' . $class);
        }
    }

}
