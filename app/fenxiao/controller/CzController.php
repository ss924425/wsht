<?php

namespace app\fenxiao\controller;

use think\Controller;
use think\Db;
use think\Request;

class CzController extends Controller
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
        //已经处理过
        if ($order_info['status']) {
            return;
        }
        $this->user_info = $user_info;
        $this->order_info = $order_info;

        file_put_contents('cznotify.txt', json_encode($this->order_info) . PHP_EOL, FILE_APPEND);
        if ($this->order_info['type'] == 0){
            $money = $this->order_info['money'];
            $this->upgrade_deal($money);   // 加余额
        }

        if($this->order_info['type'] == 1){
            $money = $this->order_info['money'];
            $this->deposit($money);   // 加保证金
        }
    }

    //处理升级支付成功后的操作
    function upgrade_deal($money)
    {
        // 增加金额
        $uid = $this->user_id;
        $res = db('user')->where('id',$uid)->setInc('user_money',$money);
        if ($res) {
            db("user_addmoney_log")->where('order_sn','=',$this->order_info['order_sn'])->setField('status', 1);
        }

    }

    function deposit($money){
        $uid = $this->user_id;
        $res = db('user')->where('id',$uid)->setInc('deposit',$money);
        if ($res) {
            db("user_addmoney_log")->where('order_sn','=',$this->order_info['order_sn'])->setField('status', 1);
        }
    }

}