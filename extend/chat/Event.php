<?php
namespace Chat;

use app\common\model\Kefu;
use app\common\model\Visitor;
use app\common\model\VisitorQueue;

class Event
{
    /**
     * 心跳维持  由客服端60秒发送一次  如果需要服务端xxS没有响应而主动断开 请自行做好断开逻辑
     * @param $fd  客户端标识
     */
    public static function heartBeat($fd)
    {
        return self::reposon($fd, 200, 'ping', []);
    }

    /**
     * 访客登录
     * @param $fd
     * @param $data
     * @param $server
     * @return array|mixed
     */
    public static function visitorLogin($fd, $data, $server)
    {
        $clientInfo = $server->connection_info($fd);

        $data = [
            'visitor_id' => $data['visitor_id'],
            'visitor_name' => $data['visitor_name'],
            'visitor_avatar' => $data['visitor_avatar'],
            'visitor_ip' => isset($clientInfo['remote_ip']) ? $clientInfo['remote_ip'] : '',
            'create_time' => time(),
            'online_status' => 1,
            'merchant_id' => $data['merchant_id'],
            'client_id' => $fd,
        ];
        $info = Visitor::getInfo($data['visitor_id']);
        if ($info) {
            Visitor::where(['visitor_id' => $data['visitor_id']])->update($data);
        }
        $server->redis->set('online:' . $fd, $data['visitor_id']);

        return self::reposon($fd, 200, '上线成功', [], 'online');
    }

    /**
     * 连接客服
     * @param $fd
     * @param $data
     * @param $server
     * @return array|mixed
     */
    public static function connect_kf($fd, $data, $server)
    {
        $info = Visitor::getInfo($data['visitor_id']);
        //自动分配客服
        $onlineKfInfo = Kefu::getOnlineKefu($data['merchant_id']);
        if (!$onlineKfInfo) {
            return self::reposon($fd, 201, '全部客服不在线', [], 'connect_kf');
        }
        //得到客服正在服务的人数
        $kfServiceNums = Visitor::getKfServiceNum(array_column($onlineKfInfo, 'kf_code'));
        $kefuList = [];
        foreach ($onlineKfInfo as  $v) {
            if (!empty($kfServiceNums[$v['kf_code']])) {
                if ($kfServiceNums[$v['kf_code']] >= $v['service_num']) {
                    continue;
                }
            } else {
                $kfServiceNums[$v['kf_code']] = 0;
            }
            $kefuList[$v['kf_code']] = $v;
        }
        if (!$kefuList) {
            return self::reposon($fd, 200, '全部客服不在线', ['connect_code' => '', 'avatar' => ''], 'connect_kf');
        }
        $preKfCode = $info['pre_kf_code'];
        //优先连上次客服
        if (!empty($kefuList[$preKfCode])) {
            $serviceKefu = $kefuList[$preKfCode];
        } else {
            //寻找接待人数最少客服
            $minKefuCode = array_search(min($kfServiceNums), $kfServiceNums);
            $serviceKefu = $kefuList[$minKefuCode];
        }

        return self::reposon($fd, 200, '连接成功', ['connect_code' => $serviceKefu['kf_code'], 'avatar' => $serviceKefu['avatar']], 'connect_kf');
    }

    /**
     * 客服登录
     * @param $fd  客户端标识
     * @param $data  请求数据
     * @param $server  连接对象
     */
    public static function kefuLogin($fd, $data, $server)
    {
        //设置客服登陆信息
        $server->redis->set('online:' . $fd, $data['kf_code']);
        $server->redis->hSet('online:kefu', $data['kf_code'], $fd);
        //客服过期时间
        $server->redis->expire('online:kefu', 60*60*24);
        $server->redis->expire('online', 60*60*24);

        //查询正在接管服务 从新接管
        $list = Visitor::getQueueing($data['kf_code']);
        if ($list) {
            foreach ($list as $v) {
                $server->redis->hSet('binds:' . $data['kf_code'], $v['visitor_id'], $v['client_id']);
            }
        }
        //更新客服连接
        Visitor::updateKfClientId($data['kf_code'], $fd);
        //更新客服状态
        Kefu::updateOnlineStatus($data['kf_code'], 1);

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
            if (strstr($uid, "KF_") === 0) {
                //消息入库
                $to_fd = $server->redis->hGet('binds:' . $uid, $data['to_id']);
                //发送游客
                Task::init($server, 'chat_log')->addTaskQueue($uid, 'sendMessage', ['fd' => $fd, 'to_fd' => $to_fd, 'data' => $data]);
                return false;
            } else {
                //游客发送信息
                if (strstr($data['to_id'], "KF_") === 0) {
                    //发送给客服
                    $kfCode = $server->redis->get('bind:' . $uid);
                    $to_fd = $server->redis->hGet('online:kefu', $kfCode);

                    //判断是否存在客服绑定中
                    $isBind = $server->redis->hGet('binds:' . $kfCode, $uid);
                    if ($isBind) {
                        //发送客服
                        Task::init($server, 'chat_log')->addTaskQueue($uid, 'sendMessage', ['fd' => $fd, 'to_fd' => $to_fd, 'data' => $data]);
                        return false;
                    }
                } else {
                    //发送给商家
                    //Task::init($server, 'chat_log')->addTaskQueue($uid, 'replyQuestion', ['fd' => $fd, 'visitor_id' => $uid, 'mc_code' => $dataArr[1], 'message' => $data['message'], 'qid' => '', 'cd_code' => $data['cd_code']]);
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
     * 断开连接
     * @param $fd  客户端标识
     * @param $server  请求数据
     */
    public static function disconnect($fd, $server)
    {
        $bool = $server->redis->exists('online:' . $fd);
        if ($bool == 0) return true;
        $uid = $server->redis->get('online:' . $fd);

        if (strstr($uid, "KF_") === 0) {
            //客服退出
            Kefu::updateOnlineStatus($uid, 0);
            $server->redis->del('online:' . $fd);
            $server->redis->hDel('online:kefu', $uid);
            $server->redis->hDel('binds:' . $uid);
        } else {
            //游客退出
            Visitor::logoutVitisor($uid);
            //更新服务状态

            //通知客服游客下线
            $kf_code = $server->redis->get('bind:' . $uid);
            $to_fd = $server->redis->hGet('online:kefu', $kf_code);

            if (!empty($to_fd) and $server->isEstablished((int)$to_fd) != false) {
                $resut = self::reposon((int)$to_fd, 200, '游客下线', ['visitor_id' => $uid], 'diffClose');
                $server->push($resut['fd'], $resut['data']);
            }

            $server->redis->del('online:' . $fd);
            $server->redis->del('bind:' . $uid);
            $server->redis->hDel('binds:' . $kf_code, $uid);
        }
        return true;
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