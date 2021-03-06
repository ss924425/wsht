<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace api\pay\controller;

use cmf\controller\RestBaseController;
use think\Controller;
use think\Exception;
use think\Loader;
use think\Log;

class WxpayczController extends RestBaseController
{
    private $_wx;
    private $debug = false;

    public function __construct()
    {
        parent::__construct();
        Loader::import('wx.Wxpayappsdk', EXTEND_PATH);
        $this->_wx = new \Wxpayappsdk();
    }

    public function getorderinfo()
    {
        $channel = input('pay_id');  // 支付类型
        $money = input('money');

        $type = input('type');    // 0 余额充值  1 保证金充值
        if ($channel != 'wxpay' || $money < 0) {
            echo json_encode(['status' => 0, 'msg' => '支付数据异常']);
            exit;
        }
        $user_info = db('user')->find($this->userId);
//        if ($user_info['id'] == 601){
//            $money = 0.01;
//        }
        if (!$user_info) {
            echo json_encode(['status' => -1, 'msg' => "支付信息获取失败,请尝试重新登陆~"]);
            exit;
        }

        $order_info = $this->createOrder($money, $type);

        $pay_setting = cmf_get_option('pay_setting');
        $wxset = $pay_setting['wxpay'];
        $this->_wx->apikey = $wxset['apikey'] ? $wxset['apikey'] : '123456';
        $dataArr = array(
            'appid' => $wxset['appid'],
            'mch_id' => $wxset['mch_id'],
            'nonce_str' => $this->_wx->getNonceStr(), //随机字符串
            'body' => '充值',
            'attach' => '',
            'out_trade_no' => $order_info['order_sn'],
            'total_fee' => $this->debug ? 1 : round($order_info['money'] * 100),
            'spbill_create_ip' => get_client_ip(),
            'notify_url' => "http://" . $_SERVER['HTTP_HOST'] . "/api/pay/Wxpaycz/notify",
            'trade_type' => 'APP'
        );


        //=====================执行=======================
        $sign = $this->_wx->MakeSign($dataArr);//签名生成
        $dataArr['sign'] = $sign;

        $xmlStr = $this->_wx->createXML($dataArr);//统一下单xml数据生成

        $xml = $this->_wx->curl('https://api.mch.weixin.qq.com/pay/unifiedorder', $xmlStr);//发送请求 统一下单数据

        //解析返回的xml字符串
        $re = $this->_wx->xmlToArray($xml);

        //判断统一下单是否成功
        if ($re['return_code'] == 'SUCCESS') {
            //支付请求数据
            $payData = array(
                'appid' => $re['appid'],
                'partnerid' => $re['mch_id'],
                'prepayid' => $re['prepay_id'],
                'noncestr' => $this->_wx->getNonceStr(),
                'package' => 'Sign=WXPay',
                'timestamp' => time()
            );

            //生成支付请求的签名
            $paySign = $this->_wx->MakeSign($payData);

            $payData['sign'] = $paySign;
            //拼接成APICLOUD所需要支付数据请求
            $payDatas = array(
                'apiKey' => $re['appid'],
                'orderId' => $re['prepay_id'],
                'mchId' => $re['mch_id'],
                'nonceStr' => $payData['noncestr'],
                'package' => 'Sign=WXPay',
                'timeStamp' => $payData['timestamp'],
                'sign' => $paySign
            );
            return json(['status' => 1, 'data' => $payDatas]);
            exit;
        } else {
            return json(['status' => 0, 'msg' => $re['return_msg']]);

        }
    }


    public function createOrder($money, $type)
    {
        $morder = db('user_addmoney_log');
        $data['order_sn'] = date('YmdHis');
        $data['user_id'] = $this->userId;
        $data['paytype'] = 1;
        $data['money'] = $money;
        $data['create_time'] = time();
        $data['status'] = 0;
        $data['is_true'] = 0;
        $data['type'] = $type;
        $res = $morder->insertGetId($data);
        return db('user_addmoney_log')->find($res);
    }

    public function notify()
    {
        try {
            Log::error('微信支付回调开始');

            $postStr =  file_get_contents('php://input');

            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $order_sn = trim($postObj->out_trade_no);
            $total_fee = trim($postObj->cash_fee) / 100;
            $morder = db('user_addmoney_log');
            $order_info = $morder->where(['order_sn' => $order_sn])->find();

            if ($order_info['money'] != round($total_fee, 2)) {
                return;
            }
            if ($order_info == null || $order_info['status'] == 1) {
                echo '<xml>
			   <return_code><![CDATA[SUCCESS]]></return_code>
			   <return_msg><![CDATA[OK]]></return_msg>
			</xml>';
                exit;
            }
            if (!$order_info['is_true']) {
                $morder->where(['order_sn' => $order_sn])->setField('is_true', 1);

                new \app\fenxiao\controller\ChongzhiController($order_info['user_id'], $order_info);
                echo '<xml>
                 <return_code><![CDATA[SUCCESS]]></return_code>
                 <return_msg><![CDATA[OK]]></return_msg>
                 </xml>';
                exit;
            }

            //支付成功，开始处理业务
//            $order_info['is_true'] = 1;

        }catch (Exception $exception)
        {
            Log::error('微信支付回调'.$exception->getMessage());
        }
    }
}
