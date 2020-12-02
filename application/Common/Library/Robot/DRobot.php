<?php

namespace application\Common\Library\Robot;

/**
 * 钉钉机器人
 * Class DRobot
 * @package application\Common\Library\Robot
 */
class DRobot
{
    /**
     * @var bool 发送结果
     */
    private $_sendResult = true;

    /**
     * @var string 错误信息
     */
    private $_error = '';

    /**
     * @var string url 拼接地址
     */
    private $_url = 'https://oapi.dingtalk.com/robot/send?access_token=%s&timestamp=%d&sign=%s';

    /**
     * @var int 发送到多少个群
     */
    private $_groupNumber;

    /**
     * @var array 群聊 access_token 和密钥信息
     */
    private $_groupInformation = [
        [
            'access_token' => '',
            'secret'    => ''
        ],
    ];

    /**
     * DRobot constructor.
     *
     * @param int $howMany 群发多少个
     */
    public function __construct($howMany = 1)
    {
        $this->_groupNumber = $howMany;
    }

    /**
     * 监控脚本运行时长，超出则报警（$start 和 $end 由 microtime(true) 获取）
     *
     * @param float $start 开始时间
     * @param float $end 结束时间
     * @param string $scriptName 脚本名字
     * @param int $timeLong 预估正常的运行时间(默认 2 小时)
     * @param int $errorRange 允许超出警戒时长的范围
     * @param int $scale 时间的小数点保留位数
     */
    public function monitoringRunTime($start, $end, $scriptName, $timeLong = 7200, $errorRange = 0, $scale = 3)
    {
        $duration = bcsub($end, $start, $scale);
        $overstep = bcsub($duration, $timeLong, $scale);
        if (bcsub($overstep, $errorRange, $scale) > 0) {
            $this->say($scriptName . ' 脚本运行时长 ' . $duration . ' s，预估正常运行时间范围在 ' . bcadd($timeLong, 0, $scale) . ' s 内，超时 ' . $overstep . ' s，请留意相关情况！');
        }
    }

    /**
     * 发送消息到钉钉群
     *
     * @param string $msg
     * @param string $keyword
     * @param array $mobiles
     * @param bool $isAtAll
     *
     * @return bool
     */
    public function say($msg = '', $keyword = 'error: ',$mobiles = [], $isAtAll = false)
    {
        $message = $keyword . $msg . PHP_EOL ;
        if ($this->isCli()) {
        	$message .= '【来自】 ' . GAMEDOMAIN . ' 的命令行脚本';
        } else {
        	$message .= '【来自】 https://' . DOMAIN . $_SERVER['REQUEST_URI'];
        }

        $this->_sendMessage([
            'msgtype'   => 'text',          // 消息类型
            'text'      => [
                'content' => $message       // 消息内容
            ],
            'at'        => [
                'atMobiles' => $mobiles,    // 被 @ 人的手机号
                'isAtAll'   => $isAtAll     // 是否 @ 所有人
            ]
        ]);

        return $this->_sendResult;
    }

    function isCli(){
    	return preg_match("/cli/i", php_sapi_name()) ? true : false;
    }

    /**
     * 发送信息
     *
     * @param $data
     */
    protected function _sendMessage($data)
    {
        // 将要发送信息的 url 列表不为空
        $urlList = $this->_makeUrlList();
        if (!empty($urlList)) {
            foreach ($urlList as $url) {
                // 发送数据
                $result = $this->_requestPostCurl($url, $data);

                // 接口请求失败
                if (false === $result) {
                    $this->_sendResult = false;
                    $this->_error = "【钉钉群聊机器人 dingTalk 接口请求失败，地址：" . $url . "】";
                    break;
                }

                // 验证不通过
                $msg = json_decode($result, true);
                if (0 != $msg['errcode'] || 'ok' != $msg['errmsg']) {
                    $this->_sendResult = false;
                    $this->_error = "【钉钉群聊机器人安全设置校验未通过，地址：" . $url . "】" . $msg['errmsg'];
                    break;
                }
            }
        } else {
            $this->_sendResult = false;
            $this->_error = "【未找到钉钉群聊机器人接入地址】" . $this->_error;
        }
    }

    /**
     * 生成将要发送的群聊 url 列表
     *
     * @return array
     */
    protected function _makeUrlList()
    {
        $urlList = [];
        try {
            $i = 1;
            foreach ($this->_groupInformation as $item) {
                // 如果大于限定值
                if ($i > $this->_groupNumber) {break;}
                $urlList[] = $this->_makeUrl($item['access_token'], $item['secret']);
                $i++;
            }
        } catch (\Exception $e) {
            $this->_sendResult = false;
            $this->_error = $e->getMessage();
        }
        return $urlList;
    }

    /**
     * curl 发送 post 请求
     *
     * @param string $url url 地址
     * @param array $data 请求数据
     *
     * @return bool|string
     */
    protected function _requestPostCurl($url, $data) {
        // 转 json 字符串
        $data_string = json_encode($data);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array ('Content-Type: application/json;charset=utf-8'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // 线下环境不用开启 curl 证书验证, 未调通情况可尝试添加该代码
        curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    /**
     * 生成请求的 url 地址
     *
     * @param string $accessToken
     * @param string $secret
     *
     * @return string
     */
    private function _makeUrl($accessToken, $secret)
    {
        $time = $this->getMillisecond();
        $sign = $this->_makeSign($time, $secret);

        return sprintf($this->_url, $accessToken, $time, $sign);
    }

    /**
     * 生成签名
     *
     * @param string $time      当前毫秒时间戳
     * @param string $secret    密钥
     *
     * @return string
     */
    private function _makeSign($time, $secret)
    {
        $stringToSign = $time . "\n" . $secret;

        return utf8_encode(urlencode(base64_encode(hash_hmac('sha256', $stringToSign, $secret, true))));
    }

    /**
     * 获取当前毫秒时间戳
     *
     * @return string
     */
    public function getMillisecond()
    {
        list($millisecond, $second) = explode(' ', microtime());

        return sprintf('%.0f', (floatval($millisecond) + floatval($second)) * 1000);
    }

    /**
     * 获取错误信息
     *
     * @return string
     */
    public function getError()
    {
        return $this->_error;
    }

}
