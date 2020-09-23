<?php

namespace chat;

use think\swoole\Server;
use chat\Event;
use think\Log;
use chat\library\redis\CoRedis;
use chat\library\redis\RedisPool;
use think\Config;

class Service
{
    public function onWorkerStart($server)
    {
        if ($server->taskworker == false) {
            $config = Config::get('redis');
            $redisPool = new RedisPool($config);
            $server->redis = CoRedis::init($redisPool);
            unset($config);
            //定时器，清除空闲连接
            $redisPool->clearTimer($server);
        }
    }

    public function onWorkerStop($server, $worker_id)
    {
        unset($server->redis);
    }

    public function onWorkerExit($server, $worker_id)
    {
        \Swoole\Timer::clearAll();
    }

    public function onOpen($server, $request)
    {
        echo "server: handshake success with fd{$request->fd}\n";
    }

    public function onMessage($server, $frame)
    {
        try {
            Log::info("WebSocket请求开始，请求信息:receive from {$frame->fd}:{$frame->data},opcode:{$frame->opcode},fin:{$frame->finish}");

            $data = json_decode($frame->data, true);
            $method = $data['method'];
            $message = $data['param'];
            if (!empty($data['token'])) {
                $message['token'] = $data['token'];
            }

            $result = call_user_func_array([Event::class, $method], [$frame->fd, $message, $server]);
            if ($result != false and $server->isEstablished((int)$result['fd']) != false) {
                $server->push($result['fd'], $result['data']);
            }
        } catch (\Exception $e) {
            Log::error('WebSocket请求异常,异常信息：' . $method . ":" . $e->getMessage() . '错误地址：' . $e->getFile() . $e->getLine() . $e);
        }
    }

    //后台推送消息用http方式
    public function onRequest($request, $response, $server)
    {
        $params = $request->rawContent();
        $params = json_decode($params, true);
        $method = $params['method'];
        $data = $params['param'];
        $resut = call_user_func_array([Event::class, $method], [$data, $server]);
        if ($resut != false and $server->isEstablished((int)$resut['fd']) != false) {
            $server->push($resut['fd'], $resut['data']);
        }
    }

    public function onClose($server, $fd)
    {
        try {
            Log::record('WebSocket关闭请求开始，请求信息[' . json_encode($server) . ']');
            Event::disconnect($fd, $server);
            echo "client {$fd} closed\n";
        } catch (BaseException $e) {
            Log::error('WebSocket关闭请求异常,异常信息：' . $e->getMessage() . '错误地址：' . $e->getFile() . $e->getLine());
        }
    }

    //推送消息的异步队列
    public function onTask($server, $task_id, $from_id, $data)
    {
        echo "#### onTask ####" . PHP_EOL;
        echo "#{$server->worker_id} onTask: [PID(进程id)={$server->worker_pid}]: task_id={$task_id} ::::" . $data, '::::' . PHP_EOL;
        $info = json_decode($data, true);
        $class = "\\chat\\library\\Task";
        call_user_func_array([new $class, $info['method']], [$server, $info['data']]);
        $server->finish($data);
    }

    public function onFinish($serv, $task_id, $data)
    {
        echo "Task {$task_id} 已完成" . PHP_EOL;
    }
}
