<?php
namespace app\native\controller;
use think\Exception;
use think\Loader;
use think\Log;


require_once "WxPayConfigInterfaceController.php";


class WxPayNativePayController
{

    /**
     * SDK版本号
     * @var string
     */
    public static $VERSION = "3.0.10";


	/**
	 * 
	 * 生成直接支付url，支付url有效期为2小时,模式二
	 * @param UnifiedOrderInput $input
	 */
	public function GetPayUrl($input)
	{
		if($input->GetTrade_type() == "NATIVE")
		{
			try{

				$config = new WxPayConfigController();
                $result = self::unifiedOrder($config,$input);
				return $result;
			} catch(Exception $e) {
                Log::error('参数异常'.$e->getMessage());
			}
		}
		return false;
	}

    public static function unifiedOrder($config, $inputObj, $timeOut = 6)
    {

        $e = new WxPayExceptionController();
        $url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
        //检测必填参数
        if(!$inputObj->IsOut_trade_noSet()) {
            throw new $e("缺少统一支付接口必填参数out_trade_no！");
        }else if(!$inputObj->IsBodySet()){
            throw new $e("缺少统一支付接口必填参数body！");
        }else if(!$inputObj->IsTotal_feeSet()) {
            throw new $e("缺少统一支付接口必填参数total_fee！");
        }else if(!$inputObj->IsTrade_typeSet()) {
            throw new $e("缺少统一支付接口必填参数trade_type！");
        }

        //关联参数
        if($inputObj->GetTrade_type() == "JSAPI" && !$inputObj->IsOpenidSet()){
            throw new $e("统一支付接口中，缺少必填参数openid！trade_type为JSAPI时，openid为必填参数！");
        }
        if($inputObj->GetTrade_type() == "NATIVE" && !$inputObj->IsProduct_idSet()){
            throw new $e("统一支付接口中，缺少必填参数product_id！trade_type为JSAPI时，product_id为必填参数！");
        }

        //异步通知url未设置，则使用配置文件中的url
        if(!$inputObj->IsNotify_urlSet() && $config->GetNotifyUrl() != ""){
            $inputObj->SetNotify_url($config->GetNotifyUrl());//异步通知url
        }

        $inputObj->SetAppid($config->GetAppId());//公众账号ID
        $inputObj->SetMch_id($config->GetMerchantId());//商户号
        $inputObj->SetSpbill_create_ip($_SERVER['REMOTE_ADDR']);//终端ip
        $inputObj->SetNonce_str(self::getNonceStr());//随机字符串

        //签名
        $inputObj->SetSign($config);
        $xml = $inputObj->ToXml();
        $startTimeStamp = self::getMillisecond();//请求开始时间
        $response = self::postXmlCurl($config, $xml, $url, false, $timeOut);

        $result = WxPayResultsController::Init($config, $response);
        self::reportCostTime($config, $url, $startTimeStamp, $result);//上报请求花费时间

        return $result;
    }

    /**
     *
     * 产生随机字符串，不长于32位
     * @param int $length
     * @return 产生的随机字符串
     */
    public static function getNonceStr($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str ="";
        for ( $i = 0; $i < $length; $i++ )  {
            $str .= substr($chars, mt_rand(0, strlen($chars)-1), 1);
        }
        return $str;
    }


    /**
     * 获取毫秒级别的时间戳
     */
    private static function getMillisecond()
    {
        //获取毫秒的时间戳
        $time = explode ( " ", microtime () );
        $time = $time[1] . ($time[0] * 1000);
        $time2 = explode( ".", $time );
        $time = $time2[0];
        return $time;
    }


    /**
     * 以post方式提交xml到对应的接口url
     *
     * @param WxPayConfigInterface $config  配置对象
     * @param string $xml  需要post的xml数据
     * @param string $url  url
     * @param bool $useCert 是否需要证书，默认不需要
     * @param int $second   url执行超时时间，默认30s
     * @throws $e
     */
    private static function postXmlCurl($config, $xml, $url, $useCert = false, $second = 30)
    {
        $e = new WxPayExceptionController();
        $ch = curl_init();
        $curlVersion = curl_version();
        $ua = "WXPaySDK/".self::$VERSION." (".PHP_OS.") PHP/".PHP_VERSION." CURL/".$curlVersion['version']." "
            .$config->GetMerchantId();

        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);

        $proxyHost = "0.0.0.0";
        $proxyPort = 0;
        $config->GetProxy($proxyHost, $proxyPort);
        //如果有配置代理这里就设置代理
        if($proxyHost != "0.0.0.0" && $proxyPort != 0){
            curl_setopt($ch,CURLOPT_PROXY, $proxyHost);
            curl_setopt($ch,CURLOPT_PROXYPORT, $proxyPort);
        }
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,TRUE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,2);//严格校验
        curl_setopt($ch,CURLOPT_USERAGENT, $ua);
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

        if($useCert == true){
            //设置证书
            //使用证书：cert 与 key 分别属于两个.pem文件
            //证书文件请放入服务器的非web目录下
            $sslCertPath = "";
            $sslKeyPath = "";
            $config->GetSSLCertPath($sslCertPath, $sslKeyPath);
            curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
            curl_setopt($ch,CURLOPT_SSLCERT, $sslCertPath);
            curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
            curl_setopt($ch,CURLOPT_SSLKEY, $sslKeyPath);
        }
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        //运行curl
        $data = curl_exec($ch);
        //返回结果
        if($data){
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            curl_close($ch);
            throw new $e("curl出错，错误码:$error");
        }
    }

    /**
     *
     * 上报数据， 上报的时候将屏蔽所有异常流程
     * @param WxPayConfigInterface $config  配置对象
     * @param string $usrl
     * @param int $startTimeStamp
     * @param array $data
     */
    private static function reportCostTime($config, $url, $startTimeStamp, $data)
    {
        //如果不需要上报数据
        $reportLevenl = $config->GetReportLevenl();
        if($reportLevenl == 0){
            return;
        }
        //如果仅失败上报
        if($reportLevenl == 1 &&
            array_key_exists("return_code", $data) &&
            $data["return_code"] == "SUCCESS" &&
            array_key_exists("result_code", $data) &&
            $data["result_code"] == "SUCCESS")
        {
            return;
        }

        //上报逻辑
        $endTimeStamp = self::getMillisecond();
        $objInput = new WxPayReportController();
        $objInput->SetInterface_url($url);
        $objInput->SetExecute_time_($endTimeStamp - $startTimeStamp);
        //返回状态码
        if(array_key_exists("return_code", $data)){
            $objInput->SetReturn_code($data["return_code"]);
        }
        //返回信息
        if(array_key_exists("return_msg", $data)){
            $objInput->SetReturn_msg($data["return_msg"]);
        }
        //业务结果
        if(array_key_exists("result_code", $data)){
            $objInput->SetResult_code($data["result_code"]);
        }
        //错误代码
        if(array_key_exists("err_code", $data)){
            $objInput->SetErr_code($data["err_code"]);
        }
        //错误代码描述
        if(array_key_exists("err_code_des", $data)){
            $objInput->SetErr_code_des($data["err_code_des"]);
        }
        //商户订单号
        if(array_key_exists("out_trade_no", $data)){
            $objInput->SetOut_trade_no($data["out_trade_no"]);
        }
        //设备号
        if(array_key_exists("device_info", $data)){
            $objInput->SetDevice_info($data["device_info"]);
        }

        try{
            self::report($config, $objInput);
        } catch (Exception $e){
            //不做任何处理
        }
    }

    /**
     *
     * 测速上报，该方法内部封装在report中，使用时请注意异常流程
     * WxPayReport中interface_url、return_code、result_code、user_ip、execute_time_必填
     * appid、mchid、spbill_create_ip、nonce_str不需要填入
     * @param WxPayConfigInterface $config  配置对象
     * @param WxPayReport $inputObj
     * @param int $timeOut
     * @throws $e
     * @return 成功时返回，其他抛异常
     */
    public static function report($config, $inputObj, $timeOut = 1)
    {
        $e =  new WxPayExceptionController();
        $url = "https://api.mch.weixin.qq.com/payitil/report";
        //检测必填参数
        if(!$inputObj->IsInterface_urlSet()) {
            throw new $e("接口URL，缺少必填参数interface_url！");

        } if(!$inputObj->IsReturn_codeSet()) {
        throw new $e("返回状态码，缺少必填参数return_code！");
    } if(!$inputObj->IsResult_codeSet()) {
        throw new $e("业务结果，缺少必填参数result_code！");
    } if(!$inputObj->IsUser_ipSet()) {
        throw new $e("访问接口IP，缺少必填参数user_ip！");
    } if(!$inputObj->IsExecute_time_Set()) {
        throw new $e("接口耗时，缺少必填参数execute_time_！");
    }
        $inputObj->SetAppid($config->GetAppId());//公众账号ID
        $inputObj->SetMch_id($config->GetMerchantId());//商户号
        $inputObj->SetUser_ip($_SERVER['REMOTE_ADDR']);//终端ip
        $inputObj->SetTime(date("YmdHis"));//商户上报时间
        $inputObj->SetNonce_str(self::getNonceStr());//随机字符串

        $inputObj->SetSign($config);//签名
        $xml = $inputObj->ToXml();

        $startTimeStamp = self::getMillisecond();//请求开始时间
        $response = self::postXmlCurl($config, $xml, $url, false, $timeOut);
        return $response;
    }
}