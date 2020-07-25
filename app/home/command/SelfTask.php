<?php

namespace app\home\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;
use think\Log;
use app\common\model\SelfTaskModel;

/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019/6/24
 * Time: 16:15
 */
class SelfTask extends Command
{
    protected function configure()
    {
        $this->setName('SelfTask')->setDescription("自动释放任务");
    }

    protected function execute(Input $input, Output $output)
    {
        try {
            $output->writeln('Date Crontab job start...');
            $time = time();
            /***获取需要处理的数据 ***/

            $ids = db('self_task_receive')->where("endtime", "<", $time)->column('id');
            $taskids = db('self_task_receive')->where("endtime", "<", $time)->column('taskid');

            if (!$ids || !$taskids) Log::info('暂无数据');

            Db::startTrans();
            $res1 = db('self_task_receive')->where('id','in',$ids)->delete();

            $res2 = db('self_task')->where('id','in',$taskids)->setInc('oldnum');

            if ($res1 && $res2){
                $res3 = db('self_task')->where('id','in',$taskids)->where('isempty','=',0)->setField('isempty',0);
                if ($res3){
                    Db::commit();
                    Log::info('' . '成功执行事务');
                }
            } else {
                Db::rollback();
                Log::error('' . '执行失败回滚事务');
            }

        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }
}