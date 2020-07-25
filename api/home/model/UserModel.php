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

class UserModel extends Model
{
    // 是否管理员
    static function isAdmin($user_info)
    {
        if (in_array($user_info['user_type'], array(1, 2))) return true;
        return false;
    }

    static function getSingleUser($user_id)
    {
        return db('user')->where('id',$user_id)->find();
    }
}