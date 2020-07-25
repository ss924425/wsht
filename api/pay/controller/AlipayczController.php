<?php
namespace api\pay\controller;
use cmf\controller\RestBaseController;
use think\Controller;
use think\Db;
use think\Exception;
use think\Log;

class AlipayczController extends RestBaseController
{
    private $fileCharset          = "UTF-8";
    private $aliset;
    public $postCharset           = "UTF-8";
    public $rsaPrivateKeyFilePath = "";
    public $rsaPublicKeyFilePath  = "";
    public $sign_type             = "RSA2";
    public function __construct()
    {
        parent::__construct();
        $pay_setting = cmf_get_option('pay_setting');
        $this->aliset = $pay_setting['alipay'];
        $this->rsaPrivateKeyFilePath = cmf_get_image_preview_url($this->aliset['rsa_private_key']);
        $this->rsaPublicKeyFilePath = cmf_get_image_preview_url($this->aliset['rsa_public_key']);
        Log::error('rsaPrivateKeyFilePath--' . $this->rsaPrivateKeyFilePath);
        Log::error('rsaPublicKeyFilePath--' . $this->rsaPublicKeyFilePath);
    }
    /**
     * 获取支付宝支付字符串信息
     * @return [type] [description]
     */
    public function getorderinfo()
    {
        try {
            Log::info("getorderinfo-支付订单开始生成");
            $channel = input('pay_id');
            $money = input('money');
            $type = input('type',0); // 0 余额充值   1 保证金充值
            if ($channel != 'alipay' || $money < 0) {
                echo json_encode(['status' => 0, 'msg' => '支付数据错误']);
                exit;
            }
            $user_info = db('user')->find($this->userId);
            if (!$user_info) {
                echo json_encode(['status' => -1, 'msg' => "支付信息获取失败,请尝试重新登陆~"]);
                exit;
            }

            $order_info = $this->createOrder($money,$type);
            if (!$order_info) {
                echo json_encode(['status' => 0, 'msg' => '当前支付人数较多，请稍后重试']);
                exit;
            }
            Log::info("getorderinfo-支付订单生成成功-".json_encode($order_info));
            $params['app_id'] = $this->aliset['appid'];
            $params['method'] = "alipay.trade.app.pay";
            $params['format'] = "json";
            $params['charset'] = "utf-8";
            $params['timestamp'] = date("Y-m-d H:i:s");
            $params['version'] = "1.0";
            $params['notify_url'] = "http://" . $_SERVER['HTTP_HOST'] . "/api" . url('pay/Alipaycz/notify');
            $params['sign_type'] = $this->sign_type;
            $params['biz_content'] = json_encode([
                'body' => '充值',
                'subject' => '充值',
                'out_trade_no' => $order_info['order_sn'],
                'total_amount' => $order_info['money'],
                'product_code' => 'QUICK_MSECURITY_PAY',
                'disable_pay_channels' => 'creditCard',
            ], JSON_UNESCAPED_UNICODE);
            # 排序,签名,排序
            $str = $this->getSignContent($params);
            $params['sign'] = $this->sign($str, $params['sign_type']);
            foreach ($params as $key => $value) {
                if ($key != 'timestamp') {
                    $params[$key] = urlencode($value);
                }
            }
            $str = $this->getSignContent($params);
            Log::info("getorderinfo-支付签名生成成功-".$str);
            echo json_encode(['status' => 1, 'data' => $str]);
            exit;
        }catch (Exception $exception)
        {
            Log::error("Exception"-$exception->getMessage());
        }
    }

    public function createOrder($money,$type)
    {
        $order_sn = date('YmdHis');
        $res = db('user_addmoney_log')->where('order_sn',$order_sn)->find();
        if($res){
            return false;
        }
        $morder = db('user_addmoney_log');
        $data['order_sn'] = $order_sn;
        $data['user_id'] = $this->userId;
        $data['paytype'] = 2;
        $data['money'] = $money;
        $data['create_time'] = time();
        $data['is_true'] = 0;
        $data['type'] = $type;
        $res = $morder->insertGetId($data);
        return db('user_addmoney_log')->find($res);
    }

    public function notify()
    {
        try {
            Log::info("notify-支付回调开始");
            $arr = $_POST;
            $out_trade_no = $_POST['out_trade_no'];
            $morder = db('user_addmoney_log');
            $order_info = $morder->where(['order_sn' => $out_trade_no])->find();
            if ($order_info == null || $order_info['status'] == 1) {
                echo "success";
                exit;
            }
            //返回格式 {"gmt_create":"2018-09-08 13:03:08","charset":"utf-8","seller_email":"cqvyj2016@sina.com","subject":"测试app支付","sign":"gZydvv7bOkzwnkVlsPKky5EDy3BS4OL1NLwnFn64jYCa3geDwAAKiBkpGNI87AVddodixTCz9Ll9hA6T8SYK8xTCFq4VTdbXrW6RGEqVdDfdu75RBSyEflCuf5jkIZXdIxe1C5idvbCthzS7ICvbDp7eYInKFOEgaewrXhs\/ddoaMxeK2etpOJiRi3\/ioSJKGYdvU+ReKXYvWX3dg5+zaQjKp8vLnKjag4XDhBO6yB1xzQYam8jHfJgn\/ZGCAVFOWEHuAHhM2qasWXKZei+gT3OnRHnvcJDhVp0NzLWRJtFiHy02yuaLS+TlLKRcvb4fN+S4e4JWL4lzzPrG6DLUWQ==","body":"测试app支付","buyer_id":"2088212942567899","invoice_amount":"0.01","notify_id":"2018090800222130309067890520057262","fund_bill_list":"[{\"amount\":\"0.01\",\"fundChannel\":\"ALIPAYACCOUNT\"}]","notify_type":"trade_status_sync","trade_status":"TRADE_SUCCESS","receipt_amount":"0.01","app_id":"2018062160408670","buyer_pay_amount":"0.01","sign_type":"RSA2","seller_id":"2088411957695896","gmt_payment":"2018-09-08 13:03:08","notify_time":"2018-09-08 13:03:09","version":"1.0","out_trade_no":"20180621604086000000005","total_amount":"0.01","trade_no":"2018090822001467890528225092","auth_app_id":"2018062160408670","buyer_logon_id":"j.c***@foxmail.com","point_amount":"0.00","result_data":1}
            $check = $this->check($arr);
            if ($check) {
                Log::info("notify-支付回调验签成功");
                //交易金额
                $total_amount = $_POST['total_amount'];
                if ($order_info['money'] != number_format($total_amount, 2)) {
                    return;
                }
                $morder = db('user_addmoney_log');
                $order_info = $morder->where(['order_sn' => $out_trade_no])->find();
                if ($_POST['trade_status'] == 'TRADE_SUCCESS' || $_POST['trade_status'] == 'TRADE_FINISHED') { // 支付成功
                    $res = $morder->where(['order_sn' => $out_trade_no])->setField('is_true', 1);
                    file_put_contents('查看.txt', json_encode($order_info) . PHP_EOL, FILE_APPEND);
                    if ($res !== false) {
                        Log::info("notify-支付回调成功，开始处理业务");
                        //支付成功，开始处理业务
                        new \app\fenxiao\controller\CzController($order_info['user_id'], $order_info);
                    }
                }
                echo "success";
                exit;
            } else {
                Log::info("notify-支付回调验签失败".json_encode($check));
                echo "fail";
                exit;
            }
        } catch (Exception $exception) {
            Log::error("notify-支付回调异常-" . $exception->getMessage());
        }
    }

    public function check($arr)
    {
        import('.alipay.aop.AopClient', '', '.php');
        import('.alipay.aop.request.AlipayTradeAppPayRequest', '', '.php');
        $aop = new \AopClient();
        return $aop->rsaCheckV1($arr,$this->rsaPublicKeyFilePath,$this->sign_type);
    }

    /** rsaCheckV1 & rsaCheckV2
     *  验证签名
     *  在使用本方法前，必须初始化AopClient且传入公钥参数。
     *  公钥是否是读取字符串还是读取文件，是根据初始化传入的值判断的。
     **/
    public function rsaCheckV1($params, $rsaPublicKeyFilePath, $signType = 'RSA')
    {
        $sign = $params['sign'];
        $params['sign_type'] = null;
        $params['sign'] = null;
        return $this->verify($this->getSignContent($params), $sign, $rsaPublicKeyFilePath, $signType);
    }
    public function verify($data, $sign, $rsaPublicKeyFilePath, $signType = 'RSA')
    {

        if ($this->checkEmpty($this->rsaPublicKeyFilePath)) {
            $pubKey = $this->alipayrsaPublicKey;
            $res    = "-----BEGIN PUBLIC KEY-----\n" .
                wordwrap($pubKey, 64, "\n", true) .
                "\n-----END PUBLIC KEY-----";
        } else {
            //读取公钥文件
            $pubKey = file_get_contents($rsaPublicKeyFilePath);

            $pubKey    = "-----BEGIN PUBLIC KEY-----\n" .
                wordwrap($pubKey, 64, "\n", true) .
                "\n-----END PUBLIC KEY-----";

            //转换为openssl格式密钥
            $res = openssl_get_publickey($pubKey);
        }
        ($res) or file_put_contents('alipaynotify.txt','支付宝RSA公钥错误。请检查公钥文件格式是否正确'.PHP_EOL,FILE_APPEND);
        //调用openssl内置方法验签，返回bool值
        $result = FALSE;
        if ("RSA2" == $signType) {
            $result = (openssl_verify($data, base64_decode($sign), $res, OPENSSL_ALGO_SHA256)===1);
        } else {
            $result = (openssl_verify($data, base64_decode($sign), $res)===1);
        }
        file_put_contents('c.txt',intval($result));
        if (!$this->checkEmpty($this->rsaPublicKeyFilePath)) {
            //释放资源
            openssl_free_key($res);
        }
        return $result;
    }
    public function make_sign($it_b_pay, $service, $body, $out_trade_no, $partner, $_input_charset, $notify_url, $subject, $payment_type, $seller_id, $total_fee, $sign_type)
    {
        $params['it_b_pay']       = $it_b_pay;
        $params['service']        = $service;
        $params['body']           = $body;
        $params['out_trade_no']   = $out_trade_no;
        $params['partner']        = $partner;
        $params['_input_charset'] = $_input_charset;
        $params['notify_url']     = $notify_url;
        $params['subject']        = $subject;
        $params['payment_type']   = $payment_type;
        $params['seller_id']      = $seller_id;
        $params['total_fee']      = $total_fee;
        ksort($params);
        $sign = $this->generateSign($params, $sign_type);
        return $sign;
    }
    public function generateSign($params, $signType = "RSA")
    {
        return $this->sign($this->getSignContent($params), $signType);
    }
    protected function sign($data, $signType = "RSA")
    {
        if ($this->checkEmpty($this->rsaPrivateKeyFilePath)) {
            $priKey = $this->rsaPrivateKey;
            $res    = "-----BEGIN RSA PRIVATE KEY-----\n" .
                wordwrap($priKey, 64, "\n", true) .
                "\n-----END RSA PRIVATE KEY-----";
        } else {
            $priKey = file_get_contents($this->rsaPrivateKeyFilePath);
            $priKey    = "-----BEGIN RSA PRIVATE KEY-----\n" .
                wordwrap($priKey, 64, "\n", true) .
                "\n-----END RSA PRIVATE KEY-----";
            $res    = openssl_get_privatekey($priKey);
        }
        ($res) or die('您使用的私钥格式错误，请检查RSA私钥配置');

        if ("RSA2" == $signType) {
            openssl_sign($data, $sign, $res, OPENSSL_ALGO_SHA256);
        } else {
            openssl_sign($data, $sign, $res);
        }

        if (!$this->checkEmpty($this->rsaPrivateKeyFilePath)) {
            openssl_free_key($res);
        }
        $sign = base64_encode($sign);
        return $sign;
    }
    protected function getSignContent($params)
    {
        ksort($params);
        $stringToBeSigned = "";
        $i                = 0;
        foreach ($params as $k => $v) {
            if (false === $this->checkEmpty($v) && "@" != substr($v, 0, 1)) {

                // 转换成目标字符集
                $v = $this->characet($v, $this->postCharset);

                if ($i == 0) {
                    $stringToBeSigned .= "$k" . "=" . "$v";
                } else {
                    $stringToBeSigned .= "&" . "$k" . "=" . "$v";
                }
                $i++;
            }
        }
        unset($k, $v);
        return $stringToBeSigned;
    }
    /**
     * 校验$value是否非空
     *  if not set ,return true;
     *    if is null , return true;
     **/
    protected function checkEmpty($value)
    {
        if (!isset($value)) {
            return true;
        }

        if ($value === null) {
            return true;
        }

        if (trim($value) === "") {
            return true;
        }

        return false;
    }
    /**
     * 转换字符集编码
     * @param $data
     * @param $targetCharset
     * @return string
     */
    public function characet($data, $targetCharset)
    {

        if (!empty($data)) {
            $fileType = $this->fileCharset;
            if (strcasecmp($fileType, $targetCharset) != 0) {
                $data = mb_convert_encoding($data, $targetCharset, $fileType);
                //              $data = iconv($fileType, $targetCharset.'//IGNORE', $data);
            }
        }

        return $data;
    }
}