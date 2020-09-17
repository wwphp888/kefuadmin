<?php

namespace app\common\model;

use think\Model;

class Visitor extends Model
{
    // 表名
    protected $name = 'visitor';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    public static function getInfo($id)
    {
        return self::where(['id' => $id])->find()->toArray();
    }

    public static function updateClientId($id, $clientId)
    {
        return self::where(['visitor_id' => $id])->update(['client_id' => $clientId]);
    }

    public static function updateKfClientId($kf_code, $kf_client_id)
    {
        return self::where(['kf_code' => $kf_code])->update(['kf_client_id' => $kf_client_id]);
    }

    public static function getQueueing($kf_code)
    {
        return self::where(['kf_code' => $kf_code])->select()->where();
    }

    public static function getKfServiceNum($kf_codes)
    {
        return self::where(['kf_code' => ['in', $kf_codes]])->group('kf_code')->column('kf_code, count(*)');
    }

    public static function logoutVitisor($visitor_id)
    {
        $data = [
            'online_status' => 0,
            'kf_code' => '',
            'kf_client_id' => 0,
        ];
        return self::where(['visitor_id' => $visitor_id])->update($data);
    }
}
