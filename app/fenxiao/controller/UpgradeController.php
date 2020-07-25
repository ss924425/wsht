<?php

namespace app\fenxiao\controller;

use think\Controller;
use think\Db;
use think\Request;

class UpgradeController extends Controller
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
        if ($user_info['vip_type'] > 1) {
            return;
        }
        //已经处理过
        if ($order_info['state']) {
            return;
        }
        $this->user_info = $user_info;
        $this->order_info = $order_info;

        file_put_contents('notify.txt', json_encode($this->order_info) . PHP_EOL, FILE_APPEND);
        $this->upgrade_deal();
    }

    //处理升级支付成功后的操作
    function upgrade_deal()
    {
        if ($this->user_info['vip_type'] > 1) {
            return;
        }
        //升级为VIP
        $this->change_agent();

        //升级订单确认完成
        db('upgrade_order')->where(['order_sn' => $this->order_info['order_sn']])->setField('state', 1);
    }


    //升级VIP
    function change_agent()
    {
        $users = db('user');
        $user_info = $users->find($this->user_id);

        if ($user_info['vip_type'] == 2) return;
        $data['vip_type'] = 2;
        $data['vip_end_time'] = strtotime("+1 year");
        $res = $users->where("id = '$this->user_id'")->update($data);
        if ($res) {
            $this->send_upgrade_sms();  //发短信
        }
    }


    function send_upgrade_sms()
    {
        return true;
    }
}