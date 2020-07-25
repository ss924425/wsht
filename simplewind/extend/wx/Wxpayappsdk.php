<?php
class Wxpayappsdk
{
    public $apikey;
    //转XML格式
    function createXml($arr) {
        return $this->arrayToXml($arr);
    }
    /**
     * 	作用：array转xml
     */
    function arrayToXml($arr) {
        $xml = "<xml>";
        foreach ($arr as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";

            } else {
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
            }

        }
        $xml .= "</xml>";
        return $xml;
    }

    /**
     * 	作用：将xml转为array
     */
    public function xmlToArray($xml) {
        //将XML转为array
        $array_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $array_data;
    }
    //封装签名算法
    function MakeSign($arr)
    {
        //签名步骤一：按字典序排序参数
        ksort($arr);
        $string = $this->ToUrlParams($arr);
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=" . $this->apikey;
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;
    }

    /**
     * 格式化参数格式化成url参数
     */
    function ToUrlParams($arr)
    {
        $buff = "";
        foreach ($arr as $k => $v) {
            if ($k != "sign" && $v != "" && !is_array($v)) {
                $buff .= $k . "=" . $v . "&";
            }
        }

        $buff = trim($buff, "&");
        return $buff;
    }


    //随机字符串(不长于32位)
    function getNonceStr($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }


    function curl($url, $post_data)
    {


        $headerArray = array(
            'Accept:application/json, text/javascript, */*',
            'Content-Type:application/x-www-form-urlencoded',
            'Referer:https://mp.weixin.qq.com/'
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        // 对认证证书来源的检查，0表示阻止对证书的合法性的检查。
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // 从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//关闭直接输出
        curl_setopt($ch, CURLOPT_POST, 1);//使用post提交数据
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);//设置 post提交的数据
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/30.0.1599.69 Safari/537.36');//设置用户代理
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headerArray);//设置头信息


        $loginData = curl_exec($ch);//这里会返回token，需要处理一下。

        return $loginData;

    }

    /**
     * 解析xml文档，转化为对象
     * @param  String $xmlStr xml文档
     * @return Object         返回Obj对象
     */
    function xmlToObject($xmlStr)
    {
        if (!is_string($xmlStr) || empty($xmlStr)) {
            return false;
        }
        // 由于解析xml的时候，即使被解析的变量为空，依然不会报错，会返回一个空的对象，所以，我们这里做了处理，当被解析的变量不是字符串，或者该变量为空，直接返回false
        libxml_disable_entity_loader(true);
        $postObj = json_decode(json_encode(simplexml_load_string($xmlStr, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        //将xml数据转换成对象返回
        return $postObj;
    }
}