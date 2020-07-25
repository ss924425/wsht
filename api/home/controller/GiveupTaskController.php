<?php

namespace api\home\controller;


use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;
use think\Log;

class GiveupTaskController extends Command
{
    protected function configure()
    {
        $this->setName('SelfTask')->setDescription("自动放弃任务");
    }
    function test1()
    {
        try {
            $time = time();
            /***获取需要处理的数据 ***/

            $ids = db('self_task_receive')->where("usetime", "<", $time)
                ->where('status','=',0)
                ->where('createtime','>','1577437200')
                ->column('id');
            $taskids = db('self_task_receive')->where("id", "in", $ids)->column('taskid');
            if (!$ids || !$taskids) Log::info('暂无数据');

            db('self_task_receive')->where('id','in',$ids)->delete();
            foreach ($taskids as $id){
                db('self_task')->where('id',$id)->setInc('oldnum');
                db('self_task')->where('id',$id)->setField('isempty',0);
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }
}