<?php

namespace app\admin\controller;


use cmf\controller\AdminBaseController;

class TaskUserPagepayController extends AdminBaseController
{
    const APPID = '2019030263436761';

    const MPK = 'MIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQDSlalKjqpZUPo9aJn8Uo+9wYgUETdlGnYFmvOg/IcnlzwoZBqnCkMLzRfN+OXoFZPyW+KrRrMQifAceWrGfJV7DCTp0i2G6pxZOjKhUdX/aUX9mqmVuTe3BFqWH/RL6J0ozXeEwvc/ekI3JmmOsLV5x+FQDLL35IBL/OvhlPZTlPAFD1N/sT9X3a/eCf6t63xWk6zPCJLtMQR/s4pcf+ArkX/Pcuh0BsTdf+ctrpoirmkJvJB1DN2l16Pl+o5DgOUXfCh7+vpo0FoC04j2+fzGc8IWaQa1cWzt25TNoIBM2U0fzW8hbuh45MwjkLD34uoKat6/VB9R5GC1mxEXin2zAgMBAAECggEBAIgo+uBZZK4BnPvt0XsDyytPonQPObkmpd8z9IlIHv+rWttm9pDBIt7TiqvEXqt0oEeZv/Ms8IUmG1nd6/tQ/Lol/QwuuP8+XT+YULpcFrlh0T6MLVDBBNRSfVwKZ0RZsJx8VeZCxemGXKAaNzBq87w9UGMZAvMkDQyVCdO/JJKfUzzZCwAhzCz3zaE17rT31PFQcD4gvSdH/uOiMxNCNrYMQNmmrTpqKjjTpgh3nE1SAwJok6+8OqTI+r7QBwUS4xhwNZ5mwWtLi1uv/L41ymRO/Gwr28krQegt8sGg22ejFIbIYr7emACNcCpjrPtgcc6j7GodbPvi2hJS0YCeWykCgYEA7vf+KTEwp2Mximg2En69i+9KgFZ4TLG80HYpnWCgFds7WkeY4PA7QGhk4Tce1vXTH6Dj8dGBFlqJ3+RbBsDyxFdRa1+0kvm6cJDy0OzYo0VO+U6HH+pvOaPS14ZJCJtZfR+XT5/lmLUKbyEpIsLqcl67Y30ID5hkvnjVb3gDz6UCgYEA4ZfMN4MJ5fl4KEgOOLvzVT5xwXtweKQFl5T7xQrGvLmw4BxWahP68yiCO9ITBFx6bkC9kPD731tHfEwhwa5X0+W3wqihVrekTSSZ+o8h3Ag7GjwSsPoZqg74IE3c0SG81CC7dRXialujT4hEv9RvISijpvxT6JGubg01o1efmHcCgYA2zd0WKVfVK6SS729nMnXZ/9kAMdSJEkIRNOg6VYyhNpQYEk93VuDf1pE8LV3/QoVWvZlJPhjyvXTdSguuMtX6PWRI0bwh8O/XHQby7z6Hwz9nYaEPqr4zY+TY1M0vEiGl1nbnJe6L5Qktj1dVx4npDNzA5k3Q9cw0+pVIaSfGmQKBgFgFZY4nV95eh06YRsxGMXyKP9JxeoHn5bKuU8ofGUob3fKju+fp4dmsbZwvrHHgL3kDU7PRA0W0FOFfxzAN+YDZOej+6Oyv/LCI3neQD3MN1xm2ZMie3RKogpIAL29+DXJrTxkxL8W1+bOXhNOgbLfwZmJKQ+cTqI6SIuKX1tKjAoGBAJ4Bf5oNvSVsoilQXX78Wwu01ijBACOutPZ/WLnBTmu7YjwkVzoSuZXOIZkVQCFUQZCbJrJVsNJVSJF3u+odjNZOe3JHDuXkUXCCN4OPw4s9LEIbFWGF2BrAPSXUM1e3RtiCA0Hsr58DoxpeHd2j21QDm6khNvTifpdsVdpVSa/E';

    const APK = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAvVodBcIsAMYVMWnJ2Pw/vL9xXHo/oupKumN6lB5cO7tbkV8ljxiCbMky8YHxCpJ8MERsuLyJrV4IkxPx+y9QRDOxKgwNRlnyI88ZNY0KwWynhMhFRp8rcZZufojcAREmEDNxZkxCtQluoxjYL9NnGaZV1BB/KKpi78MRG76q2ubv4npddVKriCfhL4xkCAxfPvVnyuW3EwWfhQa5py4FdQ6cEDZH9VQ1yBcEkFYl/fLJJ64MTNCHUkTJDJPA0pOXHDo9d5vPV6vTWsDtNERwTrn08pUbQH9awfeYD5N6iVEVZHAL1tmW1ibMujnEtpMbYSHnsLLZ+47pLu179qtvGQIDAQAB';

    function cz()
    {
        $config = array(
            //应用ID,您的APPID。
            'app_id' => self::APPID,

            //商户私钥
            'merchant_private_key' => self::MPK,

            //同步跳转
            'return_url' => "http://" . $_SERVER['HTTP_HOST'] . "admin/task_user_pagepay/return_url",

            //编码格式
            'charset' => "UTF-8",

            //签名方式
            'sign_type' => "RSA2",

            //支付宝网关
            'gatewayUrl' => "https://openapi.alipay.com/gateway.do",

            //支付宝公钥,查看地址：https://openhome.alipay.com/platform/keyManage.htm 对应APPID下的支付宝公钥。
            'alipay_public_key' => self::APK,
        );

        //异步通知地址
        $config['notify_url'] = "http://" . $_SERVER['HTTP_HOST'] . "/" . url('admin/task_user_pagepay/notify');
        //同步跳转
        $config['return_url'] = "http://" . $_SERVER['HTTP_HOST']  . url('admin/task_user_pagepay/return_url');

        //商户订单号，商户网站订单系统中唯一订单号，必填
        $out_trade_no = trim($_POST['WIDout_trade_no']);

        //订单名称，必填
        $subject = trim($_POST['WIDsubject']);

        //付款金额，必填
        $total_amount = trim($_POST['WIDtotal_amount']);

        //商品描述，可空
        $body = trim($_POST['WIDbody']);

        //构造参数
        $payRequestBuilder = new AlipayTradePagePayContentBuilder();
        $payRequestBuilder->setBody($body);
        $payRequestBuilder->setSubject($subject);
        $payRequestBuilder->setTotalAmount($total_amount);
        $payRequestBuilder->setOutTradeNo($out_trade_no);

        $aop = new AlipayTradeService($config);

        $order = [
            'WIDout_trade_no' => trim($_POST['WIDout_trade_no']),
            'WIDsubject' => trim($_POST['WIDsubject']),
            'WIDtotal_amount' => trim($_POST['WIDtotal_amount']),
            'WIDbody' => trim($_POST['WIDbody']),
            'create_time' => time(),
            'user_id' => session("ADMIN_ID"),
            'type' => $_POST['type'],
        ];
        db('task_user_order')->insert($order);

        $response = $aop->pagePay($payRequestBuilder, $config['return_url'], $config['notify_url']);

        //输出表单
        var_dump($response);
    }

    function notify()
    {

        $config = array(
            //应用ID,您的APPID。
            'app_id' => self::APPID,
            //商户私钥
            'merchant_private_key' => self::MPK,
            //编码格式
            'charset' => "UTF-8",
            //签名方式
            'sign_type' => "RSA2",

            //支付宝网关
            'gatewayUrl' => "https://openapi.alipay.com/gateway.do",

            //支付宝公钥,查看地址：https://openhome.alipay.com/platform/keyManage.htm 对应APPID下的支付宝公钥。
            'alipay_public_key' => self::APK,
        );
        //异步通知地址
        $config['notify_url'] = "http://" . $_SERVER['HTTP_HOST'] . "/" . url('admin/task_user_pagepay/notify');
        //同步跳转
        $config['return_url'] = "http://" . $_SERVER['HTTP_HOST']  . url('admin/task_user_pagepay/return_url');

        $arr = $_POST;

        $alipaySevice = new AlipayTradeService($config);
        $alipaySevice->writeLog(var_export($_POST, true));
        $result = $alipaySevice->check($arr);

        if ($result) {//验证成功

            $out_trade_no = $_POST['out_trade_no'];

            $morder = db('task_user_order');
            $order_info = $morder->where(['WIDout_trade_no' => $out_trade_no])->find();
            if ($order_info == null || $order_info['is_true'] == 1) {
                echo "success";
                exit;
            }
            $total_amount = $_POST['WIDtotal_amount'];
            if ($order_info['money'] != number_format($total_amount, 2)) {
                return;
            }

            $trade_no = $_POST['trade_no'];

            //交易状态
            $trade_status = $_POST['trade_status'];


            if ($_POST['trade_status'] == 'TRADE_FINISHED' || $_POST['trade_status'] == 'TRADE_SUCCESS') {
                $res = $morder->where(['WIDout_trade_no' => $out_trade_no])->setField('is_true', 1);
                if ($res !== false) {
                    $order_info['is_true'] = 1;
                    new \app\fenxiao\controller\TaskUserCzController($order_info['user_id'], $order_info);
                }
            }
            echo "success";    //请不要修改或删除
        } else {
            //验证失败
            echo "fail";
            exit;
        }

    }

    function return_url()
    {
        $config = array(
            //应用ID,您的APPID。
            'app_id' => self::APPID,
            //商户私钥
            'merchant_private_key' => self::MPK,
            //编码格式
            'charset' => "UTF-8",
            //签名方式
            'sign_type' => "RSA2",

            //支付宝网关
            'gatewayUrl' => "https://openapi.alipay.com/gateway.do",

            //支付宝公钥,查看地址：https://openhome.alipay.com/platform/keyManage.htm 对应APPID下的支付宝公钥。
            'alipay_public_key' => self::APK,
        );
        //异步通知地址
        $config['notify_url'] = "http://" . $_SERVER['HTTP_HOST'] . "/" . url('admin/task_user_pagepay/notify');
        //同步跳转
        $config['return_url'] = "http://" . $_SERVER['HTTP_HOST']. url('admin/task_user_pagepay/return_url');
        $arr = $_GET;
        $alipaySevice = new AlipayTradeService($config);
        $result = $alipaySevice->check($arr);
        if ($result) {//验证成功

            //商户订单号
            $out_trade_no = htmlspecialchars($_GET['out_trade_no']);
            $morder = db('task_user_order');
            $order_info = $morder->where(['WIDout_trade_no' => $out_trade_no])->find();
            if ($order_info == null || $order_info['is_true'] == 1) {
                echo "success";
                exit;
            }

            $total_amount = $_GET['total_amount'];
            if ($order_info['WIDtotal_amount'] != number_format($total_amount, 2)) {
                return;
            }


            $res = $morder->where(['WIDout_trade_no' => $out_trade_no])->setField('is_true', 1);
            if ($res !== false) {
                $order_info['is_true'] = 1;
                new \app\fenxiao\controller\TaskUserCzController($order_info['user_id'], $order_info);
            }

            //支付宝交易号
            $trade_no = htmlspecialchars($_GET['trade_no']);
            $url = 'http://'.$_SERVER['HTTP_HOST'].'/admin/index/index.html#/admin/task_user/info.html';
            return $this->success('充值成功', $url);

        } else {
            //验证失败
            echo "验证失败";
        }

    }

}
