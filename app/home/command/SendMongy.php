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
class  SendMongy extends Command
{
    protected function configure()
    {
        $this->setName('SendMongy')->setDescription("排行榜奖金发放");
    }

    /**
     * 任务榜
     * @param 传入参数1
     * @param 传入参数2
     * @return 返回格式json
     */
    protected function execute(Input $input, Output $output)
    {
        try {
            $output->writeln('Date Crontab job start...SendMongy');
            $endLastweek = mktime(23, 59, 59, date('m'), date('d') - date('w') + 7 - 7, date('Y'));
            $weeks = date("YW", $endLastweek);
            $list = Db::name('rankinglist')->where(['weeks' => $weeks, 'isfa' => 0])->select()->toArray();
            foreach ($list as $k => $v) {
                try {
                    if ($v['money'] > 0) {
                        $msg = '用户-' . $v["uid"];
                        Db::startTrans();
                        $where['id'] = $v['uid'];
                        $user = Db::name('user')->where($where)->find();
                        if (!$user)
                            throw new Exception('此用户不存在');
                        $result = db('user')->where($where)->setInc('income', $v['money']);
                        if (!$result)
                            throw new Exception('奖金发放失败');
                        //用户金额变动记录
                        $log['user_id'] = $v['uid'];
                        $log['create_time'] = time();
                        $log['coin'] = $v['money'];
                        $log['income'] = $v['money'] + $user['income'];
                        $log['notes'] = '用户:' . $v['uid'] . '本周完成任务量进入榜单奖励金额' . $v['money'] . '元';
                        $result = db('rankinglist')->where(['id' => $v['id']])->update(['isfa' => 1]);
                        if (!$result)
                            throw new Exception('奖金发放状态更新失败');
                        $result = db('user_money_log')->insert($log);
                        if (!$result)
                            throw new Exception('发放日志插入失败');
                        Db::commit();
                        Log::info($msg . '奖金发放成功，插入发放日志');
                    } else {
                        $result = db('rankinglist')->where(['id' => $v['id']])->update(['isfa' => 1]);
                        if (!$result)
                            throw new Exception('奖金发放状态更新失败');
                    }
                } catch (Exception $exception) {
                    Db::rollback();
                    Log::error($exception->getMessage());
                    $output->writeln('Date Crontab job start...SendMongy' . $exception->getMessage());
                }
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            $output->writeln('Date Crontab job start...SendMongy' . $e->getMessage());
        }
    }
}