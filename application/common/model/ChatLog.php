<?php

namespace app\common\model;


class ChatLog extends BaseModel
{
    protected static $name = 'chat_log';

    public static function updateSendStatus($id, $status = 0)
    {
        return self::where(['id' => $id])->update(['status' => $status]);
    }
}
