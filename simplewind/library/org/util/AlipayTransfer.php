<?php

namespace library\org\util;


class AlipayTransfer
{
    private $appId = 'appid';
    private $rsaPrivateKey = '私钥';
    private $alipayrsaPublicKey = "支付宝公钥";
    private $payer_name = "尉氏后台";
    private $aop;
    public  function __construct()
    {
        $this->appId = '2019061965610440';//appid
        $this->rsaPrivateKey = 'MIIEowIBAAKCAQEAoYsGF1fv/mi6uLjH3Rj2LLhe9bqioDiuMoAjivJJsf28e1wU
dfdjO+64PaWEH4zHInTER2aZX1+6FIpGXu3GUzq1jDG22E/gHvn+zT97BQva3Iwh
Ysa7+1ybI8ePAmplsrJBG4IQLAgDCu4lC3DDpJjYjs18AEJYaAhpnMKqdvybNoKr
lmNA5LtBAfxmVzecctia7mHeeylR6IMpdbuCALcYarHdDeoglwQNzJy88KcMpFA+
sK8sBlWUlK1eQ07Ia6dY+jglTF1Wl3wbMIE3tQTqJJcuHMHwfS40tqzDHTNUVwOm
ITJMIYMjwdTMF5zyFz6Ob+C06qQSQev5ak8wgwIDAQABAoIBAEw499/N22y0Z94/
OfbmD0ocmJnjvVZSSEeF1L98AS/d5LBkSzc6SnV99ysHTSdB2rg0VmTGUXoCBiAo
+nlSQjEFU6JZ1seMMNkM5qBb4qUH6fYEnMApu4soL/+a6qyeHWxK1ZOwNAdCpITk
x0/1niqtAAmphWft5h74UTTpWVuYp1HKtnUb19ccRKLCEl+sPM6Px4xE1nhm2Vb5
Bbkxax+WlQbYoV0BqzQjUE6r4XoB4yd0NX0RWzOwNxdV8vvBdCBU51yUX7aWo+ci
A10IyQVerA/qMXC65CaETluiK0Kg2EgJcIJ0NQi7KgSn0d7yp4kOFkX6cWD2UNIk
h3LIAFkCgYEA1ck8xBjox68C8GPx96nUMwzXZVfpJmCTiRhMkSK69zrXKjRXshgO
PTmcRBsY5069iWO01F0d7iSeQb/JfDsAZNwxHEQ05cRkcEHH0Sq4q4QbtCr12uVZ
CxqCppuu0YOUCy7Mg0SjY9jG5ZT1vshqPcsV/ov9RiLrxonEMJdFBn8CgYEAwXDu
lEh8A4NSrAm7R38lqm3Bxw82T2btjnvNJxQ/uvAdXpD5EOkZ70ml+SWJ07Z3CNVe
+1lhVt/f8tg54w+6gB+Zzf80yxex0LzbTV3kP1j5SupJqwMNjxXsaTEImWa5bjA5
HlehcovGJWx19W+L7WXIrz/EV/HAxl8oQn4nu/0CgYBIxSsuBNyXu/bgJOUkTGay
ydg34ui164HM1LybWpsRtLGw6AhB0vZl2MpcVGzxr1fNAGd5MgkSGtzTvJi09NB5
hIoi+QSYgXU+0OVXSZd6qolTlpwBWrgok2mNlMi5AHTQKanrtN15Cz0IwN+1hTrE
tfBSqQwZZ6Gh/xsM/zC7uQKBgQCvZFQOAwLetI2bC1/RXcmrE8VlVbeqmuq+DCZh
TozSVLBsdApAePpY6nAgzRaA7apUShLK16nYeTi3GbKy3Cn/zadJDiKyGpPRbcty
BLXVcjjm4jNVaXk7yWcHobvoSynKbNL9Xfs2vuE0QXlaxn8bCvTBYZIHI13k/5aj
Y3tniQKBgGzJPQIk5RICsCJVz7vUak10VX1iJqdEBDRkrYprvKf4Pka7iLNSA0CG
GSfEgaSPFN82H/pJvgAS2q948LtJ0oVPTm/mNCUcp1PTt7wdu661Q+5zTg4bm1oF
8zMGNOzf2HLqBtsmKVU9vM0NozxcXd+i4aunUAeJw0MFjMK92wLF'; //私钥
        $this->alipayrsaPublicKey = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAhFejq5lWhsdDgJwhaxgTkNnORqBrWMtW6FG2yWyq3QG/2gtaeEW2M3SR5Tl2neblEaMSqVYAwxr4S8P6XD8I5NQJFr2sQhxYVn3nuwtRdDSJqY0jYmvOCD+UGuZzXnWm6ih6MB7+xm87Wo00lBFzI537wJfKLRJkKQ3jAQ1MFILWn55U53b0zPNNTCaB+lltwqsJXt0Zpa5KRKg64KtfNe6mwsGgXCNFmmtTBegiSVO6HT42z1QWTkFQz6FNf0/Rsye/s0yO2HuLQ2OZmWTXxv7v6Xu11cbLg3LZLvNcwx/PlUTfx4QwXcsfvSVbMb86tIpK8WRp4nDMu3zeaDwZ3QIDAQAB';//支付宝公钥
        //引入单笔转账sdk
        Vendor('Alipayaop.AopSdk');
    }

    public  function init_aop_config()
    {
        $this->aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $this->aop->appId = $this->appId;
        $this->aop->rsaPrivateKey = $this->rsaPrivateKey;
        $this->aop->alipayrsaPublicKey=$this->alipayrsaPublicKey;
        $this->aop->apiVersion = '1.0';
        $this->aop->signType = 'RSA2';
        $this->aop->postCharset='UTF-8';
        $this->aop->format='json';
    }

    /**
     * 单笔转账接口
     * @param $order_number 订单号
     * @param $pay_no       转账账号
     * @param $pay_name     转账用户名
     * @param $amount       转账金额
     * @param $memo         备注
     */
    public function transfer($order_number,$pay_no,$pay_name,$amount,$memo)
    {
        //存入转账日志
        //$this->transferLog($order_number,$pay_no,$pay_name,$amount);
        $this->aop = new \AopClient();
        $this->init_aop_config();
        //导入请求
        $request = new \AlipayFundTransToaccountTransferRequest ();
        $request->setBizContent("{" .
            "\"out_biz_no\":\"".$order_number."\"," .//商户生成订单号
            "\"payee_type\":\"ALIPAY_LOGONID\"," .//收款方支付宝账号类型
            "\"payee_account\":\"".$pay_no."\"," .//收款方账号
            "\"amount\":\"".$amount."\"," .//总金额
            "\"payer_show_name\":\"".$this->payer_name."\"," .//付款方账户
            "\"payee_real_name\":\"".$pay_name."\"," .//收款方姓名
            "\"remark\":\"".$memo."\"" .//转账备注
            "}");
        $result = $this->aop->execute ( $request);
        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $result->$responseNode->code;
        $resultSubMsg = $result->$responseNode->sub_msg;
        //修改转账日志
        if(!empty($resultCode)&&$resultCode == 10000){
            return true;
        } else {
            return $resultSubMsg;
        }
    }

    /**
     * 存取日志
     */
    private function transferLog($order_number,$pay_no,$pay_name,$amount)
    {
        $data['order_number'] = $order_number;
        $data['pay_no'] = $pay_no;
        $data['pay_name'] = $pay_name;
        $data['amount'] = $amount;
        $data['create_time'] = time();
        M('AlipayTransferLog')->add($data);
    }

    /**
     * 修改日志
     */
    private function edit_transferLog($order_number,$result_code,$sub_msg)
    {
        $model = D("AlipayTransferLog");
        $where['order_number'] = $order_number;
        $result = $model->where($where)->order('create_time desc')->find();
        if ($result_code == 10000)
        {
            $result['status'] = 1;
            $sub_msg = 'success';
        }
        else
        {
            $result['status'] = 2;
        }
        $result['memo'] = $sub_msg;
        $result['update_time'] = time();
        M('AlipayTransferLog')->save($result);
    }

    /**
     * 查单接口
     */
    public function query($order_number)
    {
        $this->aop = new \AopClient ();
        //配置参数
        $this->init_aop_config();
        $request = new \AlipayFundTransOrderQueryRequest ();
        $request->setBizContent("{" .
            "\"out_biz_no\":\"".$order_number."\"" .
            "  }");
        $result = $this->aop->execute ( $request);
        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $result->$responseNode->code;
        if(!empty($resultCode)&&$resultCode == 10000){
            $res_arr['code'] = '00';
            $res_arr['data'] = $result;
        } else {
            $res_arr['code'] = '-1';
        }
        return $res_arr;
    }
}
