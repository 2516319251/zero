<?php

namespace kernel;

// 载入 Loader 类
require_once __DIR__ . '/library/Loader.php';

// 注册自动加载机制
library\Loader::register();

// 注册错误和异常处理机制
library\Error::register();

