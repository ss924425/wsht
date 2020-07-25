<?php

namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;

class ReportController extends AdminBaseController
{
    //总报表
    public function index()
    {
        #############################################会员部分####################################################
        //今日起始
        $beginToday = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
        $endToday = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;
        $mapToday['create_time'] = array('between', array($beginToday, $endToday));
        //昨日起始
        $beginYesterday = mktime(0, 0, 0, date('m'), date('d') - 1, date('Y'));
        $endYesterday = mktime(0, 0, 0, date('m'), date('d'), date('Y')) - 1;
        $mapYesterday['create_time'] = array('between', array($beginYesterday, $endYesterday));
        //上周起始
        $beginLastweek = mktime(0, 0, 0, date('m'), date('d') - date('w') + 1 - 7, date('Y'));
        $endLastweek = mktime(23, 59, 59, date('m'), date('d') - date('w') + 7 - 7, date('Y'));
        $mapLastweek['create_time'] = array('between', array($beginLastweek, $endLastweek));
        //本月起始
        $beginThismonth = mktime(0, 0, 0, date('m'), 1, date('Y'));
        $endThismonth = mktime(23, 59, 59, date('m'), date('t'), date('Y'));
        $mapThismonth['create_time'] = array('between', array($beginThismonth, $endThismonth));

        //会员分布
        $mvip = db('user');
//        $map['user_type'] = ['in', '1,4,5,6'];
        $map['user_type'] = 1;
        $viptotal = $mvip->where($map)->count();
        $putongvip = $mvip->where('vip_type=1')->count();
        $czvip = $mvip->where('vip_type=2')->count();
        $agentvip = $mvip->where('vip_type=2')->count();
        $this->assign('viptotal', $viptotal);
        $this->assign('putongvip', $putongvip);
        $this->assign('czvip', $czvip);
        $this->assign('agentvip', $agentvip);
        //新会员
//        $mapToday['user_type'] = ['in', '1,4,5,6'];
//        $mapYesterday['user_type'] = ['in', '1,4,5,6'];
        $mapToday['user_type'] = 1;
        $mapYesterday['user_type'] = 1;
        $newvipToday = $mvip->where($mapToday)->count();
        $newvipYesterday = $mvip->where($mapYesterday)->count();
        //环比
        if ($newvipYesterday) {
            $newviprate = intval(($newvipToday - $newvipYesterday) / $newvipYesterday * 100);
        } else {
            $newviprate = $newvipToday * 100;
        }
        //总共
        if ($viptotal) {
            $newviptotalrate = intval($newvipToday / $viptotal * 100);
        } else {
            $newviptotalrate = $newvipToday * 100;
        }

        $this->assign('newvipToday', $newvipToday);
        $this->assign('newvipYesterday', $newvipYesterday);
        $this->assign('newviprate', $newviprate);
        $this->assign('newviptotalrate', $newviptotalrate);

        #############################################任务部分####################################################
        $morder = db('task_receive');
        $ordertotal = $morder->where('receive_type in (0,2,3)')->count();
        $goingorder = $morder->where('receive_type in (0,2)')->count();//进行、待审
        $finishorder = $morder->where('receive_type=3')->count();//完成
        $this->assign('ordertotal', $ordertotal);
        $this->assign('goingorder', $goingorder);
        $this->assign('finishorder', $finishorder);

        unset($mapToday['user_type']);
        unset($mapYesterday['user_type']);
        unset($mapToday['create_time']);
        unset($mapYesterday['create_time']);
        $mapToday['receive_time'] = array('between', array($beginToday, $endToday));
        $mapYesterday['receive_time'] = array('between', array($beginYesterday, $endYesterday));
        $neworderToday = $morder->where($mapToday)->count();
        $neworderYesterday = $morder->where($mapYesterday)->count();
        //环比
        if ($neworderYesterday) {
            $neworderrate = intval(($neworderToday - $neworderYesterday) / $neworderYesterday * 100);
        } else {
            $neworderrate = $neworderToday * 100;
        }
        //总共
        if ($ordertotal) {
            $newordertotalrate = intval($neworderToday / $ordertotal * 100);
        } else {
            $newordertotalrate = $neworderToday * 100;
        }
        $this->assign('neworderToday', $neworderToday);
        $this->assign('neworderYesterday', $neworderYesterday);
        $this->assign('neworderrate', $neworderrate);
        $this->assign('newordertotalrate', $newordertotalrate);

        #############################################分销部分####################################################
        $mfx = db('user_yong_log');
        $yongtotal = $mfx->sum('fxyj');
        $vipyong = $mfx->alias('A')->join('__USER__ B', 'A.user_id=B.id', 'left')->where('B.vip_type=2')->sum('fxyj');//vip的佣金
        $agentyong = $mfx->alias('A')->join('__USER__ B', 'A.user_id=B.id', 'left')->where('B.vip_type=3')->sum('fxyj');//代理商的佣金
        $gudongyong = $mfx->alias('A')->join('__USER__ B', 'A.user_id=B.id', 'left')->where('B.vip_type=4')->sum('fxyj');//股东的佣金
        $this->assign('yongtotal', $yongtotal);
        $this->assign('vipyong', $vipyong);
        $this->assign('agentyong', $agentyong);
        $this->assign('gudongyong', $gudongyong);

        unset($mapToday['receive_time']);
        unset($mapYesterday['receive_time']);
        $mapToday['create_time'] = array('between', array($beginToday, $endToday));
        $mapYesterday['create_time'] = array('between', array($beginYesterday, $endYesterday));
        $newyongToday = $mfx->where($mapToday)->sum('fxyj');
        $newyongYesterday = $mfx->where($mapYesterday)->sum('fxyj');
        //环比
        if ($newyongYesterday) {
            $newyongrate = intval(($newyongToday - $newyongYesterday) / $newyongYesterday * 100);
        } else {
            $newyongrate = $newyongToday * 100;
        }
        //总共
        if ($yongtotal) {
            $newyongtotalrate = intval($newyongToday / $yongtotal * 100);
        } else {
            $newyongtotalrate = $newyongToday * 100;
        }
        $this->assign('newyongToday', $newyongToday);
        $this->assign('newyongYesterday', $newyongYesterday);
        $this->assign('newyongrate', $newyongrate);
        $this->assign('newyongtotalrate', $newyongtotalrate);
        #############################################折线图部分####################################################
        //普通会员
        unset($map);
        $start_time = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
        $end_time = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;
        $putongarr = array();
        $viparr = array();
        $agentarr = array();
        for ($i = 0; $i < 7; $i++) {
            $timeout = 24 * 60 * 60 * $i;
            $start_time -= $timeout;
            $end_time -= $timeout;
            $res1 = $mvip->where(['vip_type'=>1])->where('create_time','between',[$start_time,$end_time])->count();
            $res2 = $mvip->where(['vip_type'=>2])->where('create_time','between',[$start_time,$end_time])->count();
            $res3 = $mvip->where(['vip_type'=>['in','3,4']])->where('create_time','between',[$start_time,$end_time])->count();
            array_unshift($putongarr,$res1);
            array_unshift($viparr,$res2);
            array_unshift($agentarr,$res3);
        }
        $line['putong'] = $putongarr;
        $line['vip'] = $viparr;
        $line['agent'] = $agentarr;
        $this->assign('line',json_encode($line));
        #############################################整体统计部分####################################################
        $mtask = db('task');
        $tasktotal = $mtask->count();
        $mcash = db('cash');
        $cashtotal = $mcash->where('cash_status=1')->sum('cash_money');
        $this->assign('tasktotal', $tasktotal);
        $this->assign('cashtotal', $cashtotal);
        return $this->fetch();
    }

    //代理商报表
    public function indexAgent()
    {
        $agentId = cmf_get_current_admin_id();
        $agentUsers = db('user')->where(['agentId'=>$agentId])->column('id');
        $putUser = db('user')->where(['pid'=>$agentId,'agentId' => 0])->column('id');
        $agentUsers = array_merge($agentUsers,$putUser);
        #############################################会员部分####################################################
        //今日起始
        $beginToday = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
        $endToday = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;
        $mapToday['create_time'] = array('between', array($beginToday, $endToday));
        $mapToday1['create_time'] = array('between', array($beginToday, $endToday));//用于团队查询
        //昨日起始
        $beginYesterday = mktime(0, 0, 0, date('m'), date('d') - 1, date('Y'));
        $endYesterday = mktime(0, 0, 0, date('m'), date('d'), date('Y')) - 1;
        $mapYesterday['create_time'] = array('between', array($beginYesterday, $endYesterday));
        $mapYesterday1['create_time'] = array('between', array($beginYesterday, $endYesterday));//用于团队查询
        //上周起始
        $beginLastweek = mktime(0, 0, 0, date('m'), date('d') - date('w') + 1 - 7, date('Y'));
        $endLastweek = mktime(23, 59, 59, date('m'), date('d') - date('w') + 7 - 7, date('Y'));
        $mapLastweek['create_time'] = array('between', array($beginLastweek, $endLastweek));
        $mapLastweek1['create_time'] = array('between', array($beginLastweek, $endLastweek));//用于团队查询
        //本月起始
        $beginThismonth = mktime(0, 0, 0, date('m'), 1, date('Y'));
        $endThismonth = mktime(23, 59, 59, date('m'), date('t'), date('Y'));
        $mapThismonth['create_time'] = array('between', array($beginThismonth, $endThismonth));
        $mapThismonth1['create_time'] = array('between', array($beginThismonth, $endThismonth));//用于团队查询

        //会员分布
        $mvip = db('user');
        $map['vip_type'] = ['in', '1,2'];
        $map1['agentId'] = $agentId;

        $user_num1 = $mvip->where($map) -> where($map1) ->count();
        $user_num2 = $mvip->where($map) -> where(['pid' => $agentId,'agentId' => 0])->count();
//        $viptotal = $mvip->where($map)->count();
        $viptotal = $user_num1 + $user_num2;

        $putong1 = $mvip->where('vip_type=1') -> where($map1)->count();
        $putong2 = $mvip->where('vip_type=1') -> where(['pid' => $agentId,'agentId' => 0])->count();
        $putongvip = $putong1 + $putong2;

//        $czvip = $mvip->where('vip_type=2')->count();
        $czvip1 = $mvip->where('vip_type=2')-> where($map1)->count();
        $czvip2 = $mvip->where('vip_type=2')-> where(['pid' => $agentId,'agentId' => 0])->count();
        $czvip = $czvip1 + $czvip2;

        $this->assign('viptotal', $viptotal);
        $this->assign('putongvip', $putongvip);
        $this->assign('czvip', $czvip);
        //新会员
            //-------------代理商下的会员------------------
//            $mapToday['user_type'] = ['in', '1,4'];
            $mapToday['agentId'] = $agentId;
//            $mapYesterday['user_type'] = ['in', '1,4'];
            $mapYesterday['agentId'] = $agentId;
            $newvipToday0 = $mvip->where($mapToday)->count();
            $newvipYesterday0 = $mvip->where($mapYesterday)->count();

            //-------------直推会员-----------------------
//            $mapToday1['user_type'] = ['in', '1,4'];
            $mapToday1['pid'] = $agentId;
            $mapToday1['agentId'] = 0;
//            $mapYesterday1['user_type'] = ['in', '1,4'];
            $mapYesterday1['pid'] = $agentId;
            $mapYesterday1['agentId'] = 0;
            $newvipToday1 = $mvip->where($mapToday1)->count();
            $newvipYesterday1 = $mvip->where($mapYesterday1)->count();

            //团队会员等于代理商下的加上直推的会员
            $newvipToday = $newvipToday0 + $newvipToday1;
            $newvipYesterday = $newvipYesterday0 + $newvipYesterday1;

        //环比
        if ($newvipYesterday) {
            $newviprate = intval(($newvipToday - $newvipYesterday) / $newvipYesterday * 100);
        } else {
            $newviprate = $newvipToday * 100;
        }
        //总共
        if ($viptotal) {
            $newviptotalrate = intval($newvipToday / $viptotal * 100);
        } else {
            $newviptotalrate = $newvipToday * 100;
        }

        $this->assign('newvipToday', $newvipToday);
        $this->assign('newvipYesterday', $newvipYesterday);
        $this->assign('newviprate', $newviprate);
        $this->assign('newviptotalrate', $newviptotalrate);

        #############################################任务部分####################################################
        $morder = db('task_receive');
        $ordertotal = $morder->where('receive_type in (0,2,3)')->where('uid','in',$agentUsers)->count();
        $goingorder = $morder->where('receive_type in (0,2)')->where('uid','in',$agentUsers)->count();//进行、待审
        $finishorder = $morder->where('receive_type=3')->where('uid','in',$agentUsers)->count();//完成
        $this->assign('ordertotal', $ordertotal);
        $this->assign('goingorder', $goingorder);
        $this->assign('finishorder', $finishorder);

        unset($mapToday['user_type']);
        unset($mapYesterday['user_type']);
        unset($mapToday['create_time']);
        unset($mapYesterday['create_time']);
        $mapToday['receive_time'] = array('between', array($beginToday, $endToday));
        $mapYesterday['receive_time'] = array('between', array($beginYesterday, $endYesterday));
        $neworderToday = $morder->where($mapToday)->where('uid','in',$agentUsers)->count();
        $neworderYesterday = $morder->where($mapYesterday)->where('uid','in',$agentUsers)->count();
        
        //环比
        if ($neworderYesterday) {
            $neworderrate = intval(($neworderToday - $neworderYesterday) / $neworderYesterday * 100);
        } else {
            $neworderrate = $neworderToday * 100;
        }
        //总共
        if ($ordertotal) {
            $newordertotalrate = intval($neworderToday / $ordertotal * 100);
        } else {
            $newordertotalrate = $neworderToday * 100;
        }
        $this->assign('neworderToday', $neworderToday);
        $this->assign('neworderYesterday', $neworderYesterday);
        $this->assign('neworderrate', $neworderrate);
        $this->assign('newordertotalrate', $newordertotalrate);

        #############################################分销部分####################################################
        $mfx = db('user_yong_log');
        $yongtotal = $mfx->where(['user_id'=>$agentId]) -> where('type = 0')->sum('fxyj');
        $fxyong = $mfx
            ->where('yong_type=1')
            -> where('type = 0')
            ->where(['user_id'=>$agentId])
            ->sum('fxyj');//vip的佣金
        $gudingyong = $mfx
            ->where('yong_type=2')
            -> where('type = 0')
            ->where(['user_id'=>$agentId])
            ->sum('fxyj');//vip的佣金
        $this->assign('yongtotal', $yongtotal);
        $this->assign('fxyong', $fxyong);
        $this->assign('gudingyong', $gudingyong);

        unset($mapToday['receive_time']);
        unset($mapToday['agentId']);
        unset($mapYesterday['receive_time']);
        unset($mapYesterday['agentId']);

        $mapToday['create_time'] = array('between', array($beginToday, $endToday));
        $mapYesterday['create_time'] = array('between', array($beginYesterday, $endYesterday));
        $newyongToday = $mfx->where($mapToday)->where('user_id','eq',$agentId) -> where('type = 0') ->sum('fxyj');
        $newyongYesterday = $mfx->where($mapYesterday)->where('user_id','eq',$agentId) -> where('type = 0') ->sum('fxyj');
        //环比
        if ($newyongYesterday) {
            $newyongrate = intval(($newyongToday - $newyongYesterday) / $newyongYesterday * 100);
        } else {
            $newyongrate = $newyongToday * 100;
        }
        //总共
        if ($yongtotal) {
            $newyongtotalrate = intval($newyongToday / $yongtotal * 100);
        } else {
            $newyongtotalrate = $newyongToday * 100;
        }
        $this->assign('newyongToday', $newyongToday);
        $this->assign('newyongYesterday', $newyongYesterday);
        $this->assign('newyongrate', $newyongrate);
        $this->assign('newyongtotalrate', $newyongtotalrate);
        #############################################折线图部分####################################################
        //普通会员
        unset($map);
        $start_time = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
        $end_time = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;
        $putongarr = array();
        $viparr = array();
        for ($i = 0; $i < 7; $i++) {
            $timeout = 24 * 60 * 60 * $i;
            $start_time -= $timeout;
            $end_time -= $timeout;
            $res1 = $mvip
                ->where(['vip_type'=>1])
                ->where('create_time','between',[$start_time,$end_time])
                ->where('id','in',$agentUsers)
                ->count();
            $res2 = $mvip
                ->where('vip_type >= 2')
                ->where('create_time','between',[$start_time,$end_time])
                ->where('id','in',$agentUsers)
                ->count();
            array_unshift($putongarr,$res1);
            array_unshift($viparr,$res2);
        }
        $line['putong'] = $putongarr;
        $line['vip'] = $viparr;
        $this->assign('line',json_encode($line));
        #############################################整体统计部分####################################################
        $mcash = db('cash');
        $cashtotal = $mcash->where('cash_status=1')->where(['uid'=>$agentId])->sum('cash_money');
        $this->assign('cashtotal', $cashtotal);
        return $this->fetch();
    }
}