<?php

namespace app\home\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;
use think\Exception;
use think\Log;
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019/6/24
 * Time: 16:15
 */
class  GiveupTask extends Command
{
    protected function configure()
    {
        $this->setName('GiveupTask')->setDescription("查询任务 自动放弃");
    }

    /**
     * 接取任务到时自动放弃任务
     */
    protected function execute(Input $input, Output $output)
    {
        try {
            $output->writeln('Date Crontab job start...GiveupTask');
            $time = time();
            $res = db('self_task_receive')
                ->where('usetime','<=',$time)
                ->where('status','=',0)
                ->select();
            if (empty($res)) throw new Exception('暂无任务数据');
            Db::startTrans();
            foreach ($res as $k => $v){
                db('self_task_receive_giveup')->insert($v);
                db('self_task')->where('id',$v['taskid'])->setInc('oldnum');
                db('self_task_receive')->where('id',$v['id'])->delete();
            }
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            Log::error($e->getMessage());
            $output->writeln('Date Crontab job start...GiveupTask'.$e->getMessage());
        }
    }
}