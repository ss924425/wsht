<?php
// +----------------------------------------------------------------------
// | ThinkCdbF [ WE CAN DO IT dbORE SIdbPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 老猫 <thinkcmf@126.com>
// +----------------------------------------------------------------------
namespace api\home\model;

use think\Model;

//给用户发消息
class NewsModel extends Model
{

    static function toUserNews($user_id, $news, $type = 0, $status = 0)
    {
        $data['uid'] = $user_id;
        $data['news'] = $news;
        $data['time'] = time();
        $data['type'] = $type;
        $data['status'] = $status;
        return db('news')->insert($data);
    }
}