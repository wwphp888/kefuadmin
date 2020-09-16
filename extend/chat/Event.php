<?php
namespace Chat;

class Event
{
    /**
     * 心跳维持  由客服端60秒发送一次  如果需要服务端xxS没有响应而主动断开 请自行做好断开逻辑
     * @param $fd  客户端标识
     * @param $data  请求数据
     * @param $server  连接对象
     */
    public static function heartBeat($fd, $data, $server)
    {
        return self::reposon($fd, 200, 'pong', []);
    }

    /**
     * 客服登录
     * @param $fd  客户端标识
     * @param $data  请求数据
     * @param $server  连接对象
     */
    public static function kefuLogin($fd, $data, $server)
    {
        //更新客服状态

        //设置客服登陆信息
        $server->redis->set('online:' . $fd, $data['uid']);
        $server->redis->hSet('online:kefu', $data['uid'], $fd);
        //客服过期时间
        $server->redis->expire('online:kefu', 60*60*24);
        $server->redis->expire('online', 60*60*24);

        //查询正在接管服务 从新接管
        $list = QueueLogic::getQueueing($data['uid']);
        if ($list) {
            foreach ($list as $item) {
                $server->redis->hSet('binds:' . $data['uid'], $item['visitor_id'], $item['client_id']);
            }
        }
        //QueueLogic::updateQueueingkefuClientid($kefu_code, $fd);

        //$kefuInfo = KefuLogic::getKefuInfo($kefu_code);
        //排队的游客自动连接客服
//        $cd_codes = KefuLogic::getQudaoByKefuCode($kefu_code);
//        $waitInfo = Visitor::getVisitorWaitInfo($kefuInfo['mc_code'], $cd_codes, $kefuInfo['mean_service_num']);
//        if ($waitInfo) {
//            foreach ($waitInfo as $v) {
//                $resut = self::reposon((int)$v['client_id'], 200, '连接客服', [], 'connectKefu');
//                $server->push($resut['fd'], $resut['data']);
//            }
//        }
        return self::reposon($fd, 200, '客服上线成功', [], 'kefuOnline');
    }

    /**
     * 聊天
     * @param $fd  客户端标识
     * @param $data  请求数据
     */
    public static function message($fd, $data, $server)
    {
        $bool = $server->redis->exists('online:' . $fd);
        if ($bool == 0) {
            return false;
        }
        $uid = $server->redis->get('online:' . $fd);
        $data['message'] = trim($data['message']);
        if ($data['message'] == '') {
            return false;
        }
        try {
            //客服发信息给游客
            if (strstr($uid, "kf") === 0) {
                //消息入库
                $to_fd = $server->redis->hGet('binds:' . $uid, $data['to_id']);
                //发送游客
                Task::init($server, 'chat_log')->addTaskQueue($uid, 'sendMessage', ['fd' => $fd, 'to_fd' => $to_fd, 'data' => $data, 'type' => 'kefuSend']);
                return false;
            } else {
                //游客发送信息
                $dataArr = explode(":", $uid);
                if (strstr($data['to_id'], "kf") === 0) {
                    //发送给客服
                    $kfCode = $server->redis->get('bind:' . $dataArr[0]);
                    $to_fd = $server->redis->hGet('online:kefu', $kfCode);

                    //判断是否存在客服绑定中
                    $isBind = $server->redis->hGet('binds:' . $kfCode, $dataArr[0]);
                    if ($isBind) {
                        //发送客服
                        Task::init($server, 'chat_log')->addTaskQueue($dataArr, 'sendMessage', ['fd' => $fd, 'to_fd' => $to_fd, 'data' => $data, 'type' => 'visitorSend']);
                        return false;
                    }
                } else {
                    //发送给商家
                    Task::init($server, 'chat_log')->addTaskQueue($dataArr[0], 'replyQuestion', ['fd' => $fd, 'visitor_id' => $dataArr[0], 'mc_code' => $dataArr[1], 'message' => $data['message'], 'qid' => '', 'cd_code' => $data['cd_code']]);
                }
            }
        } catch (BaseException $e) {
            return self::reposon($fd, 400, '消息发送失败', $data['message'], 'message');
        }
        return false;
    }

    /**
     * 同一客服只能在同一地方登陆
     * @param $data
     * @param $server
     * @return false|mixed
     */
    public static function focusLogout($data, $server) {
        $to_fd = $server->redis->hGet('online:kefu', $data['kf_code']);
        if ($to_fd) {
            return self::reposon((int)$to_fd, 200, '您的账号已在其他地方登录', $data, 'focusLogout');
        }
        return false;
    }

    /**
     * @param $fd
     * @param int $code
     * @param string $msg
     * @param string $data
     * @param string $cmd
     * @return mixed
     */
    public static function reposon($fd, $code = 200, $msg = "操作成功", $data = '', $method = '')
    {
        return [
            'fd' => $fd,
            'data' => json_encode([
                'code' => $code,
                'msg' => $msg,
                'data' => $data,
                'method' => $method,
            ], JSON_UNESCAPED_UNICODE),
        ];
    }
}