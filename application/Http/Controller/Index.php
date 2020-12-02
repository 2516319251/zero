<?php

namespace application\Http\Controller;

use application\Common\Library\MQ\RabbitMQ;
use kernel\library\Config;

/**
 * Class Index
 * @package application\Http\Controller
 */
class Index
{
    public function index()
    {
        echo 13524;
    }

    public function sen()
    {
        $mq = new RabbitMQ();
        $mq->publish('this is a test', 'test');
    }

    public function rec()
    {
        $mq = new RabbitMQ();
        $mq->reception('test', [$this, 'back']);
    }

    public function back($body)
    {
        var_dump($body);
        return false;
    }

}
