<?php
/**
 * Created by PhpStorm.
 * User: baihu
 * Date: 2019/12/13
 * Time: 15:25
 */
return [
    'host'            => '127.0.0.1',
    'port'            => 6379,
    'auth'            => '',
    'poolMin'         => 5,   //空闲时，保存的最大链接，默认为5
    'poolMax'         => 1000,    //地址池最大连接数，默认1000
    'clearTime'       => 60000,   //清除空闲链接的定时器，默认60s
    'clearAll'        => 300000,  //空闲多久清空所有连接,默认300s
    'setDefer'        => true, //设置是否返回结果
    //options设置
    'connect_timeout' => 1, //连接超时时间，默认为1s
    'timeout'         => 1, //超时时间，默认为1s
    'serialize'       => false, //自动序列化，默认false
    'reconnect'       => 1  //自动连接尝试次数，默认为1次
];
