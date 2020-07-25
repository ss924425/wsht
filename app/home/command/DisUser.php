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
class DisUser extends Command
{
    protected function configure()
    {
        $this->setName('DisUser')->setDescription("查询信用积分 自动拉黑用户");
    }

    protected function execute(Input $input, Output $output)
    {
        try {
            $output->writeln('Date Crontab job start...DisUser');
            $uids = db('user')
                ->where('credit_score','<',150)
                ->where('user_status','<>',0)
                ->order('credit_score desc')
                ->column('id');
            if (!$uids) {
                throw new Exception('暂无用户数据');
            }

            Db::startTrans();
            $res = db('user')->where('id','in',$uids)->setField('user_status',0);
            if ($res) {
                Db::commit();
            } else {
                throw new Exception('拉黑用户异常');
            }
        } catch (\Exception $e) {
            Db::rollback();
            Log::error($e->getMessage());
            $output->writeln('Date Crontab job start...DisUser'.$e->getMessage());
        }
    }
}