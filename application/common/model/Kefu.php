<?php

namespace app\common\model;

use think\Model;

class Kefu extends Model
{
    // 表名
    protected $name = 'kefu';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    public static function getKefu($kf_code)
    {
        return self::where(['kf_code' => $kf_code])->find()->toArray();
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
            'online_status' => 1
        ];
        return self::where($where)->select()->toArray();
    }
}
