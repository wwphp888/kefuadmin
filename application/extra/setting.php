<?php

return array (
  'site' => 
  array (
    'name' => 'Fastadmin',
    'beian' => '',
    'cdnurl' => '',
    'version' => '1.1.1',
    'timezone' => 'Asia/Shanghai',
    'forbiddenip' => '',
    'languages' => 
    array (
      'backend' => 'zh-cn',
      'frontend' => 'zh-cn',
    ),
    'fixedpage' => 'dashboard',
  ),
  'recharge' => 
  array (
    'tip' => '余额可用于购买商品或用于商城消费',
    'money_list' => '10,20,30,50,100',
    'default_money' => 10,
    'min_money' => 0.1,
  ),
  'email' => 
  array (
    'email' => 
    array (
      'type' => 'email',
      'host' => '',
      'port' => '',
      'user' => '',
      'password' => '',
      'vertify_type' => '',
      'from' => '',
    ),
  ),
  'pay' => 
  array (
    'wxpay' => 
    array (
      'app_id' => 'wx5239dda9b1a7bb2a',
      'mch_id' => 1520247871,
      'key' => '8f41c6b26031cdfe47415c3376bb621c',
      'notify_url' => '',
    ),
    'alipay' => 
    array (
      'app_id' => '',
      'mch_id' => '',
      'key' => '',
      'notify_url' => '',
    ),
  ),
);