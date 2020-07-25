<?php

namespace app\admin\controller;


class AlipayTransfer
{
    private $appId = 'appid';
    private $rsaPrivateKey = '私钥';
    private $alipayrsaPublicKey = "支付宝公钥";
    private $payer_name = "尉氏后台";
    private $aop;
    public $sign_type  = "RSA2";
    public  function __construct()
    {

        $pay_setting = cmf_get_option('pay_setting');
        $this->aliset = $pay_setting['alipay'];


        $this->appId = $this->aliset['appid']; //appid
        $this->rsaPrivateKey = file_get_contents(cmf_get_image_preview_url($this->aliset['rsa_private_key'])); //私钥

        $this->alipayrsaPublicKey = file_get_contents(cmf_get_image_preview_url($this->aliset['rsa_public_key'])); //支付宝公钥
        //引入单笔转账sdk
        import('.alipay.AopSdk', '', '.php');
    }
    public function init_aop_config()
    {
        $this->aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $this->aop->appId = $this->appId;
        $this->aop->rsaPrivateKey = $this->rsaPrivateKey;
        $this->aop->alipayrsaPublicKey = $this->alipayrsaPublicKey;
        $this->aop->apiVersion = '1.0';
        $this->aop->signType = 'RSA2';
        $this->aop->postCharset = 'UTF-8';
        $this->aop->format = 'json';
    }

    /**
     * 单笔转账接口
     * @param $order_number 订单号
     * @param $pay_no       转账账号
     * @param $pay_name     转账用户名
     * @param $amount       转账金额
     * @param $memo         备注
     */
    public function transfer($order_number, $pay_no, $pay_name, $amount, $memo)
    {
        //存入转账日志
        //$this->transferLog($order_number,$pay_no,$pay_name,$amount);
        import('.alipay.aop.AopClient', '', '.php');
        $this->aop = new \AopClient();
        //配置参数
        $this->init_aop_config();

        //导入请求
        $request = new \AlipayFundTransToaccountTransferRequest ();
        $request->setBizContent("{" .
            "\"out_biz_no\":\"" . $order_number . "\"," .//商户生成订单号
            "\"payee_type\":\"ALIPAY_LOGONID\"," .//收款方支付宝账号类型
            "\"payee_account\":\"" . $pay_no . "\"," .//收款方账号
            "\"amount\":\"" . $amount . "\"," .//总金额
            "\"payer_show_name\":\"" . $this->payer_name . "\"," .//付款方账户
            "\"payee_real_name\":\"" . $pay_name . "\"," .//收款方姓名
            "\"remark\":\"" . $memo . "\"" .//转账备注
            "}");

        $result = $this->aop->execute($request);

        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $result->$responseNode->code;
        $resultSubMsg = $result->$responseNode->msg;

        if (!empty($resultCode) && $resultCode == 10000) {
            return true;
        } else {
            return $resultSubMsg;
        }
    }

}
