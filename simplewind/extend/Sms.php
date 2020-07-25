<?php
class Sms
{
    const SEND_API_URL = 'https://dx.ipyy.net/sms.aspx?action=send';
    public $ErrorInfo;
    public function send($mobile, $content)
    {
       $sms = $smsSetting = cmf_get_option('sms_setting');
        $data = array(
            "userid" => $sms['userid'],
            "account" => $sms['account'],
            "password" => $sms['password'],
            "mobile" => $mobile, //string	手机号码每次只能提交1个号码
            "content" => $content . "【{$sms['sign']}】" //string	短信内容。
        );
        $result = $this->curl_post(self::SEND_API_URL, $data);
        $result = $this->xml2array($result);
        if ($result['returnstatus'] == 'Success') {
            return true;
        } else {
            $this->ErrorInfo = $result['message'];
            return false;
        }
    }

    private function xml2array($xml)
    {
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $values;
    }

    private function curl_post($url, $post_data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        $output = curl_exec($ch);
        if($output === false){
            file_put_contents('./smslog.txt',date('Y-m-d H:i:s') .curl_error($ch). PHP_EOL,FILE_APPEND);
            file_put_contents('./smslog.txt',date('Y-m-d H:i:s') .curl_errno($ch). PHP_EOL,FILE_APPEND);
        }
        curl_close($ch);
        return $output;
    }
}