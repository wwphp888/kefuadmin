<?php

namespace app\common\library;

use Yansongda\Pay\Pay as PayApi;
use think\Config;

/**
 * 支付类
 */
class Pay
{
    private static $payWay = [
        1 => 'wxpay',
        2 => 'alipay',
    ];

    /**
     * @desc 发起支付
     * @param $type
     * @param $mode
     * @param $data
     * @return mixed
     */
    public static function send($type, $mode, $data)
    {
        return call_user_func_array([self::class, self::$payWay[$type]], [$mode, $data]);
    }

    /**
     * @desc 微信支付
     * @param $mode
     * @param array $data
     * @return mixed
     */
    public static function wxpay($mode, $data = [])
    {
        $setting = Config::get('setting');

        if (empty($setting['pay']['wxpay'])) {
            throw new \Exception('微信支付未设置');
        }
        $pay = $setting['pay']['wxpay'];
        if (empty($pay['app_id']) || empty($pay['mch_id']) || empty($pay['key']) || empty($pay['notify_url'])) {
            throw new \Exception('微信支付设置不完整');
        }

        $config = [
            'app_id'  => $pay['app_id'],
            'mch_id' => $pay['mch_id'],
            'key' => $pay['key'],
            'notify_url' => $pay['notify_url'],
            'http' => [
                'timeout' => 3,
                'connect_timeout' => 3,
            ]
        ];

        $body = [
            'out_trade_no' => $data['orderno'],
            'total_fee' => $data['amount'] * 100,
            'body' => $data['body'],
        ];
        if (!empty($data['openid'])) {
            $body['openid'] = $data['openid'];
        }
        return PayApi::wechat($config)->{$mode}($body);
    }

    /**
     * @desc 支付宝支付
     * @param $mode
     * @param array $data
     * @return mixed
     */
    public static function alipay($mode, $data = [])
    {
        $setting = Config::get('setting');

        if (empty($setting['pay']['alipay'])) {
            throw new \Exception('微信支付未设置');
        }
        $pay = $setting['pay']['alipay'];
        if (empty($pay['app_id']) || empty($pay['notify_url']) || empty($pay['return_url']) || empty($pay['private_key'])) {
            throw new \Exception('支付宝设置不完整');
        }

        $config = [
            'app_id' => $pay['app_id'],
            'notify_url' => $pay['notify_url'],
            'return_url' => $pay['return_url'],
            //'ali_public_key' => 'lejian201811@163.com',
            'private_key' => $pay['private_key'],
            'http' => [
                'timeout' => 3,
                'connect_timeout' => 3,
            ]
        ];
        $body = [
            'out_trade_no' => $data['orderno'],
            'total_fee' => $data['amount'] * 100,
            'subject' => $data['body'],
        ];
        return PayApi::alipay($config)->{$mode}($body);
    }
}
