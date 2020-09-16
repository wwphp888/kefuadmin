<?php

namespace app\admin\controller\goods;

use app\common\controller\Backend;
use fast\Tree;

/**
 * 商品分类
 */
class Cate extends Backend
{
    protected $model = null;
    protected $cateList = [];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\goods\Cate;
        $this->cateList = $this->model->getCateList();
        $this->view->assign("cateList", $this->model->getCateOptions($this->cateList));
    }

    /**
     * 查看
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            $list = $this->cateList;
            $total = count($this->cateList);

            return json(['total' => $total, 'rows' => $list]);
        }
        return $this->view->fetch();
    }

    /**
     * @desc 删除
     * @param string $ids
     */
    public function del($ids = "")
    {
        if ($ids) {
            $delIds = [];
            foreach (explode(',', $ids) as $k => $v) {
                $delIds = array_merge($delIds, Tree::instance()->getChildrenIds($v, true));
            }
            $delIds = array_unique($delIds);
            $count = $this->model->where('id', 'in', $delIds)->delete();
            if ($count) {
                $this->success();
            }
        }
        $this->error();
    }
}