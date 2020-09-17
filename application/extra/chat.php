<?php

return [
    // 扩展自身配置
    'host' => '0.0.0.0',
    'port' => 9510,
    'type' => 'socket',  //服务类型 支持 socket http server
    'mode' => '',   //运行模式 默认为SWOOLE_PROCESS
    'sock_type' => '',  //sock type 默认为SWOOLE_SOCK_TCP
    'swoole_class' => '', // 自定义服务类名称
    'app_path' => APP_PATH,

    // 可以支持swoole的所有配置参数
    'daemonize' => false,
    'pid_file' => RUNTIME_PATH . 'swoole_server.pid',
    'log_file' => RUNTIME_PATH . 'swoole_server.log',
    'worker_num' => 2,  //主进程  一般为服务器进程的核数 1-4倍
    'reload_async' => true
];
