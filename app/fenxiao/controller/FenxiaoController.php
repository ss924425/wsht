<?php

namespace app\fenxiao\controller;

use think\Controller;
use think\Db;
use think\Request;

class FenxiaoController extends Controller
{
    private $user_id;
    private $user_info;
    private $order_info;
//    private $fenxiao_info_1 = [
//        'fenxiao_switch' => '1',  //分销开关
//        'fenxiao_level' => '2',  //二级分销
//        'first_per' => '50',  //一级分销率
//        'second_per' => '50',  //二级分销率
//        'agent_per' => '50',  //代理商固返金额
//        'fx_viptype_list' => [[1, 2, 3, 4], [3, 4]] //参与佣金的用户类型
//    ];
    private $fenxiao_info = [];

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

        $this->fenxiao_info = cmf_get_option('upgrade_setting');
        $this->fenxiao_info['fx_viptype_list'] = array($this->fenxiao_info['first_per_type'],$this->fenxiao_info['second_per_type']);

        file_put_contents('notify.txt', json_encode($this->order_info) . PHP_EOL, FILE_APPEND);
        $this->upgrade_deal();
    }

    //处理升级支付成功后的操作
    function upgrade_deal()
    {
        if ($this->user_info['vip_type'] > 1) {
            return;
        }
        //总开关
        if ($this->fenxiao_info['fenxiao_switch'] == 0) {
            return;
        }
        //升级为VIP
        $this->change_agent();
        //处理分销佣金
        $this->deal_all();
        //处理固定返
        $this->deal_agent();
        //升级订单确认完成
        db('upgrade_order')->where(['order_sn' => $this->order_info['order_sn']])->setField('state', 1);
    }

    //给代理商发固定返
    function deal_agent()
    {
        if (!$this->user_info['agentId']) {
            return;
        }
        $agent_info = db('user')->find($this->user_info['agentId']);
        if (!$agent_info || $agent_info['vip_type'] < 3) {
            return;
        }
        $res = db('user')->where(['id' => $this->user_info['agentId']])->setInc('yong_money', $this->fenxiao_info['agent_per']);
        if ($res > 0) {
            $this->deal_fenxiaolog($this->order_info['id'], $this->user_info['agentId'], $this->fenxiao_info['agent_per'], $this->fenxiao_info['agent_per'], '', 1);
            $this->maid_news($this->user_info['agentId'], $this->fenxiao_info['agent_per'], 2);
        }
    }

    function deal_all()
    {
        $fenxiao_level = $this->fenxiao_info['fenxiao_level'];
        $pid_array = $this->get_deal_user_id($this->user_id, $fenxiao_level);
        if (!$pid_array) {
            return;
        }
        $i = 1;
        $users = db('user');

        foreach ($pid_array as $pid) {
            if ($pid == 0) {
                break;
            }
            $parent_info = $users->where(['id' => $pid])->find();
            //一级一定是团队内
            if ($i == 1) {
                $pid_profit = $this->profit_sum($i);
            } else {
                //二级以上agentID相同则为团队内
                if ($this->user_info['agentId'] > 0 && $pid == $this->user_info['agentId']) {
                    $pid_profit = $this->profit_sum($i);
                } else {
                    $pid_profit = 0;
                }
            }

            if ($pid_profit > 0 && in_array($parent_info['vip_type'], $this->fenxiao_info['fx_viptype_list'][$i - 1])) {

                $users->where(['id' => $pid])->setInc('yong_money', $pid_profit);
                $this->deal_fenxiaolog($this->order_info['id'], $pid, $this->order_info['payprice'], $pid_profit, $i);
                $this->maid_news($pid, $pid_profit, 1);
            }
            $i++;
        }
    }

    function get_deal_user_id($user_id, $fenxiao_level, $i = 0, $arr = [])
    {
        $users = db('user');
        if ($fenxiao_level == 0) {
            return $arr;
        }
        $pid = $users->where(['id' => $user_id])->value('pid');
        $arr[$i] = $pid;
        if ($pid == 0) {
            return $arr;
        } else {
            $i++;
            $fenxiao_level--;
            return $this->get_deal_user_id($pid, $fenxiao_level, $i, $arr);
        }
    }

    //通用分销佣金计算方法
    public function profit_sum($type)
    {
        $profit = 0;
        switch ($type) {
            case 1:
                $profit = $this->fenxiao_info['first_per'];
                break;
            case 2:
                $profit = $this->fenxiao_info['second_per'];
                break;
        }
        return $profit;
    }

    //升级VIP
    function change_agent()
    {
        $users = db('user');
        $user_info = $users->find($this->user_id);
        //管理员用户、已经是高级用户的不处理
        $allow_list = array(2, 3, 4);
        if (in_array($user_info['vip_type'], $allow_list)) {
            return;
        }

        $data['vip_type'] = 2;
        $data['vip_end_time'] = strtotime("+1 year");
        $res = $users->where("id = '$this->user_id'")->update($data);
        if ($res) {
            $this->send_upgrade_sms();
        }
    }

    /**
     * 写入分销日志
     * @param $user_id 发放给
     * @param $fxprice 分销价
     * @param $fxyj 分销佣金
     * @param $type 几级分销
     * @param int $yong_type 佣金类型
     */
    function deal_fenxiaolog($order_id, $user_id, $fxprice, $fxyj, $type, $yong_type = 0, $notes = '')
    {
        $log['order_id'] = $order_id;
        $log['yong_type'] = $yong_type;
        $log['user_id'] = $user_id;
        $log['user_login'] = db('user')->where(['id' => $user_id])->value('mobile');
        $log['sup_id'] = $this->user_id;
        $log['sup_login'] = $this->user_info['mobile'];
        $log['create_time'] = time();
        $log['fxprice'] = $fxprice;
        $log['fxyj'] = $fxyj;
        switch ($yong_type) {
            case 0:
                {
                    if ($type == 1) {
                        $log['notes'] = "分销1级佣金";
                        break;
                    } elseif ($type == 2) {
                        $log['notes'] = "分销2级佣金";
                        break;
                    }
                }
            case 1:
                $log['notes'] = "代理商固定返佣";
                break;
            case 2:
                $log['notes'] = "股东任务返佣";
                break;
        }
        $log['notes'] = $notes ? $notes : $log['notes'];
        Db::name('user_yong_log')->insert($log);
    }

    //佣金发放成功后消息推送
    function maid_news($user_id, $fxyj, $type)
    {
        if ($type == 1) {
            $add['uid'] = $user_id;
            $add['time'] = time();
            $add['news'] = '推荐会员升级成功，奖励佣金' . $fxyj . '元';
            $add['type'] = 2;
            $add['status'] = 0;
        } else if ($type == 2) {
            $add['uid'] = $user_id;
            $add['time'] = time();
            $add['news'] = '团队中会员升级成功，奖励佣金' . $fxyj . '元';
            $add['type'] = 2;
            $add['status'] = 0;
        }
        Db::name('news')->insert($add);
    }

    function send_upgrade_sms()
    {
        return true;
//        $message = "您已成功升级VIP，可享受一级分销，快去把注册链接分享给小伙伴吧";
//        $url = "http://" . $_SERVER['HTTP_HOST'] . url('index/Agent/order', ['top' => $this->user_id]);
//        $user_info = db('user')->where(['id' => $this->user_id])->value('mobile');
//        $message .= getShortUrl($url);
//        cmf_send_sms($user_info['mobile'], $message);
    }
}