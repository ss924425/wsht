<?php

namespace app\index\controller;

use app\common\model\SelfTaskModel;
use think\Controller;
use think\Db;

class QueueController extends Controller
{
    private $islock = array('value' => 0, 'expire' => 0);
    private $expiretime = 600; //锁过期时间，秒
    private $setting;

    //初始赋值
    public function __construct()
    {
        parent::__construct();
        $lock = \TbUtil::getCache('queuelock', 'first');
        if (!empty($lock)) $this->islock = $lock;
        $this->setting = cmf_get_option('selftask_setting');
    }

    public function queueMain()
    {

        if ($this->checkLock()) {
            return false; //锁定的时候直接返回
        } else {
            $this->setLock(); //没锁的话锁定
        }

        $this->dealTask(); //普通任务
        $this->deleteLock(); //执行完删除锁
    }

    //加锁
    private function setLock()
    {
        $array = array('value' => 1, 'expire' => time());
        \TbUtil::setCache('queuelock', 'first', $array);
        $this->islock = $array;
    }

    //删除锁


    //检查是否锁定
    private function checkLock()
    {
        $lock = $this->islock;
        if ($lock['value'] == 1 && $lock['expire'] < (time() - $this->expiretime)) { //过期了，删除锁
            $this->deleteLock();
            return false;
        }
        if (empty($lock['value'])) {
            return false;
        } else {
            return true;
        }
    }

    private function dealTask()
    {
        try {
            $model_task = new SelfTaskModel();
            // 改变是否已被抢完 普通和试用任务
            $where0 = array('status' => 0, 'iscount' => 0, 'isempty' => 1);
            $allempty = db('self_task')->field('id,num,type')->where($where0)->limit(0,20110)->order('end','DESC')->select();
            if (!empty($allempty)) {
                foreach ($allempty as $k => $v) {
                    $last = $model_task::isEmpty($v['id'], $v['num']);
                    if ($last > 0) {
                        db('self_task')->where(array('id' => $v['id']))->update(array('isempty' => 0));
                    }
                }
            }
            // 将已被抢完的改成 被抢完 普通和试用任务
            $where3 = array('status' => 0, 'iscount' => 0, 'isempty' => 0);
            $allemptys = db('self_task')->field('id,num,type')->where($where3)->limit(0,20110)->order('end','DESC')->select();
            if (!empty($allemptys)) {
                foreach ($allemptys as $k => $v) {
                    $last = $model_task::isEmpty($v['id'], $v['num']);
                    if ($last <= 0) {
                        db('self_task')->where(array('id' => $v['id']))->update(array('isempty' => 1));
                    }
                }
            }

            // 结算任务 普通任务
//            $where1 = array('iscount' => 0, 'end' => array('lt', time()), 'type' => 0);
//            $needcount = db('self_task')->where($where1)->limit(0, 20110)->order('end', 'DESC')->select();
//            if (!empty($needcount)) {
//                foreach ($needcount as $k => $v) {
//                    $counting = \TbUtil::getCache('counttask', $v['id']);
//                    if (is_array($counting) && $counting['status'] == 1) {
//                        continue;
//                    }
//                    \TbUtil::setCache('counttask', $v['id'], array('status' => 1));
//                    $model_task::countTask($v);
//                    \TbUtil::deleteCache('counttask', $v['id']);
//                }
//            }


            // 改变标识未开始的任务 不分任务类型
            $where2 = array('isstart' => 1, 'start' => array('lt', time()));
            $needstart = db('self_task')->field('id')->where($where2)->limit('0', 20110)->order('end', 'DESC')->select();
            if (!empty($needstart)) {
                foreach ($needstart as $k => $v) {
                    db('self_task')->where(array('id' => $v['id']))->update(array('isstart' => 0));
                }
            }

            // 即时改变任务剩余数量缓存 不分任务类型
            $where4 = array('status' => 0, 'iscount' => 0, 'isempty' => 0);
            $allemptya = db('self_task')->field('id,num,type')->where($where4)->limit(1, 20110)->order('end', 'DESC')->select();

            if (!empty($allemptya)) {
                foreach ($allemptya as $k => $v) {
                    $last = $model_task::isEmpty($v['id'], $v['num']);
                    \TbUtil::setCache('takednum', $v['id'], $v['num'] - $last);
                }
            }
        } catch (\Exception $e) {
            file_put_contents('queue_error.log', $e->getMessage() . PHP_EOL, FILE_APPEND);
        }
    }
}