<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace api\home\controller;

use app\fenxiao\controller\FenxiaoController;
use think\Db;
use think\Validate;
use cmf\controller\RestBaseController;

class TestController extends RestBaseController
{
    public function demo1()
    {
        $hidetxt = '妈的，尼玛,';
        $list = preg_split("/,|，/", trim($hidetxt,',，'));
        dump($list);
        $content = "尼玛我发的不好吗妈的再不给审核";
        echo $content;
        foreach($list as $find){
            $content = str_replace($find,'*',$content);
        }
        echo '<hr>';
        echo $content;
    }

    public function demo2()
    {
        echo "http://" . $_SERVER['HTTP_HOST'] . "/api/index.php" . url('pay/Alipaycz/notify');
    }
    //佣金收益排行
    public function day_rank()
    {
        $request = $this->request->param();

        if(isset($request['type'])){
            $type = $request['type'];

            $list = db('ranking_list') -> where(['type' => $type]) -> select() -> toArray();
//            $list = cache('day_rank_timing_week');
            if(!$list) {
                $list = [];
            }

            $this->success('查询完成！', $list);
        }else{
            $this -> error('请选择查询类型');
        }
    }

    //排行榜定时查询
    public function day_rank_timing()
    {
        //周排行
//        $begtime = strtotime(date('Y-m-d', strtotime("this week Monday", $endtime)));
//        $endtime = time();
        $begtime = strtotime('-1 monday', time());
        $endtime = strtotime('-1 sunday', time() + 3600*24 - 1);

        $list = Db::query("SELECT * from (
SELECT ur.id,ur.mobile,max(ur.avatar)avatar,sum(ifnull(ulog.coin,0))+sum(ifnull(ylog.fxyj,0)) ymoney from mc_user ur

LEFT join mc_user_money_log ulog on ur.id=ulog.user_id and ulog.create_time>=$begtime and ulog.create_time<=$endtime

LEFT join mc_user_yong_log ylog on ur.id=ylog.user_id and ylog.create_time>=$begtime and ylog.create_time<=$endtime

GROUP by ur.id,ur.mobile)MM order by MM.ymoney desc LIMIT 10");

        foreach ($list as $k => $v) {
//            $list[$k]['zyj'] = sprintf("%.2f", $v['ymoney']);
            $list[$k]['zyj'] = $v['ymoney'];
            if ($v['mobile']) {
                $list[$k]['mobile'] = substr_replace($v['mobile'], '****', 3, 4);
            }
            $list[$k]['user_id'] = $v['id'];
            $list[$k]['type'] = 2;
            $list[$k]['create_time'] = date('Y-m-d H:i:s',time());
            unset($list[$k]['id']);
            unset($list[$k]['ymoney']);
        }

        db('ranking_list') -> where(['type' => 2]) -> delete();

        db('ranking_list') -> insertAll($list);
//        cache('day_rank_timing_week', $list);
//        var_dump($list);die;

    }

    public function sign()
    {
        $request = $this->request->param();
        $token = $request['token'];
        $data = db('user_token')->where(['token' => $token])->field('user_id')->find();
        $uid = $data['user_id'];
        if($uid) {
            $m = db('sign_record');

            if(isset($request['nowYear']) || isset($request['nowMonth'])){
                $nowYear = $request['nowYear'];
                $nowMonth = $request['nowMonth'];

                $mouth = $this -> mFristAndLast($nowYear,$nowMonth);
                $map['sign_time'] = array('between', $mouth);
                $map['vipid'] = $uid;
                $cache = $m->where($map)->field('is_zengsong,sign_time')->select() -> toArray();

            }else{
                $beginThismonth = mktime(0, 0, 0, date('m'), 1, date('Y'));
                $endThismonth = mktime(23, 59, 59, date('m'), date('t'), date('Y'));
                $map['sign_time'] = array('between', array($beginThismonth, $endThismonth));
                $map['vipid'] = $uid;
                $cache = $m->where($map)->field('is_zengsong,sign_time')->select()->toArray();
            }

            $today = 0;
            foreach($cache as $k => $v){
                $cache[$k]['sign_time'] = date('Y-m-d',$v['sign_time']);
                if(date('Y-m-d',$v['sign_time']) == date('Y-m-d',time())){
                    $today = 1;
                }
            }
            if (!$cache) {
                $info['status'] = 0;
            } else {
                $info['status'] = 1;
            }
            $info['signList'] = $cache;
            $info['today'] = $today;
            $continue = $m->where($map) -> order('id desc')->value('continue_sign');
            if($continue){
                $info['continue'] = $continue;
            }else{
                $info['continue'] = 0;
            }

            $this->success('成功',$info);
        }
    }

    //获取指定月份开始和结束的时间戳
    function mFristAndLast($y = "", $m = ""){
        if ($y == "") $y = date("Y");
        if ($m == "") $m = date("m");
        $m = sprintf("%02d", intval($m));
        $y = str_pad(intval($y), 4, "0", STR_PAD_RIGHT);

        $m>12 || $m<1 ? $m=1 : $m=$m;
        $firstday = strtotime($y . $m . "01000000");
        $firstdaystr = date("Y-m-01", $firstday);
        $lastday = strtotime(date('Y-m-d 23:59:59', strtotime("$firstdaystr +1 month -1 day")));

        return array(
            $firstday,
            $lastday
        );
    }

    public function sign_info()
    {
        $request = $this->request->param();
        $token = $request['token'];
        $data = db('user_token')->where(['token' => $token])->field('user_id')->find();
        $uid = $data['user_id'];
        if($uid) {
            $msign = db('sign_record');
            $signmap['vipid'] = $uid;
            $lastsign = $msign->where($signmap)->order('id desc')->find();

            if($lastsign){
                $tmp_continue_sign = $lastsign['continue_sign'];
                $lastsign['continue_sign']++;//签到加一

                $endsigntime = $lastsign['sign_time'];
            }else{
                $lastsign['continue_sign'] = 1;
                $endsigntime = 0;
            }

            $d1 = date_create(date('Y-m-d', $endsigntime));
            $d2 = date_create(date('Y-m-d', time()));
            $diff = date_diff($d1, $d2);
            $late = $diff->format("%a");
            $zsflag = false;
            //判断是否签到过
            if ($late < 1) {
                $this -> error('您今日已经签过到了！');
            }
            //正常签到累计流程
            $data_sign['sign_time'] = time();
            $data_sign['vipid'] = $uid;
            $data_sign['is_zengsong'] = 0;
            if ($late >= 1 && $late < 2) {
                $data_sign['continue_sign'] = $lastsign['continue_sign'];
                $zsflag = true;
            } else {
                //签到中断，中间隔了一天
                $data_sign['continue_sign'] = 1; //签到次数置1
                $zsflag = true;
            }
            $r = $msign->insert($data_sign);
            if (!$r) {
                $this -> error('签到失败！');
            }
            if ($zsflag) {
                Db::startTrans();
                try {
                    $score_setting = cmf_get_option('score_setting');
                    Db::name('user')->where(['id' => $uid])->setInc('credit_score', $score_setting['sign']);

                    // 积分日志
                    $socrelog['user_id'] = $uid;
                    $socrelog['score'] = $score_setting['sign'];
                    $socrelog['type'] = 1;
                    $socrelog['create_time'] = time();
                    db('user_score_log')->insert($socrelog);

                    //会员消息通知
                    $user_news['uid'] = $uid;
                    $user_news['time'] = time();
                    $user_news['news'] = '签到成功,奖励积分'.$score_setting['sign'].'分';
                    $user_news['type'] = 3;
                    $user_news['status'] = 0;

                    Db::name('news')->insert($user_news);

                    Db::commit();
                }catch(\Exception $e) {
                    Db::rollback();
                }

                $info['msg'] = '签到成功,奖励积分2分';
            }
            $info['status'] = 1;
            $info['num'] = $data_sign['continue_sign'];
            $this -> success($info['msg'],$info['num']);
        }
    }

    function testttt()
    {
        $sort = db('self_task_sort')->where('id','=',9)->find();
        $data['appname'] = db('self_task_sort')->where('id','=',$sort['pid'])->value('appname');
        dump($data);die;
    }
}
