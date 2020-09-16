<?php

namespace app\admin\model\goods;

use fast\Tree;
use think\Model;


class Cate extends Model
{
    // 表名
    protected $name = 'goods_cate';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];

    protected static function init()
    {
        self::afterInsert(function ($row) {
            $pk = $row->getPk();
            $row->getQuery()->where($pk, $row[$pk])->update(['weigh' => $row[$pk]]);
        });
    }

    public function goods()
    {
        return $this->hasOne('Goods');
    }

    /**
     * @desc 得到分类树
     * @return mixed
     */
    public function getCateList()
    {
        $cateList = collection($this->order('weigh', 'desc')->order('id', 'asc')->select())->toArray();
        Tree::instance()->init($cateList);
        return Tree::instance()->getTreeList(Tree::instance()->getTreeArray(0), 'name');
    }

    /**
     * @desc 得到分类树选项
     * @param null $cateList
     * @return array
     */
    public function getCateOptions($cateList = null)
    {
        if (!$cateList) {
            $cateList = $this->getCateList();
        }
        $cateOptions[0] = '顶级';
        foreach ($cateList as $v) {
            $cateOptions[$v['id']] = $v['name'];
        }
        return $cateOptions;
    }
}
