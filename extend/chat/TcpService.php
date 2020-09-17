<?php
namespace chat;

class TcpService
{
    /**
     * 开启tcp连接
     * @param $serv
     * @param $fd
     */
    public static function onConnectTcp($serv, $fd)
    {
        echo "#### onConnectTcp ####" . $fd . PHP_EOL;
    }

    /**
     * 接收tcp消息
     * @param $serv
     * @param $fd
     * @param $from_id
     * @param $data
     */
    public static function onReceiveTcp($serv, $fd, $from_id, $data)
    {
        echo "#### onReceiveTcp ####" . $fd . PHP_EOL;
    }

    /**
     * 关闭tcp连接
     * @param $serv
     * @param $fd
     */
    public static function onCloseTcp($serv, $fd)
    {
        echo "#### onCloseTcp ####" . $fd . PHP_EOL;
    }

    /**
     * 关闭tcp连接
     * @param $serv
     * @param $fd
     */
    public static function onPacketTcp($serv, $data, $addr)
    {
        echo "#### onCloseTcp ####" . json_encode($addr) . PHP_EOL;
    }
}
