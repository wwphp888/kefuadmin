<?php

namespace app\common\model;

class Kefu extends BaseModel
{
    protected static $name = 'kefu';

    public static function getInfo($kf_code)
    {
        return self::where(['kf_code' => $kf_code])->find();
    }

    public static function updateOnlineStatus($kf_code, $status)
    {
        return self::where(['kf_code' => $kf_code])->update(['online_status' => $status]);
    }

    public static function getOnlineKefu($merchant_id)
    {
        $where = [
            'merchant_id' => $merchant_id,
            'status' => 1,
            'online_status' => 'online'
        ];
        return self::where($where)->select();
    }
}
