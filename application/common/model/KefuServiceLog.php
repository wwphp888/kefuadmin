<?php

namespace app\common\model;

use think\Model;

class KefuServiceLog extends Model
{
    // 表名
    protected $name = 'kefu_service_log';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;
}
