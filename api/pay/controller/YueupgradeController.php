<?php

namespace api\pay\controller;

use cmf\controller\RestBaseController;
use think\Controller;
use think\Db;
use think\Exception;
use think\Log;

class YueupgradeController extends RestBaseController
{

    public function getorderinfo()
    {

        $channel = input('pay_id');
        $money = input('money');
        if ($channel != 'yue' || $money < 0) {
            echo json_encode(['status' => 0, 'msg' => '支付数据错误']);
            exit;
        }
        $user_info = db('user')->find($this->userId);

        if (!$user_info) {
            echo json_encode(['status' => -1, 'msg' => "支付信息获取失败,请尝试重新登陆~"]);
            exit;
        }

        if ($user_info['vip_type'] > 1) {
            echo json_encode(['status' => 0, 'msg' => "您已经升级过了~".$user_info['vip_type']]);
            exit;
        }

        $order_info = $this->createOrder($money);
        if (!$order_info) {
            echo json_encode(['status' => 0, 'msg' => '当前支付人数较多，请稍后重试']);
            exit;
        }

        if ($user_info['user_money'] + $user_info['income'] < $money){
            echo json_encode(['status' => 0, 'msg' => '余额不足']);
            exit;
        }

        $res = $this->upuser($user_info,$money);
        if ($res){
            echo json_encode(['status' => 1, 'msg' => '升级成功']);
            exit;
        }
    }

    public function createOrder($money)
    {
        $order_sn = date('YmdHis');
        $res = db('upgrade_order')->where('order_sn', $order_sn)->find();
        if ($res) {
            return false;
        }
        $morder = db('upgrade_order');
        $data['order_sn'] = $order_sn;
        $data['user_id'] = $this->userId;
        $data['paytype'] = 3;  // 余额支付
        $data['payprice'] = $money;
        $data['paytime'] = time();
        $data['state'] = 0;
        $data['is_true'] = 0;
        $res = $morder->insertGetId($data);
        return db('upgrade_order')->find($res);
    }

    public function upuser($user,$money)
    {
        $res = db("user")->where('id',$user['id'])->setField('vip_type',2);
        if ($res){
           Db::startTrans();
           //扣钱
            if ($user['user_money'] >= $money){
                $res1 = db("user")->where('id',$user['id'])->setDec('user_money',$money);
                if ($res1) {
                    $newuser = db('user')->where('id',$user['id'])->find();
                    $logdata = [
                        'user_id' => $user['id'],
                        'coin' => "-".$money,
                        'type' => 99,
                        'user_money' => $newuser['user_money'],
                        'deposit' => $newuser['deposit'],
                        'income' => $newuser['income'],
                        'notes' => '升级会员',
                        'create_time' => time(),
                    ];
                    $res2 = db("user_money_log")->insertGetId($logdata);
                    if ($res2){
                        Db::commit();
                        return true;
                    }
                }
            } else {
                if ($user['income'] >= $money){
                    $res3 = db("user")->where('id',$user['id'])->setDec('income',$money);
                    if ($res3){
                        $newuser1 = db('user')->where('id',$user['id'])->find();
                        $logdata1 = [
                            'user_id' => $user['id'],
                            'coin' => "-".$money,
                            'type' => 99,
                            'user_money' => $newuser1['user_money'],
                            'deposit' => $newuser1['deposit'],
                            'income' => $newuser1['income'],
                            'notes' => '升级会员',
                            'create_time' => time(),
                        ];
                        $res4 = db("user_money_log")->insertGetId($logdata1);
                        if ($res4){
                            Db::commit();
                            return true;
                        }
                    }
                } else {
                    $zong  = $user['user_money'] + $user['income'];
                    if ($zong >= $money){
                        $cha = $zong - $money;
                        $res5 = db("user")->where('id',$user['id'])->update(['user_money'=>$cha,'income'=>0]);
                        if ($res5){
                            $newuser2 = db('user')->where('id',$user['id'])->find();
                            $logdata2 = [
                                'user_id' => $user['id'],
                                'coin' => "-".$money,
                                'type' => 99,
                                'user_money' => $newuser2['user_money'],
                                'deposit' => $newuser2['deposit'],
                                'income' => $newuser2['income'],
                                'notes' => '升级会员',
                                'create_time' => time(),
                            ];
                            $res6 = db("user_money_log")->insertGetId($logdata2);
                            if ($res6){
                                Db::commit();
                                return true;
                            }
                        }
                    }
                }
            }
        }
    }

}