<?php

namespace app\admin\model;

use think\Model;


class ChatLog extends Model
{

    

    

    // 表名
    protected $name = 'chat_log';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'send_status_text',
        'create_time_text'
    ];
    

    
    public function getSendStatusList()
    {
        return ['1' => __('Send_status 1'), '0' => __('Send_status 0')];
    }


    public function getSendStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['send_status']) ? $data['send_status'] : '');
        $list = $this->getSendStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getCreateTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['create_time']) ? $data['create_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setCreateTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


}
