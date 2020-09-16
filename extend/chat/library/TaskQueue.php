<?php
/**
 * task操作类
 * Author: baihu
 * Date: 2019/12/21
 * Time: 10:57
 */

namespace chat\library;

use think\Config;

class TaskQueue
{
    public $service;
    public $config;
    public $qname_num;

    public function __construct($service, $config = [])
    {
        $this->service = $service;
        if (!$config) {
            $config = Config::pull('task');

            if (empty($config['task_queue'])) {
                throw new \RuntimeException(date('Y-m-d H:i:s', time()) . "：未配置task_queue");
            }

            $this->config = $config['task_queue'];
        } else {
            $this->config = $config;
        }
    }

    public function setQueue($qname)
    {
        if (!isset($this->config[$qname])) {
            throw new \RuntimeException(date('Y-m-d H:i:s', time()) . "：task_queue_name未配置");
        }
        $this->qname_num = $this->config[$qname];
        return $this;
    }

    public function addTaskQueue($uid, $method, $data)
    {
        //获取队列数量
        if (is_array($this->qname_num)) {
            //获取队列数量
            $num = $this->qname_num[1] - $this->qname_num[0] + 1;
            $end = $this->toNum($uid);
            $task_id = ($end % $num) + $this->qname_num[0];
        } else {
            $task_id = $this->qname_num;
        }
        $this->service->task($this->creatTask($method, $data), $task_id);
        return true;

    }

    /**
     * 创建Task所需任务数据
     * @param $method
     * @param $params
     */
    public function creatTask($method, $params)
    {
        $data['method'] = $method;
        $data['data'] = $params;
        return json_encode($data);
    }

    public function toNum($uid)
    {
        //截取字符串最后一位  //保证相同用户在同一个task执行
        $end = substr($uid, 0, strlen($uid) - 1);
        //转成ASII码
        return ord($end);
    }

    public function __destruct()
    {
        $this->service = null;
        $this->config = null;
        $this->qname_num = null;
    }
}
