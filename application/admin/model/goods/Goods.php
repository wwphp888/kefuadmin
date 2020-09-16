<?php

namespace app\admin\model\goods;

use think\Model;


class Goods extends Model
{
    // 表名
    protected $name = 'goods';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'is_hot_text',
        'is_recomand_text',
        'status_text'
    ];

    public function getIsHotList()
    {
        return ['0' => __('Is_hot 0'), '1' => __('Is_hot 1')];
    }

    public function getIsRecomandList()
    {
        return ['0' => __('Is_recomand 0'), '1' => __('Is_recomand 1')];
    }

    public function getStatusList()
    {
        return ['0' => __('Status 0'), '1' => __('Status 1')];
    }


    public function getIsHotTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['is_hot']) ? $data['is_hot'] : '');
        $list = $this->getIsHotList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getIsRecomandTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['is_recomand']) ? $data['is_recomand'] : '');
        $list = $this->getIsRecomandList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function cate()
    {
        return $this->belongsTo('app\admin\model\Goods\Cate', 'cate_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
