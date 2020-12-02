<?php

// [ 应用入口文件 ]
namespace kernel;

// 加载启动文件
require_once __DIR__ . '/../kernel/start.php';

// 启动框架
library\Container::get('app')->run();
