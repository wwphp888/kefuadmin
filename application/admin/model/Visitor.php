<?php

namespace app\admin\model;

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

    // 追加属性
    protected $append = [
        'online_status_text',
        'create_time_text',
        'update_time_text'
    ];
    

    
    public function getOnlineStatusList()
    {
        return ['online' => __('Online_status online'), 'offline' => __('Online_status offline')];
    }


    public function getOnlineStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['online_status']) ? $data['online_status'] : '');
        $list = $this->getOnlineStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getCreateTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['create_time']) ? $data['create_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getUpdateTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['update_time']) ? $data['update_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setCreateTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setUpdateTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


}
