<?php

namespace app\fenxiao\controller;

use think\Controller;
use think\Db;
use think\Request;

class ChongzhiController extends Controller
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

        file_put_contents('xxxxxxwxnotify.txt', json_encode($this->order_info) . PHP_EOL, FILE_APPEND);
        if ($this->order_info['type'] == 0){
            $money = $this->order_info['money'];
            $this->add_money($money);   // 加余额
        }

        if($this->order_info['type'] == 1){
            $money = $this->order_info['money'];
            $this->add_deposit($money);   // 加保证金
        }
    }



    function add_money($money)
    {
        db('user')->where('id',$this->user_id)->setInc('user_money',$money);

    }

    function add_deposit($money)
    {
        db('user')->where('id',$this->user_id)->setInc('deposit',$money);
    }

}