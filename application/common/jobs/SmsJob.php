<?php
/**
 * Created by PhpStorm.
 * User: zhoujun
 * Date: 2018/9/1
 * Time: 11:40
 */
namespace app\common\jobs;

use think\queue\job;

class SmsJob
{
    public function fire(Job $job, $data){

        file_put_contents(ROOT_PATH . 'ww.txt', 112, FILE_APPEND);
        $job->delete();
    }
}