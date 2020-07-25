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
class  Auto extends  Command
{
    protected function configure()
    {
        $this->setName('Auto')->setDescription("自动完成任务 计划任务");
    }

    protected function execute(Input $input, Output $output)
    {
        try {
            $output->writeln('Date Crontab job start...');
            $create_time = time();
            /***获取需要处理的数据 ***/
            $data = db('self_task')->alias("a")->join("self_task_receive b",
                " a.id=b.taskid ")->where("b.status", "=", 1)->
            where("a.end", "<", $create_time)
                ->fieldRaw("b.*,a.continueid,a.continue ,a.id AS sid,b.status as sstatus")
                ->select()->toArray();

            if (!$data)
                Log::info('暂无需要采纳的数据');
            $setting = cmf_get_option('selftask_setting');

            $model_task = new SelfTaskModel();

            foreach ($data as $k => $v) {
                $flag = 0;
                $msg = '任务-' . $v["id"] . '-' . $v["taskid"];
                Db::startTrans();
                $where['id'] = $v['id'];
                $where['status'] = 1;
                $taked = db('self_task_receive')->where($where)->find();
                $result = \db('self_task_receive')->where('id', $v['id'])->setField('status', 2);
                if ($result && $taked) {

                    $task = db('self_task')->where(array('id' => $taked['taskid']))->find();

                    $res1 = $model_task::agreeTask($setting, $v,'','');

                    if ($res1) {
                        Log::info($msg . '采纳成功');
                        $flag = 1;
                    } else {
                        Log::error($msg . '采纳失败');
                    }
                    if ($flag > 0) {
                        Db::commit();
                        Log::info($msg . '成功执行事务');
                    } else {
                        Db::rollback();
                        Log::error($msg . '执行失败回滚事务');
                    }
                } else {
                    Log::error($msg . '更新采纳状态失败');
                }
            }
        }
        catch (\Exception $e)
        {
            Log::error($e->getMessage());
        }
    }
}