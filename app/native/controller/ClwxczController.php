<?php

namespace app\native\controller;

use cmf\controller\AdminBaseController;
use cmf\phpqrcode\QRcode;
use think\Loader;
use think\Request;

class ClwxczController extends AdminBaseController
{
    function __construct(Request $request = null)
    {
        parent::__construct($request);
        Loader::import('native.WxPay.Api', EXTEND_PATH);
        require_once "WxPayNativePayController.php";
        require_once "phpqrcode.php";
    }

    function cz()
    {
        return $this->fetch();
    }

    // 下单
    function order()
    {
        $data = $this->request->param();
        $notify = new WxPayNativePayController();
        $input = new WxPayUnifiedOrderController();
        $notify_url = "http://" . $_SERVER['HTTP_HOST'] . url('native/Nativenotify/notify');
        $input->SetBody($data['ordernote']);
        $input->SetAttach($data['ordername']);
        $input->SetOut_trade_no("sdkphp123456789".date("YmdHis"));
        $input->SetTotal_fee($data['ordermoney'] * 100);
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetGoods_tag("余额充值");
//        $input->SetNotify_url("http://paysdk.weixin.qq.com/notify.php");
        $input->SetNotify_url($notify_url);
        $input->SetTrade_type("NATIVE");
        $input->SetProduct_id($data['orderid']);

        $result = $notify->GetPayUrl($input);
        if ($result){
            // 创建支付订单
            $clorder = [
                'WIDout_trade_no' => trim($_POST['orderid']),
                'WIDsubject' => trim($_POST['ordername']),
                'WIDtotal_amount' => trim($_POST['ordermoney']),
                'WIDbody' => trim($_POST['ordernote']),
                'create_time' => time(),
                'user_id' => session("ADMIN_ID"),
                'type' => 2, // 微信支付
                'out_trade_no' => $input->GetOut_trade_no(),
            ];
            db('clczorder_log')->insert($clorder);
        }

        $url = $result["code_url"];
        if(substr($url, 0, 6) == "weixin"){
           $codeurl = QRcode::png($url,false,'QR_ECLEVEL_L',6);
        }else{

            header('HTTP/1.1 404 Not Found');
        }
        dump($codeurl);die;
    }


}