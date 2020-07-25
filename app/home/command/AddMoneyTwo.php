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
class  AddMoneyTwo extends Command
{
    protected function configure()
    {
        $this->setName('AddMoneyTwo')->setDescription("查询榜单 自动加钱");
    }

    /**
     * 团队榜单
     * @param 传入参数1
     * @param 传入参数2
     * @return 返回格式json
     */
    protected function execute(Input $input, Output $output)
    {
        try {
            $output->writeln('Date Crontab job start...AddMoneyTwo');
            //获取上周起始日期
            $beginLastweek = mktime(0, 0, 0, date('m'), date('d') - date('w') + 1 - 7, date('Y'));
            $endLastweek = mktime(23, 59, 59, date('m'), date('d') - date('w') + 7 - 7, date('Y'));
            $weeks = date("YW", $endLastweek);
            $endToday = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
            $fieldtwo = 'l.pid as lpid,count(*) as num,u.agentId,u.pid,u.user_type,u.vip_type,u.user_nickname,u.truename,u.avatar,u.sex,u.user_money,u.deposit,u.income,u.yong_money,u.income,u.tuiguang_sum';
            $datatwo = db('user_tuiguang_log')->alias('l')
                ->join('user u', 'l.pid = u.id', 'right')
                ->where('l.create_time','>=',$beginLastweek)
                ->where('l.create_time','<=',$endLastweek)
                ->where('u.user_status','=',1)
                ->order('num desc')
                ->limit(10)
                ->field($fieldtwo)
                ->group('l.pid')
                ->select()
                ->toArray();
            if (!$datatwo) {
                Log::error('暂无团队榜单数据');
            }
            $setting = cmf_get_option('topmoney_setting');
            Db::startTrans();
            $data = array();
            $ids = array();
            $top = 1;
            $res = Db::name('rankinglist')->where(['weeks' => $weeks, 'type' => 2])->sum('isfa');
            if ($res > 0)
                throw new Exception('奖金已发放，不能重新排位');
            db()->query('delete from mc_rankinglist where weeks=' . $weeks . ' and type=2');
            foreach ($datatwo as $k => $v) {
                $dt['uid'] = $v['lpid'];
                $dt['type'] = 2;
                $dt['money'] = $setting['team']['top' . $top];
                $dt['top'] = $top;
                $dt['weeks'] = $weeks;
                $dt['numbertext'] = $v['num'];
                $dt['remark'] = '第' . $top . '名，团队数：' . $v['num'];
                $dt['create_time'] = date("Y-m-d H:i:s");
                array_push($data, $dt);
                array_push($ids, $v['lpid']);
                $top = $top + 1;
            }
            $userlist = Db::name('user')->where(['user_type' => ['in', [1, 4, 5]]])
                ->order('id desc')
                ->limit(300)
                ->field('id')
                ->select()
                ->toArray();
            $num = count($userlist) - 1;
            while (10 - $top >= 0) {
                $user = $userlist[$num];
                if (!in_array($user['id'], $ids)) {
                    $dt['uid'] = $user['id'];
                    $dt['type'] = 2;
                    $dt['money'] = 0;
                    $dt['top'] = $top;
                    $dt['weeks'] = $weeks;
                    $dt['numbertext'] = 0;
                    $dt['remark'] = '第' . $top . '名，团队数：' . 0;
                    $dt['create_time'] = date("Y-m-d H:i:s");
                    array_push($data, $dt);
                    $top = $top + 1;
                }
                $num = $num - 1;
                if (count($data) >= 10 || $num < 0)
                    break;

            }
            $res = db('rankinglist')->insertAll($data);
            if ($res) {
                Db::commit();
            } else {
                throw new Exception('任务榜排位异常');
            }
        } catch (Exception $e) {
            Db::rollback();
            Log::error($e->getMessage());
            $output->writeln('Date Crontab job start...AddMoneyTwo'.$e->getMessage());
        }
    }
}