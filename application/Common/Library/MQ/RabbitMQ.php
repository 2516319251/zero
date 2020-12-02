<?php

namespace application\Common\Library\MQ;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Class RabbitMQ
 * @package application\Common\Library\MQ
 */
class RabbitMQ
{
    /**
     * @var AMQPStreamConnection 连接队列实例
     */
    protected $_connection;

    /**
     * @var \PhpAmqpLib\Channel\AMQPChannel 队列通道
     */
    protected $_channel;

    /**
     * @var callback 自定义回调处理函数
     */
    protected $_callback;

    /**
     * 构造函数
     * RabbitMQ constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        try {
            $this->_connection = new AMQPStreamConnection(
                '127.0.0.1',
                5672,
                'guest',
                'guest',
                '/',
                false,
                'AMQPLAIN',
                null,
                'en_US',
                3.0,
                300,
                null,
                true,
                150
            );
            $this->_channel = $this->_connection->channel();
        } catch (\Exception $e) {
            throw new \Exception('Connection failed: ' . $e->getMessage());
        }
    }

    /**
     * 推送消息（生产者）
     * @param string|array $body 推送内容
     * @param string $queue 队列名
     * @param null|string $exchange 交换机名
     * @param null|string $routeKey 路由键
     * @param array $header 请求头
     */
    public function publish($body, $queue, $exchange = null, $routeKey = null, $header = [])
    {
        $exchange = $exchange ?? 'exchange_' . $queue;
        $routeKey = $routeKey ?? 'route_' . $queue;

        // 声明初始化交换机
        $this->_channel->exchange_declare($exchange, 'direct', false, true, false);
        // 声明初始化队列
        $this->_channel->queue_declare($queue, false, true, false, false);
        // 将队列与某个交换机进行绑定，并使用路由关键字
        $this->_channel->queue_bind($queue, $exchange, $routeKey);

        $properties = array_merge([
            'content_type' => 'text/plain',
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT
        ], $header);
        $msg = new AMQPMessage(is_array($body) ? json_encode($body) : $body, $properties);
        $this->_channel->basic_publish($msg, $exchange, $routeKey);
    }

    /**
     * 接受消息（消费者）
     * @param string $queue 队列名
     * @param callable $callback 自定义回调处理方法
     * @param null|string $exchange 交换机名
     * @param null|string $routeKey 路由键
     * @param string $tag 消费标签
     * @throws \ErrorException
     */
    public function reception($queue, $callback, $exchange = null, $routeKey = null, $tag = '')
    {
//         if (!isset($callback) || empty($callback)) {return ;}
        if (null !== $callback) {
            $this->_isCallable($callback);
        }

        $exchange = $exchange ?? 'exchange_' . $queue;
        $routeKey = $routeKey ?? 'route_' . $queue;
        $this->_callback = $callback;

        $this->_channel->basic_qos(null, 10, null);
        $this->_channel->queue_bind($queue, $exchange, $routeKey);
        $this->_channel->basic_consume($queue, $tag, false, false, false, false, [$this, 'acknowledge']);

        while (count($this->_channel->callbacks)) {
            $this->_channel->wait();
        }
    }

    /**
     * 消息回复确认
     * @param $msg
     */
    public function acknowledge($msg)
    {
        if (
            empty($body = $msg->body) ||
            empty($data = json_decode($body, true) ?? $body)
        ) {
            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
            return;
        }
        // 如果自定义回调函数返回 false，则不进行消息回复确认
        if (false === call_user_func($this->_callback, $data)) {return;}
        // $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);

        $nack = 'basic_ack';
        $msg->delivery_info['channel']->$nack($msg->delivery_info['delivery_tag']);
    }

    /**
     * 是否是回调函数
     * @param mixed $argument
     * @throws \InvalidArgumentException
     */
    private function _isCallable($argument)
    {
        if (!is_callable($argument)) {
            throw new \InvalidArgumentException(sprintf(
                'Given argument "%s" should be callable. %s type was given.',
                $argument,
                gettype($argument)
            ));
        }
    }

    /**
     * 析构函数
     * @throws \Exception
     */
    public function __destruct()
    {
        $this->_channel->close();
        $this->_connection->close();
    }

}
