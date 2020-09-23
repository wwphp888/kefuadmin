<?php
/**
 * @Copyright (C), WANGWEI.
 * @Author 王维
 * @Name model.php
 * @Date: 2019/1/8
 * @Time: 20:54
 * @Description
 */

namespace app\common\model;

use think\Db;

/**
 * Class Model
 * @package think
 * @mixin Db
 */
class BaseModel
{
    protected static $name = '';
    protected static $connection = '';
    protected static $pk = 'id';

    /**
     * @desc 调用db
     * @param $method
     * @param $args
     * @return mixed
     * @throws \think\Exception
     */
    public static function __callStatic($method, $args)
    {
        $db = Db::connect(static::$connection, false)->name(static::$name)->pk(static::$pk);
        return call_user_func_array([$db, $method], $args);
    }
}