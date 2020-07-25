<?php

namespace app\home\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019/6/24
 * Time: 16:15
 */
class  AutoController extends  Command
{
    protected function configure()
    {
        $this->setName('Auto')->setDescription("自动完成任务 计划任务");
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('Date Crontab job start...');
        /*** 这里写计划任务列表集 START ***/
        $data = db('self_task_receive')->where('status','neq',3)->where('status','neq',2)->select()->toArray();
        Db::startTrans();
        foreach ($data as $k =>$v){
            $res = \TbUtil::lastTime($v['endtime']);
            if ($res == '0天0时0分'){
                $result = \db('self_task_receive')->where('id',$v['id'])->setField('status',2);
                if (!$result){
                    Db::rollback();
                    return $this->error('自动采纳任务失败,联系管理员!');
                }
                Db::commit();
            }

        }
        /*** 这里写计划任务列表集 END ***/
        $output->writeln('Date Crontab job end...');
    }

}