<?php
/**
 * Created by PhpStorm.
 * Author: baihu    task 任务分配进程数
 * Date: 2019/12/21
 * Time: 10:50
 */
return  [
    //分配任务名称,每个任务加起来总数不得超过task_work_num-1  [0,10]代表0-10随机匹配  如服务器配置较低，请手动更改此配置
    'task_work_num' => 60,
    'task_queue' => [
        'chat_log' => [0,9],
    ]
];
