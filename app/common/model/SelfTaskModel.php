<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 老猫 <thinkcmf@126.com>
// +----------------------------------------------------------------------
namespace app\common\model;

use api\home\model\NewsModel;
use app\admin\controller\DouyinUrlController;
use think\Exception;
use think\Model;
use think\Log;
use think\Db;
class SelfTaskModel extends Model
{

    static function getStatusInTask($user_id, $taskid, $tasknum, $puber, $tasklimitnum, $isindex = false)
    {
        // 用户id 任务id  任务总量  任务发布者  限制接取量
        $tasklimitnum = $tasklimitnum > $tasknum ? $tasknum : $tasklimitnum;   //  5

        $mytaked = db('self_task_receive')->where(array('userid' => $user_id, 'taskid' => $taskid))->select()->toArray();

        $comnum = 0;
        $time = time();
        //接过了
        if (!empty($mytaked)) {
            foreach ($mytaked as $k => $v) {
//                if ($v['status'] == 0 && $v['endtime'] < $time) {
//                    $re = db('self_task_receive')->where('id', $v['id'])->delete();
//                    if ($re) {
//                        db("self_task")->where('id',$taskid)->setInc('oldnum');
//                        db("self_task")->where('id',$taskid)->setField('isempty',0);
//                    }
//                }
                if ($v['status'] == 0 && $v['endtime'] > $time) {
                    return array('status' => 1, 'last' => 0, 'reply' => $v); // 抢了还没完成
                }
                if ($v['status'] >= 1 && $v['status'] !=3) {
                    $comnum++;
                }

            }
        } else {
            //没接过
            $res = self::timesLimit($user_id, $puber);

            if (!$res) return array('status' => 3, 'last' => 0); // 达到了此发布者的数量限制

            if (!$isindex) {
                $lastnum = self::isEmpty($taskid, $tasknum);

                if ($lastnum <= 0) {
                    db('self_task')->where('id','=',$taskid)->setField('oldnum',0);
                    return array('status' => 4, 'last' => 0); // 被抢完了
                }

            }
        }

        if ($comnum >= $tasklimitnum) return array('status' => 2, 'last' => 0); //抢的次数达到限制数量

        $setting = cmf_get_option('selftask_setting');


        $res = self::timesLimit($user_id, $puber);
        if (!$res) return array('status' => 3, 'last' => 0); // 达到了此发布者的数量限制

        $lastnum = $tasklimitnum - $comnum;

        if (!$isindex) {
            $lastnum = self::isEmpty($taskid, $tasknum);
            if ($lastnum <= 0) return array('status' => 4, 'last' => 0); // 被抢完了
        }

        return array('status' => 0, 'last' => $tasklimitnum - $comnum, 'totallast' => $lastnum);
    }

    //判断这个任务还有没次数
    static function isCanreceive($user_id, $taskid,$limitnum)
    {
        $mytakedsum = db('self_task_receive')->where(array('userid' => $user_id, 'taskid' => $taskid))->where('status','<>',3)->count('id');

        if ($mytakedsum < $limitnum){
            return true;
        }
        return false;
    }

    // 是不是已经被抢完了
    static function isEmpty($taskid, $tasknum)
    {
        $time = time();
        $taked = db('self_task_receive')->where('status','<>',3)->where(array('taskid' => $taskid, 'endtime' => array('gt',$time)))->count();
        return $tasknum - $taked;
    }


    static function timesLimit($user_id, $pubuid)
    {
        // 数量限制 需要先计算数量限制 不能判断是不是存在，因为可能是后台发布的
        $user = array();
        if (empty($pubuid)) {
            $pubuid = '';
        } else {
            $user = db('user')->where('id', $pubuid)->find();
        }

        $limit = 0;
        //抢一个商家的任务几天最多几次
        $setting = cmf_get_option('selftask_setting');
        if ($setting['permaxre'] > 0) $limit = $setting['permaxre'];
        if ($user) {
            if ($user['limitnum'] > 0) $limit = $user['limitnum'];
        }

        if ($limit > 0) {
            $today = strtotime(date('Y-m-d', time()));

            if ($setting['permaxday'] > 0) $today = time() - $setting['permaxday'] * 3600 * 24;
            if ($user) {
                if ($user['limitday'] > 0) $today = time() - $user['limitday'] * 3600 * 24;
            }
            $map['userid'] = $user_id;
            $map['pubuid'] = $pubuid;
            $map['createtime'] = array('egt', $today);
            $map['endtime'] = array('gt', time());
            $allre = db('self_task_receive')->field('id')->where($map)->group('taskid')->select();
            if (count($allre) >= $limit) return false; // 不能再抢此发布者的任务
        }
        return true;
    }

    static function hideKey($hidetxt, $content)
    {

        if (!empty($hidetxt)) {
            $list = preg_split("/,|，/", trim($hidetxt,',，'));
            foreach($list as $find){
                $content = str_replace($find,'*',$content);
            }
        }

        return $content;
    }

    static function isCanPub($data)
    {
        if ($data['title'] == '') return '您还没填写标题';
        if ($data['content'] == '') return '您还没填写任务内容';
        if ($data['num'] <= 0) return '任务总量不能小于等于0';
        if ($data['money'] <= 0) return '任务赏金不能小于等于0';
        if ($data['limitnum'] < 0) return '限制回复不能小于0';
        return true;
    }

    static function pubGiveParent($setting, $userinfo, $taskid, $upmoney)
    {
        try {
            $fxyj = $upmoney * $setting['pcgive'] / 100;
            if ($fxyj < 0.01) {
                return true;
            }
            if ($userinfo['pid'] == 0) {
                return true;
            }

            $parent = db('user')->where('id', $userinfo['pid'])->find();
            if (!$parent) {
                return true;
            }
            //发佣金
            db('user')->where('id', $parent['id'])->setInc('income', $fxyj);
            $newIncome = db('user')->where('id', $userinfo['pid'])->value('income');
            //写余额变更日志
            //写入变更日志
            $moneylog['user_id'] = $parent['id'];
            $moneylog['tid'] = $taskid;
            $moneylog['rid'] = 0;
            $moneylog['agentId'] = $parent['agentId'];
            $moneylog['create_time'] = time();
            $moneylog['coin'] = $fxyj;//更改金额
            $moneylog['notes'] = '用户' . $userinfo['id'] . '发任务' . $taskid . ',向上级返佣';
            $moneylog['channel'] = 32;//下级下下级做任务
            $moneylog['income'] = $newIncome; // 变更后的收入
            $res = db('user_money_log')->insert($moneylog);
            //写佣金日志
            $yonglog['order_id'] = $taskid;
            $yonglog['yong_type'] = 0;//分销
            $yonglog['user_id'] = $parent['id'];
            $yonglog['user_login'] = $parent['user_login'];
            $yonglog['sup_id'] = $userinfo['id'];
            $yonglog['sup_login'] = $userinfo['user_login'];
            $yonglog['fxprice'] = $upmoney;
            $yonglog['fxyj'] = $fxyj;
            $yonglog['create_time'] = time();
            $yonglog['notes'] = '来自' . $userinfo['id'] . '发布任务ID' . $taskid . '返佣';
            $yonglog['channel'] = 4; //商城消费
            db('user_yong_log')->insert($yonglog);
        } catch (\Exception $e) {
            file_put_contents('pubgiveparent_err.txt', $e->getMessage() . PHP_EOL, FILE_APPEND);
        }
    }


    static function ipLimit($maxip)
    {
        if ($maxip > 0) {
            $ip = getIp();
            $today = strtotime(date('Y-m-d', time()));
            $ipnum = db('self_task_receive')->where(array('createtime' => array('gt', $today), 'ip' => $ip))->count();
            if ($ipnum >= $maxip) return true;
        }
        return false;
    }

    // 采纳任务
    static function agreeTask($set, $taked ,$firstid,$task)
    {
        try {
            Db::startTrans();
            $counting = \TbUtil::getCache('counttaked', $taked['id']);
            if (is_array($counting) && $counting['status'] == 1 && $counting['time'] > (time() - 60)) {
                throw new Exception("判断缓存数据");
            }
            \TbUtil::setCache('counttaked', $taked['id'], array('status' => 1, 'time' => time()));

            $replyer = db('user')->find($taked['userid']);

            //回复者平台使用费
            $server = 0;
            if ($set['replyserver'] > 0) {
                $server = round($taked['money'] * $set['replyserver'] / 100, 2);
                if ($server < 0.01) $server = 0;
            }
            //给被采纳的人发钱
            $res = db('user')->where('id', $taked['userid'])->setInc('income', $taked['money']);

            $score_setting = cmf_get_option('score_setting');
            if ($taked['id'] == $firstid) {
                db('user')->where('id', $taked['userid'])->setInc('credit_score', $score_setting['first']);
                $socrelog['user_id'] = $taked['userid'];
                $socrelog['score'] = $score_setting['first'];
                $socrelog['type'] = 1; //增加
                $socrelog['create_time'] = time();
                db('user_score_log')->insert($socrelog);
            }

            if (!$res) {
                throw new Exception("给被采纳的人发钱-UserID:" . $taked['userid'] . "-" . json_encode($taked));
            }


            $newIncome = db('user')->where('id', $taked['userid'])->value('income');

            //写入变更日志
            $moneylog['user_id'] = $replyer['id'];
            $moneylog['rid'] = $taked['id'];
            $moneylog['create_time'] = time();
            $moneylog['coin'] = $taked['money'];//更改金额
            $moneylog['notes'] = '自发系统接的任务ID' . $taked['taskid'] . '被雇主采纳';
            $moneylog['income'] = $newIncome;
            $moneylog['channel'] = 31;//做任务

            $res = db('user_money_log')->insertGetId($moneylog);
            if (!$res) {
                throw new Exception("写入变更日志失败:" . json_encode($moneylog));
            }

            // 扣服务费
            if ($server > 0) {
                $res = db('user')->where('id', $taked['userid'])->setDec('income', $server);
                $newMoney = db('user')->where('id', $taked['userid'])->value('income');
                if (!$res) {
                    throw new Exception("扣服务费:" . $taked['userid'] . "-" . $server);
                }
                //写入变更日志
                $moneylog['user_id'] = $replyer['id'];
                $moneylog['tid'] = $taked['taskid'];
                $moneylog['rid'] = $taked['id'];
                $moneylog['create_time'] = time();
                $moneylog['coin'] =  "-".$server;
                $moneylog['notes'] = '自发系统接的任务ID' . $taked['taskid'] . '被雇主采纳,扣除平台服务费';
                $moneylog['channel'] = 35;//扣服务费
                $moneylog['income'] = $newMoney;

                $res = db('user_money_log')->insertGetId($moneylog);

                if (!$res) {
                    throw new Exception("写入变更日志失败:" . json_encode($moneylog));
                }
            }

            // 上级奖励  (有上级并且设置上级奖励大于0)
            if ($replyer['pid'] > 0 && $set['commongive'] > 0) {
                $upmoney = 0;
                $twoupmoney=0;
                $parent = db('user')->where('id', $replyer['pid'])->find();
                $upmoney = $taked['money'] * $set['commongive'] / 100;//任务完成给上级
                if ($upmoney >= 0.01) {
                    //改余额
                    $res1 = db('user')->where('id', $parent['id'])->setInc('income', $upmoney);
                    $newIncome = db('user')->where('id', $parent['id'])->value('income');
                    if (!$res1) {
                        throw new Exception("上级奖励改余额失败:" . $parent['id'] . "-" . $upmoney);
                    }
                    //写余额日志
                    $moneylog['user_id'] = $parent['id'];
                    $moneylog['tid'] = $taked['sid'];
                    $moneylog['rid'] = $taked['id'];
                    $moneylog['agentId'] = $parent['agentId'];
                    $moneylog['create_time'] = time();
                    $moneylog['coin'] = $upmoney;
                    $moneylog['income'] = $newIncome;
                    $moneylog['notes'] = '来自' . $taked['userid'] . '完成任务ID' . $taked['sid'] . '反佣';
                    $moneylog['channel'] = 32;//任务佣金
                    $res2 = db('user_money_log')->insert($moneylog);

                    if (!$res2) {
                        throw new Exception("写余额日志:" . json_encode($moneylog));
                    }
                    //写佣金日志
                    $yonglog['order_id'] = $taked['id'];
                    $yonglog['yong_type'] = 0;//分销
                    $yonglog['user_id'] = $parent['id'];
                    $yonglog['user_login'] = $parent['user_login'];
                    $yonglog['sup_id'] = $replyer['id'];
                    $yonglog['sup_login'] = $replyer['user_login'];
                    $yonglog['fxprice'] = $taked['money'];
                    $yonglog['fxyj'] = $upmoney;
                    $yonglog['create_time'] = time();
                    $yonglog['notes'] = '来自' . $taked['userid'] . '完成任务ID' . $taked['sid'] . '返佣';
                    $yonglog['channel'] = 4; // 4任务返佣
                    $res3 = db('user_yong_log')->insert($yonglog);

                    if (!$res3) {
                        throw new Exception("写佣金日志:" . json_encode($yonglog));
                    }
                }
                // 如果上级存在上级 给上级的上级奖励
                if ($parent['pid'] > 0 && $set['commontwogive'] > 0){

                    //查到上级的上级
                    $p_parent = db('user')->where('id', $parent['pid'])->find();
                    $twoupmoney = $taked['money'] * $set['commontwogive'] / 100;//任务完成给上级
                    if ($twoupmoney >= 0.01){
                        //改余额
                        $res4 = db('user')->where('id', $p_parent['id'])->setInc('income', $twoupmoney);
                        if (!$res4) {
                            throw new Exception("上上级奖励改余额失败:" . $parent['id'] . "-" . $twoupmoney);
                        }
                        $newIncomedata = db('user')->where('id', $p_parent['id'])->value('income');
                        //写余额日志
                        $moneylog['user_id'] = $p_parent['id'];
                        $moneylog['tid'] = $taked['sid'];
                        $moneylog['rid'] = $taked['id'];
                        $moneylog['agentId'] = $p_parent['agentId'];
                        $moneylog['create_time'] = time();
                        $moneylog['coin'] = $twoupmoney;
                        $moneylog['notes'] = '来自' . $taked['userid'] . '完成任务ID' . $taked['sid'] . '反佣';
                        $moneylog['channel'] = 32;//任务佣金
                        $moneylog['income'] = $newIncomedata;
                        $res5 = db('user_money_log')->insert($moneylog);
                        if (!$res5) {
                            throw new Exception("写余额日志:" . json_encode($moneylog));
                        }
                        //写佣金日志
                        $yonglog['order_id'] = $taked['id'];
                        $yonglog['yong_type'] = 0;//分销
                        $yonglog['user_id'] = $p_parent['id'];
                        $yonglog['user_login'] = $p_parent['user_login'];
                        $yonglog['sup_id'] = $parent['id'];
                        $yonglog['sup_login'] = $parent['user_login'];
                        $yonglog['fxprice'] = $taked['money'];
                        $yonglog['fxyj'] = $upmoney;
                        $yonglog['create_time'] = time();
                        $yonglog['notes'] = '来自' . $taked['userid'] . '完成任务ID' . $taked['sid'] . '返佣';
                        $yonglog['channel'] = 4; //4任务返佣
                        $res6 = db('user_yong_log')->insert($yonglog);
                        if (!$res6) {
                            throw new Exception("写佣金日志:" . json_encode($yonglog));
                        }
                    }
                }

                //连续奖励
                $iscanewai = 0; // 默认不给额外奖励
                $where = array('continueid' => $taked['continueid'], 'userid' => $taked['userid'], 'ewai' => array('gt', 0.01));
                $payednum = db('self_task_receive')->where($where)->count();

                if ($taked['continue'] == 1 && $payednum <= 0) {
                    $continue = db('self_task_continue')->where(array('id' => $taked['continueid'], 'isback' => 0))->find();
                    if (!empty($continue)) {
                        $alltask = db('self_task')->where(array('continueid' => $taked['continueid']))->select()->toArray();
                        if (is_array($alltask)) {
                            $totalewai = 0;
                            $trueewai = 0;
                            foreach ($alltask as $v) {
                                if ($v['id'] != $taked['sid']) { // 只计算连续发布的其他任务
                                    $totalewai++;
                                    $thisreply = db('self_task_receive')->where(array('status' => 2, 'userid' => $taked['userid'], 'taskid' => $v['id']))->find();
                                    if (!empty($thisreply) && $thisreply['ewai'] <= 0) {
                                        $trueewai++;
                                    }
                                }
                            }
                        }
                        if ($totalewai == $trueewai && $totalewai > 0) $iscanewai = 1;
                    }
                }
                $ewai = 0;
                if ($iscanewai == 1) {
                    $ewai = $continue['money'];
                    if ($ewai >= 0.01) {
                        $res = db('user')->where('id', $taked['userid'])->setInc('income', $ewai);
                        $newMoney = db('user')->where('id', $taked['userid'])->value('income');
                        if (!$res) {
                            throw new Exception("error:iscanewai:" . $ewai);
                        }
                        //写入变更日志
                        $moneylog['user_id'] = $replyer['id'];
                        $moneylog['tid'] = $taked['sid'];
                        $moneylog['rid'] = $taked['id'];
                        $moneylog['agentId'] = $replyer['agentId'];
                        $moneylog['create_time'] = time();
                        $moneylog['coin'] = $ewai;//更改金额
                        $moneylog['income'] = $newMoney;
                        $moneylog['notes'] = '自发系统接的任务ID' . $taked['sid'] . '被雇主采纳,额外奖励';
                        $moneylog['channel'] = 31;//自发任务系统
                        $moneylog['integral'] = $replyer['integral'];//积分值
                        $res7 = db('user_money_log')->insert($moneylog);
                        if (!$res7) {
                            throw new Exception("写入变更日志:" . json_encode($moneylog));
                        }
                    }
                }

                // 更新回复
                $update = array('dealtime' => time(), 'server' => $server, 'status' => 2, 'giveparent' => $upmoney,'giveparentup'=>$twoupmoney, 'ewai' => $ewai);

                db('self_task_receive')->where(array('id' => $taked['id']))->update($update);
                // 增加发布数量
                if ($taked['pubuid'] > 0){
                    db('user')->where('id', $taked['pubuid'])->setInc('pubnumber');

                    // 增加采纳数量
                    db('user')->where('id', $taked['pubuid'])->setInc('acceptnumber');
                }
                // 增加完成数量
                db('user')->where('id', $replyer['id'])->setInc('replynumber');

                db('user')->where('id', $replyer['id'])->setInc('acceptednumber');

                \TbUtil::deleteCache('counttaked', $taked['id']);
                if ($taked['sortid'] == 9){
                    if ($taked['orderid'] && $taked['order_aa']) self::clNum($taked);
                }

                Db::commit();
                return 'bufenyongwancheng';



            } else {

                // 更新回复
                $update = array('dealtime' => time(), 'server' => $server, 'status' => 2);
                db('self_task_receive')->where(array('id' => $taked['id']))->update($update);

                // 增加发布、采纳数量
                db('user')->where('id', $taked['pubuid'])->setInc('pubnumber');
                db('user')->where('id', $taked['pubuid'])->setInc('acceptnumber');

                // 增加完成数量
                db('user')->where('id', $replyer['id'])->setInc('replynumber');
                db('user')->where('id', $replyer['id'])->setInc('acceptednumber');


                \TbUtil::deleteCache('counttaked', $taked['id']);

                if ($taked['sortid'] == 9){
                    if ($taked['orderid'] && $taked['order_aa']) self::clNum($taked);
                }
                Db::commit();
                return 'quanbuwancheng';



            }
        } catch (Exception $e) {
            Db::rollback();
            Log::write("Exception:" . $e->getMessage());
            throw new Exception($e->getMessage());
            return false;
        }
        finally {
            \TbUtil::deleteCache('counttaked', $taked['id']);
        }
        return false;
    }

    // 从林回传执行
    static function clNum($taked)
    {
        $taskInfo = db('self_task')->where('orderid', $taked['orderid'])->find();
        if (!$taskInfo) throw new Exception("任务不存在" );

//        $num = db('self_task_receive')->where('orderid', $taked['orderid'])->where('order_aa', $taked['order_aa'])->where('status', 2)->count();
//
//        $start_num = db('self_task')->where(['orderid' => $taked['orderid']])->value('start_num');
//        $num = $num + $start_num;

        if (strlen($taked['order_aa']) == 19){
            $nowdata = DouyinUrlController::RmD($taked['order_aa']);
        }

        if (strlen($taked['order_aa']) > 19){
            $aa = DouyinUrlController::aa($taked['order_aa']);
            $nowdata = DouyinUrlController::RmD($aa);
        }

        $nowdata = json_decode($nowdata,true);
        $num = $nowdata['zan'];

        // $url = "http://by.conglin178.com/admin_jiuwuxiaohun.php?m=home&c=api&a=edit&goods_id=" . $taskInfo['account_id'] . "&order_state=jxz&order_id=" . $taked['orderid'] . "&now_num=" . $num . "&apikey=" . $taskInfo['api_key'];

        $url = "http://by.conglin178.com/admin_jiuwuxiaohun.php?m=home&c=api&a=edit&goods_id=" . $taskInfo['account_id'] . "&order_state=jxz&order_id=" . $taskInfo['orderid'] . "&apikey=" . $taskInfo['api_key'] . "&now_num=" . $num;

        $clurllog['orderid'] =$taked['orderid'];
        $clurllog['order_aa'] = $taked['order_aa'];
        $clurllog['num'] = $num;
        $clurllog['url'] = $url;
        $clurllog['create_time'] = time();
        db('cl_url_log')->insert($clurllog);

        self::http_curl($url);
    }


    static function http_curl($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $output = curl_exec($ch);
        curl_close($ch);
        return json_decode($output, true);
    }

    /**
     * 用户审核任务
     */
    static function userAgree($dealstatus, $receive,$dealnote='')
    {
        $data = [
            'taskid' => $receive['taskid'],  //任务id
            'userid' => $receive['userid'],  //回复人id
            'replyid' => $receive['id'],  // 回复id
            'dealtime' => time(),
            'status' => $dealstatus,
            'dealnote' => $dealnote
        ];
        $res = db('self_task_receive_useragree')->insert($data);
        if ($res)
            return true;
        else
            return false;
    }


    // 拒绝回复
    static function refuseTask($taked, $reason, $task, $pubernick)
    {
        // 更新回复
        $update = array('dealtime' => time(), 'status' => 3, 'reason' => $reason);
        $res = db('self_task_receive')->where(array('id' => $taked['id']))->update($update);

        // 增加发布数量
        $puber = db('user')->where('id', $taked['pubuid'])->find();
        db('user')->where('id', $puber['id'])->setInc('pubnumber');

        // 增加回复数量
        $replyer = db('user')->where('id', $taked['userid'])->find();
        db('user')->where('id', $replyer['id'])->setInc('replynumber');

        db('self_task_scan')->where('id', 1)->setInc('pubed');
        db('self_task_scan')->where('id', 1)->setInc('commpubed');

        // 任务被拒绝扣除信誉积分
        $score_setting = cmf_get_option('score_setting');
        db('user')->where('id',$taked['userid'])->setDec('credit_score',$score_setting['notpass']);

        // 积分日志
        $socrelog['user_id'] = $taked['userid'];
        $socrelog['score'] = $score_setting['notpass'];
        $socrelog['type'] = 2; //减少
        $socrelog['create_time'] = time();
        db('user_score_log')->insert($socrelog);

        //给回复者发消息
        $model_news = new NewsModel();
        $news = "雇主{$puber['user_nickname']}拒绝了您的回复";
        $model_news::toUserNews($replyer['id'], $news);//后面需要改，可以查看是哪个任务，哪个回复
        return $res;
    }

    // 结算任务
    static function countTask($task, $backserver = false)
    {
        // 先采纳未处理的回复
        parent::startTrans();
        try {

            $taked = db('self_task_receive')->alias('a')
                ->join('self_task b', 'b.id = a.taskid')
                ->where(array('a.status' => 1, 'a.taskid' => $task['id']))
                ->field("a.*,b.continueid,b.continue ,b.id AS sid,a.status as sstatus,b.start,b.end,a.taskid,b.sortid")
                ->select()
                ->toArray();

            $setting = cmf_get_option('selftask_setting');
            if (is_array($taked)) {
                foreach ($taked as $v) {
                    self::agreeTask($setting, $v,'', $task);
                }
            }

            // 判断是否填写抖音视频id
            if (!empty($task['order_aa'])){
                $numurl = 'https://www.iesdouyin.com/web/api/v2/aweme/iteminfo/?item_ids=' . $task['order_aa'];
                $numdata = self::http_curl($numurl);
                $end_num = 0;
                if (!empty($numdata['item_list'])){
                    $end_num = $numdata['item_list'][0]['statistics']['digg_count'];  // 视频当前的点赞量
                }
                db('self_task')->where('id',$task['id'])->setField('end_num',$end_num);
            }

            // 退钱
            $backmoney = 0;
            $payed = 0;
//            $allagree = db('self_task_receive')->where(array('status' => 2, 'taskid' => $task['id']))->field(array('money'))->select()->toArray();
//            if (is_array($allagree)) {
//                foreach ($allagree as $v) {
//                    $payed += $v['money'];
//                }
//            }
            $allagree = db('self_task_receive')->where(array('status' => 2, 'taskid' => $task['id']))->count();

            $payed = sprintf("%.2f",$allagree * $task['falsemoney']);
//            $backmoney = $task['num'] * $task['money'] - $payed;
            $backmoney = $task['num'] * $task['falsemoney'] - $payed;
            // 计算退还额外奖励
            $diff = 0;
            if ($task['continue'] == 1) {
                $where = array('continueid' => $task['continueid'], 'iscount' => 0);
                $lasttask = db('self_task')->where($where)->where(array('id' => array('neq', $task['id'])))->count();
                if ($lasttask <= 0) { // 是最后一个了 退还剩余额外奖励
                    $continue = db('self_task_continue')->where(array('id' => $task['continueid'], 'isback' => 0))->find();
                    if (!empty($continue) && $continue['backmoney'] <= 0) { // 表示还没有退还
                        $diff = $continue['totalmoney'] - $continue['prizemoney'];
                        $backmoney += $diff;
                    }
                }
            }
            $backserver = cmf_get_option('selftask_setting');
            if ($backserver['isbacktm'] == 1) {
                $backmoney = $backmoney + $task['costserver'] + $task['costka'] + $task['costtop'];
            }

            $backmoney = round($backmoney, 2);
            $puber = db('user')->where('id', $task['userid'])->find();
            if ($backmoney > 0) {
                $res = db('user')->where('id', $task['userid'])->setInc('user_money', $backmoney);
                $newMoneydata = db('user')->where('id', $task['userid'])->value('user_money');
                //写入变更日志
                $moneylog['user_id'] = $task['userid'];
                $moneylog['tid'] = $task['id'];
                $moneylog['rid'] = 0;
                $moneylog['agentId'] = $puber['agentId'];
                $moneylog['create_time'] = time();
                $moneylog['coin'] = $backmoney;//更改金额
                $moneylog['user_money'] = $newMoneydata;
                $moneylog['notes'] = '自发系统接任务ID' . $task['id'] . '被管理员驳回,退还额还奖励';
                $moneylog['channel'] = 14;//自发任务系统
                db('user_money_log')->insert($moneylog);
            }
            if ($diff > 0) {
                db('self_task_continue')->where(array('id' => $continue['id']))->setField(array('backmoney' => $diff, 'isback' => 1));
            }

            // 更新任务
            $update = array('counttime' => time(), 'iscount' => 1, 'backmoney' => $backmoney);
            $res = db('self_task')->where(array('id' => $task['id']))->update($update);
            if (!$update) {
                parent::rollback();
            }
        } catch (\Exception $e) {
            parent::rollback();
            return false;
        }
        parent::commit();
        // 结算通知 (后面再写用户通知)
        if($task['userid']>0){
            $model_news = new NewsModel();
            $news = "你的任务，ID{$task['id']},{$task['title']},已被结算";
            $model_news::toUserNews($task['userid'], $news);//后面需要改，可以查看是哪个任务，哪个回复
        }
        return $res;
    }

    // 删除任务图片
    static function deleteTaskImg($id, $type)
    {
        return true;
        set_time_limit(0);
//        try{
//            if (is_array($id)) {
//                $task = $id;
//            } else {
//                $task = db('self_task')->where(array('id' => $id))->find();
//            }
//
//            if (empty($task)) return false;
//
//            $images = json_decode($task['images'], true);
//            if (!empty($images)) {
//                foreach ($images as $v) {
//                    \TbUtil::deleteImage($v);
//                }
//            }
//
//            $taked = db('self_task_receive')->where(array('taskid' => $task['id']))->select()->toArray();
//
//            if (!empty($taked)) {
//                foreach ($taked as $v) {
//                    $images = json_decode($v['images'], true);
//                    if (!empty($images)) {
//                        foreach ($images as $vv) {
//                            \TbUtil::deleteImage($vv);
//                        }
//                    }
//
//                    // 补充内容
//                    $addlist = db('self_task_remindlog')->where(array('takedid' => $v['id']))->select()->toArray();
//                    if (!empty($addlist)) {
//                        foreach ($addlist as $vv) {
//                            if (!empty($vv['images'])) $images = json_decode($vv['images'], true);
//                            if (!empty($images) && is_array($images)) {
//                                foreach ($images as $vvv) {
//                                    \TbUtil::deleteImage($vvv);
//                                }
//                            }
//
//                        }
//                    }
//
//                }
//            }
//            return true;
//        }catch(\Exception $e){
//            echo $e->getMessage();exit;
//        }
    }
}