<?php

namespace app\fenxiao\controller;

use think\Controller;
use think\Db;
use think\Request;

class ClCzController extends Controller
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

        file_put_contents('clnotify.txt', json_encode($this->order_info) . PHP_EOL, FILE_APPEND);

        $money = $this->order_info['WIDtotal_amount'];
        $this->add_money($money);   // 加余额
    }


    function add_money($money)
    {
        $uid = $this->user_id;
        $res = db('user')->where('id','=',$uid)->setInc('cl_money',$money);
        if ($res) {
            $this->send_upgrade_sms();
        }
    }


    function send_upgrade_sms()
    {
        return true;
    }
}