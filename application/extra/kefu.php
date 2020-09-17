<?php
/**
 * 网站基础配置文件
 * User: zhc
 * Date: 2019/11/13
 * Time: 14:25
 */
return [
    //*************************************网站设置****************************************
    'web_name'                 => '在线客服系统',              #网站名
    'web_domain'               => 'http://chat.tiyuba.top',            #网站地址
    'web_title'                => '在线客服系统',             #网站标题
    'acquiesce_password'       => '123456',                  #添加后台用户,商户,客服默认密码
    'valid_day'                =>  31,             #添加商户有效期
    //*************************************chat service 设置****************************************
    'socket_port'             =>   9510,                    #websocket端口
    'http_port'               =>   9191,                    # http接口
    'socket_domain'           =>  'http://chat.tiyuba.top',      # 服务连接地址  可以不和本站地址相同  自己配置转发
    'open_ssl'                =>  [
        'open_status'        =>'off',                     #是否开启  如果本站地址配置ssl  开启ssl  off 关闭   on 开启
        'ssl'                =>[
            'local_cert'  => '/mnt/www/timely_service_seller_2020/public/2020cert/214474304490958.pem',     #your/path/of/server.pem
            'local_pk'    => '/mnt/www/timely_service_seller_2020/public/2020cert/214474304490958.key',       #/your/path/of/server.key'
            'verify_peer' => false,
        ]
    ],
];
