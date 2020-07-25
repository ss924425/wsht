<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 老猫 <thinkcmf@126.com>
// +----------------------------------------------------------------------
namespace app\common\model;

use think\Model;

class UserModel extends Model
{
    static function getMyData($uid, $cache = null)
    {
        if (empty($cache)) {
            // 未开始的
            $cache['pubed1'] = db('self_task')->where(array('userid' => $uid, 'status|isstart' => 1))->count();
            // 进行中的
            $cache['pubed2'] = db('self_task')->where(array('status' => 0, 'start' => array('lt', time()), 'iscount' => 0, 'userid' => $uid))->count();
            // 已结算的
            $cache['pubed3'] = db('self_task')->where(array('iscount' => 1, 'userid' => $uid))->count();


            // 待回复的任务
            $cache['takeda'] = db('self_task_receive')->where(array('status' => 0, 'endtime' => array('gt', time()), 'userid' => $uid))->count();
            // 待审核
            $cache['takedb'] = db('self_task_receive')->where(array('status' => 1, 'endtime' => array('gt', time()), 'userid' => $uid))->count();
            // 已完成
            $cache['takedc'] = db('self_task_receive')->where(array('status' => 2, 'endtime' => array('gt', time()), 'userid' => $uid))->count();
            // 未通过
            $cache['takedd'] = db('self_task_receive')->where(array('status' => 3, 'endtime' => array('gt', time()), 'userid' => $uid))->count();

            // 我的小弟
            $cache['down'] = db('user')->where(array('pid' => $uid))->count();

            // 未读消息
            $cache['imess'] = db('news')->where(array('uid' => $uid, 'status' => 0))->count();
        }
        return $cache;
    }
}