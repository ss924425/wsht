<?php

namespace app\fenxiao\controller;

use think\Controller;
use think\Db;
use think\Request;

class TaskUserCzController extends Controller
{
    private $user_id;
    private $user_info;
    private $order_info;


    function __construct($user_id, $order_info)
    {
        parent::__construct();
        $this->user_id = $user_id;
        $user_info = db('user')->find($user_id);
        if (!$user_info) {
            return;
        }

        $this->user_info = $user_info;
        $this->order_info = $order_info;

        file_put_contents('notify.txt', json_encode($this->order_info) . PHP_EOL, FILE_APPEND);

        $money = $this->order_info['WIDtotal_amount'];
        if ($order_info['type'] == 1){
            $this->add_money($money);   // 加余额
        }
        if ($order_info['type'] == 2){
            $this->add_deposit($money);   // 保证金
        }

    }

    function add_money($money)
    {
        $uid = $this->user_id;
        $res = db('user')->where('id','=',$uid)->setInc('user_money',$money);
        if ($res) {  // 成功之后加入余额变更日志
            $newdata = db('user')->where('id','=',$uid)->field('user_money,deposit,income')->find();
            $logdata = [
                'user_id' => $uid,
                'coin' => $money,
                'channel' => 66, //任务用户充钱
                'user_money' => $newdata['user_money'],
                'deposit' => $newdata['deposit'],
                'income' => $newdata['income'],
                'create_time' => time(),
                'notes' => '任务用户充值余额'.$money
            ];
            db('user_money_log')->insert($logdata);
            $this->send_upgrade_sms();
        }
    }

    function add_deposit($money)
    {
        $uid = $this->user_id;
        $res = db('user')->where('id','=',$uid)->setInc('deposit',$money);
        if ($res) {  // 成功之后加入余额变更日志
            $newdata = db('user')->where('id','=',$uid)->field('user_money,deposit,income')->find();
            $logdata = [
                'user_id' => $uid,
                'coin' => $money,
                'channel' => 67, //任务用户充钱
                'user_money' => $newdata['user_money'],
                'deposit' => $newdata['deposit'],
                'income' => $newdata['income'],
                'create_time' => time(),
                'notes' => '任务用户充值保证金'.$money
            ];
            db('user_money_log')->insert($logdata);
            $this->send_upgrade_sms();
        }
    }


    function send_upgrade_sms()
    {
        return true;
    }
}