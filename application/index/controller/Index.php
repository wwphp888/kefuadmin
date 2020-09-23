<?php
namespace app\index\controller;

use app\common\controller\Frontend;
use app\common\model\Visitor;
use think\Cookie;

class Index extends Frontend
{
    protected $layout = 'default';

    public function index()
    {
        $uid = input('param.uid');
        $merchant_id = input('param.merchant_id');

        if (!$merchant_id) {
            echo '缺少商户';exit;
        }

        if (!$uid) {
            $uid = Cookie::get('visitor:id');
            if (!$uid) {
                $uid = time() . rand(1000, 9999);
                Cookie::set('visitor:id', $uid, 3600 * 24);
            }
        }
        //保存访客信息
        $visitor = Visitor::getInfo($uid);
        if ($visitor) {
            Visitor::where(['visitor_id' => $uid])->update([
                'visitor_ip' => request()->ip(),
                'update_time' => time(),
            ]);
        } else {
            Visitor::insert([
                'visitor_id' => $uid,
                'name' => $uid,
                'avatar' => '',
                'visitor_ip' => request()->ip(),
                'create_time' => time()
            ]);
        }

        $this->assign([
            'visitor_id' => $uid,
            'merchant_id' => $merchant_id,
            'name' => $uid,
            'avatar' => '',
            'port' => '9510',
        ]);
        return $this->view->fetch();
    }

    /**
     * 获取验证码
     * @return mixed
     */
    public function verify()
    {
        $config = [
            'fontSize' => 50, // 验证码字体大小
            'length' => 4, // 验证码位数
        ];
        $captcha = new Captcha($config);
        return $captcha->entry();
    }

}