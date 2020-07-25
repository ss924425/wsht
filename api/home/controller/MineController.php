<?php

namespace api\home\controller;

use app\common\model\UserModel;
use think\Db;
use cmf\controller\RestBaseController;
use think\Exception;
use think\Log;
use think\Validate;

class MineController extends RestBaseController
{

    //用户详情页面
    public function user_details()
    {
        try {
            $uid = $this->getUserId();
            if (empty($uid)) $this->error(['code' => 10001, 'msg' => $this->standard_code['10001']]);
            $user = $this->getUserInfo();
            if ($user['vip_type'] == 1) {
                $user['name_type'] = '普通会员';
            } else if ($user['vip_type'] == 2) {
                $user['name_type'] = 'VIP会员';
            } else if ($user['vip_type'] == 3) {
                $user['name_type'] = '代理商';
            } else if ($user['vip_type'] == 4) {
                $user['name_type'] = '股东';
            }
            $user['vip_end_time'] = $user['vip_end_time'] > time() ? date('Y.m.d', $user['vip_end_time']) : 0;
            $user['money'] = round($user['user_money'] + $user['yong_money'], 2);
            $user['income'] = round($user['income'], 2);
            $user['deposit'] = round($user['deposit'], 2);
            $model_user = new UserModel();
            $mydata = $model_user::getMyData($uid);
            $user['mydata'] = $mydata;
            $user['isLast'] = 1;//0：过期，1：未过期
            Log::info('user_details-success-' . json_encode($user));

            $addscore = db('user_score_log')->where('user_id',$uid)->where('type','=',1)->sum('score');
            $decscore = db('user_score_log')->where('user_id',$uid)->where('type','=',2)->sum('score');
            $user['addscore'] = empty($addscore) ? 0 : $addscore;
            $user['decscore'] = empty($decscore) ? 0 : $decscore;

            $this->success('成功', $user);
        } catch (Exception $ex) {
            Log::error('user_details-Exception-' . $ex);
            $this->error('系统异常，请退出重试！');
        }
    }


    /**
     * 账户余额
     */
    public function balance()
    {
        $request = $this->request->param();
        if (empty($request)) {
            $this->error('失败');
            exit;
        }
        $token = $request['token'];

        if (isset($request['p'])) {
            $p = $request['p'];
        } else {
            $p = 1;
        }
        $data = db('user_token')->where(['token' => $token])->field('user_id')->find();
        $uid = $data['user_id'];
        if ($uid > 0) {

//            $list1 = db('user_money_log') -> where(['user_id' => $uid]) -> select() -> toArray();

            $list1 = db('user_money_log')->alias('u')
                ->join('mc_task_receive r', 'u.rid = r.id', 'left')
                ->join('mc_task_branch b', 'r.bid = b.id', 'left')
                ->join('mc_task t', 't.id = b.tid', 'left')
                ->field('u.id,u.coin,u.create_time,u.channel,u.integral,u.notes,t.title,b.b_title,t.thumb')
                ->where(['u.user_id' => $uid, 'u.type' => 0])
                ->order('u.id', 'desc')
                ->limit(10)->page($p)
                ->select()->toarray();
            foreach ($list1 as $k => $v) {
                $list1[$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
                if ($v['channel'] == 0) {
                    if ($v['title'] == null) {
//                        unset($list1[$k]);
                        $list1[$k]['channel'] = 1;
                    }
                }
            }
//            $list1 = array_values($list1);

            $list2 = db('user_yong_log')->alias('y')
                ->join('user u', 'u.id = y.sup_id')
                ->field('y.sup_login,y.fxprice,y.fxyj,y.create_time,y.type,y.yong_type,u.avatar,u.vip_type,u.id')
                ->where(['y.user_id' => $uid])
                ->order('id', 'desc')
                ->limit(10)->page($p)
                ->select()->toArray();
            foreach ($list2 as $k => $v) {
                $list2[$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
                if ($v['type'] == 0) {
                    $list2[$k]['type'] == '收入';
                } elseif ($v['type'] == 1) {
                    $list2[$k]['type'] == '支出';
                }
                if ($v['vip_type'] == 1) {
                    $list2[$k]['vip_type'] = '普通会员';
                } elseif ($v['vip_type'] == 2) {
                    $list2[$k]['vip_type'] = 'VIP会员';
                } elseif ($v['vip_type'] == 3) {
                    $list2[$k]['vip_type'] = '代理商';
                } elseif ($v['vip_type'] == 4) {
                    $list2[$k]['vip_type'] = '股东';
                }
            }

            $this->success('请求成功', ['list1' => $list1, 'list2' => $list2]);
        } else {
            $err = ['code' => -1, 'msg' => 'token无效!'];
            $this->error($err);
        }
    }

    /**
     * 任务查看
     */
    public function taskManage()
    {
        $request = $this->request->param();
        if (empty($request)) {
            $this->error('失败');
            exit;
        }
        $token = $request['token'];
        $type = $request['type'];
        $p = $request['p'];
        if (empty($p)) {
            $p = 1;
        }

        $data = db('user_token')->where(['token' => $token])->field('user_id')->find();
        $uid = $data['user_id'];
        if ($uid > 0) {
            if ($type == 0) {
                //进行中任务
                $where = ['r.uid' => $uid, 'r.receive_type' => 0, 'r.receive_status' => 0];
            } elseif ($type == 1) {
                //待审核任务
                $where = ['r.uid' => $uid, 'r.receive_type' => 2, 'r.receive_status' => 0];

            } elseif ($type == 2) {
                //已通过审核
                $where = ['r.uid' => $uid, 'r.receive_type' => 3, 'r.receive_status' => 1];

            } elseif ($type == 3) {
                //未通过审核
                $where = ['r.uid' => $uid, 'r.receive_type' => 1, 'r.receive_status' => 2];
            } else {
                $this->error('请求错误');
            }

            $list = db('task_receive')->alias('r')
                ->join('mc_task_branch b', 'r.bid = b.id')
                ->join('mc_task t', 'b.tid = t.id')
//                -> field('r.id,r.receive_time,b.b_title,t.title,b.b_money,t.thumb,r.handle_notes,b.id as bid,r.submit,t.recharge')
                ->field('t.id,r.receive_time,t.title,t.thumb,r.handle_notes,r.submit,b.b_money,t.recharge')
                ->where($where)
                ->order('id', 'desc')
                ->limit(10)->page($p)
                ->select()->toarray();
            foreach ($list as $k => $v) {
                $list[$k]['receive_time'] = date('Y-m-d H:i:s', $v['receive_time']);
            }

            $this->success('请求成功', $list);
        } else {
            $err = ['code' => -1, 'msg' => 'token无效!'];
            $this->error($err);
        }
    }

    /**
     * 任务详情
     */
    public function taskDetails()
    {
        $request = $this->request->param();
        $token = $request['token'];
        $data = db('user_token')->where(['token' => $token])->field('user_id')->find();
        $uid = $data['user_id'];
        if ($uid > 0) {
            $request = $this->request;
            if ($this->request->isPost()) {
                $id = $request->param('id');
                $task = db('task_branch')->alias('b')
                    ->join('mc_task t', 'b.tid = t.id')
                    ->where(['b.id' => $id])
                    ->field('game_id,thumb,title,com_num,rel_num,garea,platform,task_link,b_money,b_title,b.id,b_validate,b_remark,b_end_time')
                    ->find();

                $task['end'] = $end = strtotime($task['b_end_time']);
                $task['now'] = $now = time();
                $diff = $end - $now;
                if ($diff < 0) {
                    //小时
                    $task['h'] = 0;
                    //分钟
                    $task['m'] = 0;
                    //秒
                    $task['s'] = 0;
                } else {
                    //小时
                    $task['h'] = floor($diff / 3600);
                    //分钟
                    $task['m'] = floor($diff % 3600 / 60);
                    //秒
                    $task['s'] = floor($diff % 3600 % 60);
                }

                $receive = db('task_receive')->alias('r')
                    ->join('mc_user u', 'u.id = r.uid')
                    ->field('r.*,u.user_login,u.avatar')
                    ->where(['r.bid' => $id, 'r.uid' => $uid])->find();

                //用户与任务的关系
                if (empty($receive)) {
                    $task['user_task'] = -1;
                    $task['ut_id'] = 0;
                } else {
                    $task['user_task'] = $receive['receive_type'];
                    $task['ut_id'] = $receive['id'];
                }
                $this->success('请求成功', [
                    'rec' => $receive,
                    'list' => $task
                ]);
            }
        } else {
            $err = ['code' => -1, 'msg' => 'token无效!'];
            $this->error($err);
        }
    }

    //消息通知页面
    public function message()
    {
        $request = $this->request->param();

        $p = $request['p'];
        if (empty($p)) {
            $p = 1;
        }

        $token = $request['token'];
        $data = db('user_token')->where(['token' => $token])->field('user_id')->find();
        $uid = $data['user_id'];
        if ($uid > 0) {
            $list = db('news')->alias('n')
                ->join('mc_task_branch b', 'n.bid = b.id', 'left')
                ->join('mc_task t', 'b.tid = t.id', 'left')
                ->field('n.id,n.time,n.news,n.type,n.status,t.title,b.b_title')
                ->where(['n.uid' => $uid])
                ->order('id desc')
                ->limit(10)->page($p)
                ->select()->toarray();
            foreach ($list as $k => $v) {
                $list[$k]['time'] = date('Y-m-d H:i:s', $v['time']);
            }
            $this->success('请求成功', $list);
        } else {
            $err = ['code' => -1, 'msg' => 'token无效!'];
            $this->error($err);
        }
    }

    //消息角标
    public function Corner_mark()
    {
        $request = $this->request->param();
        $token = $request['token'];
        $data = db('user_token')->where(['token' => $token])->field('user_id')->find();
        $uid = $data['user_id'];
        if ($uid > 0) {
            $quantity = db('news')->where(['uid' => $uid, 'status' => 0])->count();
            if ($quantity) {
                $this->success('搜索成功', ['quantity' => $quantity]);
            } else {
                $this->error('搜索失败', ['quantity' => 0]);
            }
        } else {
            $err = ['code' => -1, 'msg' => 'token无效!'];
            $this->error($err);
        }
    }


    //消息详情页
    public function messageDetails()
    {
        $request = $this->request->param();
        if (empty($request['id'])) {
            $this->error('缺少参数id');
            exit;
        }
        $token = $request['token'];
        $data = db('user_token')->where(['token' => $token])->field('user_id')->find();
        $uid = $data['user_id'];
        $news_id = $request['id'];
        if ($uid > 0) {
            if ($news_id > 0) {
                //修改消息为已读状态
                db('news')->where(['id' => $news_id])->update(['status' => 1]);

                $news = db('news')->alias('n')
                    ->join('mc_task_branch b', 'n.bid = b.id', 'left')
                    ->join('mc_task t', 'b.tid = t.id', 'left')
                    ->field('n.id,n.time,n.news,n.type,n.status,t.title,b.b_title,t.thumb')
                    ->where(['n.id' => $news_id])->find();

                if (!empty($news)) {
                    $news['date'] = date('Y-m-d', $news['time']);
                    unset($news['time']);
                }
                $this->success('请求成功', $news);
            } else {
                $this->error('请求失败');
            }
        } else {
            $err = ['code' => -1, 'msg' => 'token无效!'];
            $this->error($err);
        }
    }

    /**
     * 消息列表(后台发布的消息)
     */
    public function notice()
    {
        $request = $this->request->param();
        $p = isset($request['p']) && $request['p'] ? $request['p'] : 1;
        $pagesize = 10;
        $start = ($p - 1) * 10;
        $list = Db::name('notice')
            ->where('issend',1)
            ->limit($start,$pagesize)
            ->order('create_time desc')
            ->select()->toArray();
        foreach ($list as $k=>$v){
            $list[$k]['content'] = htmlspecialchars_decode($v['content']);
        }
        if (!empty($list)){
            $this->success('请求成功',$list);
        } else {
            $this->error('暂无数据');
        }
    }

    public function readNotic()
    {
        $id = $this->request->param('id');
        $res = Db::name('notice')->where(['id'=>$id])->update(['status'=>1]);
        if ($res)
        {
            $this->success('请求成功');
        } else {
            $this->error('失败');
        }
    }

    //提现页面
    public function cash()
    {
        $request = $this->request->param();
        $token = $request['token'];

        $p = $request['p'];
        if (empty($p)) {
            $p = 1;
        }

        $data = db('user_token')->where(['token' => $token])->field('user_id')->find();
        $uid = $data['user_id'];
        if ($uid > 0) {
            $user_setting = cmf_get_option('user_setting');
            $list = db('cash')->where(['uid' => $uid])->order('id', 'desc')->limit(10)->page($p)->select()->toarray();
            foreach ($list as $k => $v) {
                $list[$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
            }
            $user = db('user')->where(['id' => $uid])->field('user_money,apply_name,apply_account,yong_money,income,deposit')->find();
            $this->success('请求成功', [
                'list' => $list,
                'user_setting' => $user_setting,
                'user' => $user
            ]);
        } else {
            $err = ['code' => -1, 'msg' => 'token无效!'];
            $this->error($err);
        }
    }


    //余额提现操作
    public function cash_info()
    {
        try {
            $uid = $this->getUserId();
            // $uid = 16;

            if (!$uid) $this->error(['code' => 10001, 'msg' => $this->standard_code['10001']]);

            $user = $this->getUserInfo();
            // $user = db('user')->find($uid);

            if (empty($user['apply_name']) || empty($user['apply_account'])) {
                $this->error(['code' => 10006, 'msg' => '您还没有完善支付宝信息，不能进行提现操作']);
            }
            $user_setting = cmf_get_option('user_setting');

            $cash = db('cash')->where(['uid' => $uid, 'cash_type' => 1])->whereTime('create_time', 'today')->count();
            if ($cash == $user_setting['frequency']) {
                $this->error('今日提现已达上限，请明天再试！');
            }

            //用户提现的金额
            $money = $this->request->param('money');
            if ($money < $user_setting['minimum']) {
                $this->error('最低提现金额为'.$user_setting['minimum'].'元');
            }

            //用户提现所需手续费
            $proce = $this->request->param('proce');
            $notes = $this->request->param('notes');

            Db::startTrans();

            $file = $this->request->param('cash_img');
            if (!empty($file)) {
                $path = ROOT_PATH . 'public/upload/cash_img';
                if (!is_dir($path)) {
                    $re = mkdir($path);
                    if ($re !== true) {
                        $this->error('上传收款码失败');
                    }
                }
                $upload = $this->base64_image_content($file, $path);
                if ($upload === false) {
                    $this->error('上传收款码失败');
                }
                $add['cash_img'] = $upload;
            }

            if ($money + $proce <= 0) {
                $this->error('请输入正确的金额');
            }

            if ($user['vip_type'] == 1) {
                if ($money + $proce > ($user['user_money'] + $user['yong_money'])) {
                    $this->error('余额不足');
                }
            } else {
                if ($user['user_money'] < $money + $proce) {
                    if ($user['yong_money'] < $money + $proce - $user['user_money']) {
                        $this->error('余额不足');
                    }
                }
            }

            //查询当前会员的代理商的ID
            $agent = $user['agentId'];



            //提现表添加数据
            $add['cash_money'] = $money;
            $add['u_notes'] = $notes;
            $add['create_time'] = time();
            $add['procedures'] = $proce;

            $add['uid'] = $uid;
            $add['cash_type'] = 1; //余额提现
            if ($agent) {
                $add['agentId'] = $agent;
            } else {
                $add['agentId'] = 0;
            }
            $cid = Db::name('cash')->insertGetId($add);

            //金额扣除的处理
            if ($user['user_money'] >= $money + $proce) {
                Db::name('user')->where(['id' => $uid])->setDec('user_money', ($money + $proce));
                $newdata = Db::name('user')->where(['id' => $uid])->value('user_money');
                //用户金额变动记录
                $add1['user_id'] = $uid;
                $add1['cid'] = $cid;
                $add1['create_time'] = time();
                $add1['coin'] = 0 - ($money + $proce);
                $add1['notes'] = '余额提现';
                $add1['channel'] = 15;
                $add1['user_money'] = $newdata;
                if ($agent) {
                    $add1['agentId'] = $agent;
                } else {
                    $add1['agentId'] = 0;
                }
                Db::name('user_money_log')->insert($add1);
            } elseif ($user['user_money'] + $user['yong_money'] >= $money + $proce) {
                $res_user_money = Db::name('user')->where(['id' => $uid])->setDec('user_money', $user['user_money']);
                $res_yong_money = Db::name('user')->where(['id' => $uid])->setDec('yong_money', ($money + $proce - $user['user_money']));
                if ($res_user_money) {
                    $newdata = Db::name('user')->where(['id' => $uid])->value('user_money');
                    //用户金额变动记录
                    $add1['user_id'] = $uid;
                    $add1['cid'] = $cid;
                    $add1['create_time'] = time();
                    $add1['coin'] = 0 - $user['user_money'];
                    $add1['channel'] = 15;//余额提现
                    $add1['user_money'] = $newdata;
                    if ($agent) {
                        $add1['agentId'] = $agent;
                    } else {
                        $add1['agentId'] = 0;
                    }
                    Db::name('user_money_log')->insert($add1);
                }
                if ($res_yong_money) {
                    $add2['user_id'] = $uid;
                    $add2['yong_type'] = 3;
                    $add2['user_login'] = db('user')->where(['id' => $uid])->value('user_login');
                    $add2['fxprice'] = $money + $proce - $user['user_money'];
                    $add2['fxyj'] = $money + $proce - $user['user_money'];
                    $add2['create_time'] = time();
                    $add2['type'] = 1;
                    $add2['status'] = 0;
                    $add2['notes'] = '提现扣除佣金';
                    Db::name('user_yong_log')->insert($add2);
                }
            }
            if (!$cid) {
                // 回滚事务
                Db::rollback();
                $this->error('申请失败');
            }

            Db::commit();
            $this->success('申请成功');
        } catch (Exception $exception) {
            // 回滚事务
            Db::rollback();
            $this->error('申请失败');
        }
    }

    /**
     * 收入提现
     */
    public function income_cash()
    {
        try {
            $uid = $this->getUserId();
            if (!$uid) $this->error(['code' => 10001, 'msg' => $this->standard_code['10001']]);
            $user = $this->getUserInfo();

            if (empty($user['apply_name']) || empty($user['apply_account'])) {
                $this->error(['code' => 10006, 'msg' => '您还没有完善支付宝信息，不能进行提现操作']);
            }
            $user_setting = cmf_get_option('user_setting');
            $cash = db('cash')->where(['uid' => $uid, 'cash_type' => 3])->whereTime('create_time', 'today')->count();
            if ($cash == $user_setting['frequency']) {
                $this->error('今日提现已达上限，请明天再试！');
            }
            //用户提现的金额
            $money = $this->request->param('money');  //不扣除手续费
            $notes = $this->request->param('notes');
            $file = $this->request->param('cash_img');
            //用户提现的金额
            $money = $this->request->param('money');
            if ($money < $user_setting['minimum']) {
                $this->error('最低提现金额为'.$user_setting['minimum'].'元');
            }
            Db::startTrans();

            if (!empty($file)) {
                $path = ROOT_PATH . 'public/upload/cash_img';
                if (!is_dir($path)) {
                    $re = mkdir($path);
                    if ($re !== true) {
                        $this->error('上传收款码失败');
                    }
                }
                $upload = $this->base64_image_content($file, $path);
                if ($upload === false) {
                    $this->error('上传收款码失败');
                }

                if ($user['income'] <= 0 || ($user['income'] - $money) <= 0) {
                    $this->error('余额不足，不能提现');
                }
                $add['cash_img'] = $upload;
            }
            //提现表添加数据
            $add['cash_money'] = $money;
            $add['u_notes'] = $notes;
            $add['create_time'] = time();
            $add['procedures'] = 0;
            $add['uid'] = $uid;

            $add['cash_type'] = 3; //收入提现
            $add['agentId'] = $user['agentId'];
            $cid = Db::name('cash')->insertGetId($add);
            if (!$cid) {
                Db::rollback();
                $this->error('写入提现日志失败，请稍后重试');
            }

            if ($user['income'] <= 0 || ($user['income'] - $money) <= 0) {
                $this->error('余额不足，不能提现');
            }
            $res = Db::name('user')->where(['id' => $uid])->setDec('income', $money);
            if (!$res) {
                Db::rollback();
                $this->error('更改账户余额失败，请稍后重试');
            }
            $newdata = Db::name('user')->where(['id' => $uid])->value('income');
            //用户金额变动记录
            $add1['user_id'] = $uid;
            $add1['cid'] = $cid;
            $add1['create_time'] = time();
            $add1['coin'] = 0 - $money;
            $add1['agentId'] = $user['agentId'];
            $add1['notes'] = '收入提现';
            $add1['income'] = $newdata;
            $add1['channel'] = 33; //收入提现

            $res = Db::name('user_money_log')->insert($add1);

            if (!$res) {
                // 回滚事务
                Db::rollback();
                $this->error('申请失败，请稍后重试');
            }
            Db::commit();
            $this->success('申请成功');
        } catch (Exception $exception) {
            // 回滚事务
            Db::rollback();
            $this->error('申请失败，请稍后重试');
        }
    }

    /**
     * 保证金提现
     * @param 传入参数1
     * @param 传入参数2
     * @return 返回格式json
     */
    public function depositCashout()
    {
        try {
            $uid = $this->getUserId();
            if (!$uid) $this->error(['code' => 10001, 'msg' => $this->standard_code['10001']]);

            $user = $this->getUserInfo();
            //用户提现的金额
            $money = $this->request->param('money');
            $notes = $this->request->param('notes');
            $file = $this->request->param('cash_img');
            //用户提现的金额
            $user_setting = cmf_get_option('user_setting');
            $money = $this->request->param('money');
            if ($money < $user_setting['minimum']) {
                $this->error('最低提现金额为'.$user_setting['minimum'].'元');
            }
            Db::startTrans();
            if (!empty($file)) {
                $path = ROOT_PATH . 'public/upload/cash_img';
                if (!is_dir($path)) {
                    $re = mkdir($path);
                    if ($re !== true) {
                        $this->error('上传收款码失败');
                    }
                }
                $upload = $this->base64_image_content($file, $path);
                if ($upload === false) {
                    $this->error('上传收款码失败');
                }
                $add['cash_img'] = $upload;
            }

            if ($user['deposit'] <= 0 || ($user['deposit'] - $money) <= 0) {
                $this->error('保证金不足，不能提现');
            }

            if (empty($user['apply_name']) || empty($user['apply_account'])) {
                $this->error(['code' => 10006, 'msg' => '您还没有完善支付宝信息，不能进行提现操作']);
            }

            $user_setting = cmf_get_option('user_setting');
            $cash = db('cash')->where(['uid' => $uid, 'cash_type' => 2])->whereTime('create_time', 'today')->count();
            if ($cash == $user_setting['frequency']) {
                $this->error('今日提现已达上限，请明天再试！');
            }


            //提现表添加数据
            $add['cash_money'] = $money;
            $add['u_notes'] = $notes;
            $add['create_time'] = time();
            $add['procedures'] = 0;
            $add['uid'] = $uid;

            $add['cash_type'] = 2; //保证金提现
            $add['agentId'] = $user['agentId'];
            $cid = Db::name('cash')->insertGetId($add);
            if (!$cid) {
                Db::rollback();
                $this->error('写入提现日志失败，请稍后重试');
            }

            $res = Db::name('user')->where(['id' => $uid])->setDec('deposit', $money);

            if (!$res) {
                Db::rollback();
                $this->error('更改账户余额失败，请稍后重试');
            }
            $newdata = Db::name('user')->where(['id' => $uid])->value('deposit');
            //用户金额变动记录
            $add1['user_id'] = $uid;
            $add1['cid'] = $cid;
            $add1['create_time'] = time();
            $add1['coin'] = 0 - $money;
            $add1['agentId'] = $user['agentId'];
            $add1['notes'] = '保证金提现';
            $add1['deposit'] = $newdata;
            $add1['channel'] = 22; //保证金提现

            $res = Db::name('user_money_log')->insert($add1);

            if (!$res) {
                // 回滚事务
                Db::rollback();
                $this->error('申请失败，请稍后重试');
            }

            Db::commit();
            $this->success('申请成功');
        }catch (Exception $exception)
        {
            // 回滚事务
            Db::rollback();
            $this->error('申请失败，请稍后重试');
        }
    }




    function base64_image_content($base64_image_content, $path)
    {
        //匹配出图片的格式
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_image_content, $result)) {
            $type = $result[2];
            $new_file = $path . "/" . date('Ymd', time()) . "/";
            if (!file_exists($new_file)) {
                //检查是否有该文件夹，如果没有就创建，并给予最高权限
                mkdir($new_file, 0700, true);
            }
            $fileName = time() . uniqid() . ".{$type}";
            $new_file = $new_file . $fileName;
            if (file_put_contents($new_file, base64_decode(str_replace($result[1], '', $base64_image_content)))) {
//                return '/'.$new_file;
                return 'http://' . $_SERVER['HTTP_HOST'] . '/' . "upload/cash_img/" . date('Ymd', time()) . "/" . $fileName;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    //会员积分转余额页面
    public function integral_info()
    {
        $request = $this->request->param();
        $token = $request['token'];
        $data = db('user_token')->where(['token' => $token])->field('user_id')->find();
        $uid = $data['user_id'];
        if ($uid) {
            $user_setting = cmf_get_option('user_setting');
            $user = db('user')->where(['id' => $uid])->field('integral')->find();
            $this->success('请求成功', [
                'user_setting' => $user_setting['distribution1'],
                'user_integral' => $user['integral']
            ]);
        } else {
            $err = ['code' => -1, 'msg' => 'token无效!'];
            $this->error($err);
        }
    }

    //有米积分列表页
    public function umi_order()
    {
        $request = $this->request->param();
        $token = $request['token'];
        $data = db('user_token')->where(['token' => $token])->field('user_id')->find();
        $uid = $data['user_id'];
        if ($uid) {
            if (isset($request['p'])) {
                $p = $request['p'];
            } else {
                $p = 1;
            }
            $list = db('umi_order')->where(['user' => $uid])->limit(10)->page($p)->order('id desc')->select()->toArray();
            foreach ($list as $k => $v) {
                $list[$k]['time'] = date('Y-m-d H:i:s', $v['time']);
            }

            $this->success('请求成功', ['list' => $list]);
        } else {
            $err = ['code' => -1, 'msg' => 'token无效!'];
            $this->error($err);
        }
    }

    //会员积分转余额操作
    public function integral_money()
    {
        $request = $this->request->param();
        $token = $request['token'];
        $data = db('user_token')->where(['token' => $token])->field('user_id')->find();
        $uid = $data['user_id'];
        if ($uid) {
            if ($request['integral'] <= 0) {
                $this->error('请输入正确的金额');
            }
            $user = db('user')->where(['id' => $uid])->find();
            if ($user['integral'] <= 0) {
                $this->error('账户积分不足');
            }
            if ($request['integral'] > $user['integral']) {
                $this->error('提现积分大于账户积分');
            }

            //获取积分转余额的比率
            $user_setting = cmf_get_option('user_setting');
            $money = $request['integral'] / $user_setting['distribution1'];

            $flag = false;
            $err = '操作失败';

            $agentid = db('user')->where(['id' => $uid])->value('agentId');

            $res = Db::query('call umi_integral(:uid,:integrals,:money,:agentId)', ['uid' => $uid, 'integrals' => $request['integral'], 'money' => $money, 'agentId' => $agentid]);

            foreach ($res[0][0] as $k => $v) {
                if ($v == 1) {
                    $flag = true;
                    $err = '操作成功';
                    break;
                }
            }

            if ($flag) {
                $this->success($err, ['user_task' => 0]);
            } else {
                $this->error($err);
            }
        } else {
            $err = ['code' => -1, 'msg' => 'token无效!'];
            $this->error($err);
        }
    }


    //  保证金提现
    public function outDeposit()
    {
        $request = $this->request->param();
        $token = $request['token'];
        $data = db('user_token')->where(['token' => $token])->field('user_id')->find();
        $uid = $data['user_id'];

        if ($uid > 0) {
            $user = db('user')->where(['id' => $uid])->find();

            if (empty($user['apply_name']) || empty($user['apply_account'])) {
                $this->error('您还没有完善支付宝信息，不能进行提现操作');
            }
            if ($user['user_type'] == 1) {
                $cash = db('cash')->where(['uid' => $uid])->whereTime('create_time', 'today')->count();
                if ($cash == 1) {
                    $this->error('普通会员每天只能提现一次，请明天在来提现。');
                }
            } else {
                $cash = db('cash')->where(['uid' => $uid])->whereTime('create_time', 'today')->count();
                if ($cash >= 2) {
                    $this->error('今天已达到2次，请明天在来提现。');
                }
            }

            $request = $this->request;
            if ($this->request->isPost()) {
                // 用户提现金额
                $money = sprintf('%2.f', $request->param('money'));
                // 提现所需手续费
                $proce = sprintf('%2.f', $request->param('proce'));

                if (($money + $proce) <= 0) {
                    $this->error('请输入正确的提现金额');
                }

                if ($money + $proce > $user['deposit']) {
                    $this->error('保证金余额不足');
                }
                if ($user['vip_type'] == 1) {
                    if ($money + $proce > $user['deposit']) {
                        $this->error('保证金余额不足');
                    }
                    // 获取后台设置的每日最大提现额度
                    $user_setting = cmf_get_option('user_setting');
                    if ($money > $user_setting['registerMoney']) {
                        $this->error('普通会员每日提现最大额度为' . $user_setting['registerMoney'] . '元');
                    }
                } else {
                    if ($user['deposit'] < ($money + $proce)) {
                        if ($user['deposit'] < $money + $proce - $user['deposit']) {
                            $this->error('保证金余额不足');
                        }
                    }
                }

                // 查询当前会员的代理商id
                $agent = $user['agentId'];

                // 开启事务操作
                Db::startTrans();
                // 提现表添加数据
                $add = [
                    'cash_money' => $money,
                    'u_notes' => $request->param('notes'),
                    'create_time' => time(),
                    'procedures' => $proce,
                    'uid' => $uid,
                    'cash_type' => 2  //保证金
                ];
                if (!empty($agent)) {
                    $add['agentId'] = $agent;
                } else {
                    $add['agentId'] = 0;
                }
                // 添加提现表并获取插入id
                $cid = Db::name('cash')->insertGetId($add);
                if (empty($cid)) {
                    Db::rollback();
                    $this->error('提现失败');
                }

                //金额扣除处理
                if ($user['deposit'] >= ($money + $proce)) {
                    Db::name('user')->where(['id' => $uid])->setDec('deposit', ($money + $proce));
                    $newdata = Db::name('user')->where(['id' => $uid])->value('deposit');
                    // 插入日志
                    $logData = [
                        'user_id' => $uid,
                        'cid' => $cid, // 对应提现表的id
                        'create_time' => time(),
                        'coin' => 0 - ($money + $proce),
                        'type' => 1,
                        'status' => 0,
                        'notes' => '保证金提现',
                        'deposit' => $newdata
                    ];
                    if (!empty($agent)) {
                        $logData['agentId'] = $agent;
                    } else {
                        $logData['agentId'] = 0;
                    }

                    $res = Db::name('user_money_log')->insert($logData);

                    if (empty($res)) {
                        Db::rollback();
                        $this->error('添加日志失败');
                    }
                }

                if ($cid > 0) {
                    Db::commit();
                    $this->success('申请成功');
                } else {
                    // 回滚事务
                    Db::rollback();
                    $this->error('申请失败');
                }
            }
        } else {
            $err = ['code' => -1, 'msg' => 'token无效!'];
            $this->error($err);
        }
    }

    // 个人接取的任务
    public function receiveTask()
    {
        $token = $this->request->param('token');
        $p = $this->request->param('p');
        if (empty($p)) {
            $p = 1;
        }
        $status = $this->request->param('status'); // 1进行中 2.已完成

        $data = db('user_token')->where(['token' => $token])->field('user_id')->find();
        $uid = $data['user_id'];

        // 接的任务
        $filed = ['t.id', 't.money', 't.images', 't.end', 't.title', 't.type', 'r.status'];
        $where = [];
        if (!empty($status) && $status == 1) {
            $where['r.status'] = ['<', '3'];
        } elseif (!empty($status) && $status == 2) {
            $where['r.status'] = ['=', '3'];
        }

        $receive_task = db('self_task_receive')->alias('r')
            ->join('self_task t', 'r.taskid = t.id', 'left')
            ->where(['r.userid' => $uid])
            ->where($where)
            ->field($filed)
            ->limit(10)->page($p)
            ->select()->toArray();

        if (empty($receive_task)) {
            $this->success('暂无数据');
        }
        foreach ($receive_task as $k => $v) {
            $end = strtotime($receive_task[$k]['end']);
            if ($end - time() > 0) {
                $receive_task[$k]['isInvalid'] = 0;
            } else {
                $receive_task[$k]['isInvalid'] = 1;
            }

            if (!empty($v['images'])) {
                $receive_task[$k]['images'] = json_decode($v['images'])[0];
            }

            if ($v['type'] == 5) {
                $receive_task[$k]['type'] = "点击任务";
            } elseif ($v['type'] == 6) {
                $receive_task[$k]['type'] = "试玩任务";
            }

            if ($v['status'] == 0) {
                $receive_task[$k]['status'] = '进行中';
            } elseif ($v['status'] == 1) {
                $receive_task[$k]['status'] = '已回复';
            } elseif ($v['status'] == 2) {
                $receive_task[$k]['status'] = '已采纳';
            } elseif ($v['status'] == 3) {
                $receive_task[$k]['status'] = '已拒绝';
            } else {
                $receive_task[$k]['status'] = '未知状态';
            }
        }

        $this->success('查询个人任务成功', $receive_task);
    }

    // 个人发的任务
    public function mytasklist()
    {
        $request = $this->request->param();
        $token = $this->request->param('token');
        if (empty($token)) {
            $err = ['code' => -1, 'msg' => 'token无效!'];
            $this->error($err);
            exit;
        }
        $p = $this->request->param('p');
        if (empty($p)) {
            $p = 1;
        }
        $data = db('user_token')->where(['token' => $token])->field('user_id')->find();
        $uid = $data['user_id'];

        $field = ['id', 'money', 'images', 'end', 'title', 'type', 'status'];
        $task_list = db('self_task')
            ->where('userid', $uid)
            ->field($field)
            ->limit(10)->page($p)
            ->select()->toArray();
        if (empty($task_list)) {
            $this->success('暂无数据');
        }
        foreach ($task_list as $k => $v) {
            $end = strtotime($task_list[$k]['end']);
            if ($end - time() > 0) {
                $task_list[$k]['isInvalid'] = 0;
            } else {
                $task_list[$k]['isInvalid'] = 1;
            }

            if ($v['type'] == 5) {
                $task_list[$k]['type'] = "点击任务";
            } elseif ($v['type'] == 6) {
                $task_list[$k]['type'] = "试玩任务";
            }

            if (!empty($v['images'])) {
                $task_list[$k]['images'] = json_decode($v['images'])[0];
            }

            if ($v['status'] == 0) {
                $task_list[$k]['status'] = '进行中';
            } elseif ($v['status'] == 1) {
                $task_list[$k]['status'] = '审核中';
            } elseif ($v['status'] == 2) {
                $task_list[$k]['status'] = '已下架';
            } else {
                $task_list[$k]['status'] = '未知状态';
            }
        }

        $this->success('查询任务成功', $task_list);
    }

}