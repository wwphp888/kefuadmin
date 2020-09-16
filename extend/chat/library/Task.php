<?php
namespace chat;

use think\Log;
use chat\Event as Event;
use chat\TaskQueue;
use think\Config;

/**
 * Task处理封装 请在服务开始分配task_worker_num大小 保证该参数 大于所以任务分配
 */

class Task
{
    public static function init($service, $qname)
    {
        $queue = new TaskQueue($service);
        return $queue->setQueue($qname);
    }

    /**
     * 创建Task所需任务数据
     * @param $method  请求方法
     * @param $params  传递数据
     */
    public static function creatTask($method, $params)
    {
        $data['method'] = $method;
        $data['data'] = $params;
        return json_encode($data);
    }

    /**
     * 正常发送消息
     * @param $server  请求方法
     * @param $data  传递数据
     */
    public static function sendMessage($server, $data)
    {
        //消息入库
        $chat_log_id = ChatLogLogic::addChatLog($data['data'], 1, 2);
        $message = [
            'from_avatar' => $data['data']['from_avatar'],
            'from_name' => $data['data']['from_name'],
            'from_id' => $data['data']['from_id'],
            'create_time' => date('Y-m-d H:i:s'),
            'message' => $data['data']['message'],
            'log_id' => $chat_log_id,
            'read_status' => 1,
            'cd_code' => $data['data']['cd_code']
        ];

        if (isset($data['fd']) and ($server->isEstablished((int)$data['fd']) != false)) {
            $resut = Event::reposon($data['fd'], 200, '发送成功', $message, 'message');
            $server->push($resut['fd'], $resut['data']);
        }
        if ( isset($data['to_fd']) and ($server->isEstablished((int)$data['to_fd']) != false)) {
            $resut = Event::reposon($data['to_fd'], 200, '来新信息了', $message, 'chatMessage');
            $server->push($resut['fd'], $resut['data']);

        } else {
            //更新聊天日志状态
            ChatLogLogic::updateSendStatus($chat_log_id, 2);
        }
        return true;
    }

    /**
     * 模拟商户发送消息
     * @param $method  请求方法
     * @param $data  传递数据
     * @param $data  请求数据
     */
    public static function simulationMerMessage($server, $data)
    {
        //获取商户信息
        $mer_info = MerchantLogic::getMerchantConfigInfoByCode($data['mc_code']);
        //获取商户配置信息
        $mer_config_info = ConfigLogic::findChatConfigByCode($data['mc_code']);

        $chat_log_id = ChatLogLogic::addChatLog([
            'from_id' => $data['mc_code'],
            'from_name' => $mer_info['mc_name'],
            'from_avatar' => $mer_config_info['avatar'],
            'to_id' => $data['visitor_id'],
            'to_name' => $data['visitor_name'],
            'to_avatar' => $data['visitor_avatar'],
            'message' => $data['message'],
            'mc_code' => $data['mc_code'],
            'cd_code' => $data['cd_code']
        ]);
        $message = [
            'from_avatar' => $mer_config_info['avatar'],
            'from_name' => $mer_info['mc_name'],
            'from_id' => $data['mc_code'],
            'create_time' => date('Y-m-d H:i:s'),
            'message' => $data['message'],
            'log_id' => $chat_log_id,
            'read_status' => 2,
        ];
        if (isset($data['fd']) and ($server->isEstablished((int)$data['fd']) != false)) {
            $resut = Event::reposon($data['fd'], 200, '来新信息了', $message, 'chatMessage');
            $server->push($resut['fd'], $resut['data']);
        } else {
            //更新聊天日志状态
            ChatLogLogic::updateSendStatus($chat_log_id, 2);
        }
        return true;
    }

    /**
     * 模拟客服发送消息
     * @param $method  请求方法
     * @param $data  传递数据
     * @param $data  请求数据
     */
    public static function simulationKefuMessage($server, $data)
    {

        $chat_log_id = ChatLogLogic::addChatLog([
            'from_id' => $data['kf_code'],
            'from_name' => $data['kf_name'],
            'from_avatar' => $data['kf_avatar'],
            'to_id' => $data['visitor_id'],
            'to_name' => $data['visitor_name'],
            'to_avatar' => $data['visitor_avatar'],
            'message' => $data['message'],
            'mc_code' => $data['mc_code'],
            'cd_code' => $data['cd_code']
        ], $data['read_status'], $data['type']);
        $message = [
            'from_avatar' => $data['kf_avatar'],
            'from_name' => $data['kf_name'],
            'from_id' => $data['kf_code'],
            'create_time' => date('Y-m-d H:i:s'),
            'message' => $data['message'],
            'log_id' => $chat_log_id,
            'read_status' => $data['read_status']
        ];
        //给客服本身发送消息
        if (isset($data['to_fd']) and ($server->isEstablished((int)$data['to_fd']) != false)) {
            $message['to_id'] = $data['visitor_id'];
            $resut = Event::reposon($data['to_fd'], 200, '发送成功', $message, 'message');
            $server->push($resut['fd'], $resut['data']);
        }
        //给用户发送消息
        if (isset($data['fd']) and ($server->isEstablished((int)$data['fd']) != false)) {
            $resut = Event::reposon($data['fd'], 200, '来新信息了', $message, 'chatMessage');
            $server->push($resut['fd'], $resut['data']);
        } else {
            //更新聊天日志状态
            ChatLogLogic::updateSendStatus($chat_log_id, 2);
        }

        return true;
    }
}
