<?php
namespace app\index\controller;

use app\common\controller\Frontend;
use app\common\model\Visitor;
use app\common\model\Kefu as KefuModel;

class Kefu extends Frontend
{
    protected $layout = 'default';

    public function index()
    {
        $kfCode = input('param.kf_code');

        //保存访客信息
        $kefu = KefuModel::getInfo($kfCode);

        $this->assign([
            'kf_code' => $kefu['kf_code'],
            'name' => $kefu['name'],
            'avatar' => $kefu['avatar']
        ]);
        return $this->view->fetch();
    }
}