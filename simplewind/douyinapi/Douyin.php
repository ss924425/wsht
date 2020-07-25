<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 小夏 < 449134904@qq.com>
// +----------------------------------------------------------------------
namespace Douyinapi;

class Douyin
{

    public function __construct($aa)
    {
        header('Content-Type:text/html; charset=UTF-8');
        date_default_timezone_set('PRC');
        $uid = $aa;
        if (empty($uid)) {
            exit('{"code":0,"data":null,msg:"请设置UID"}');
        }
    }

    static function RmD($uid)
    {
        try {
            $ua = 'Mozilla/5.0 (Linux; Android 7.0; EVA-AL10 Build/HUAWEIEVA-AL10; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/62.0.3202.84 Mobile Safari/537.36 aweme_710 JsSdk/1.0 NetType/WIFI Channel/tianzhuo_dy_dsg app_version/7.1.0 ByteLocale/zh-Hans-CN Region/CN';
            $ret = self::http_request("https://www.dyshortvideo.com/share/video/" . $uid, null, $ua);
            $tk = self::getmidstr($ret, 'dytk: "', '"', 7, 7);

            if ($tk != "") {
                $ret = self::http_request("https://www.iesdouyin.com/web/api/v2/aweme/iteminfo/?item_ids=" . $uid . "&dytk=" . $tk, null, $ua);
                $arr = (array)json_decode($ret, true);

                $zan = isset($arr['item_list'][0]['statistics']['digg_count']) ? $arr['item_list'][0]['statistics']['digg_count'] : 0;
                $ping = isset($arr['item_list'][0]['statistics']['comment_count']) ? $arr['item_list'][0]['statistics']['comment_count'] : 0;
                $tm = isset($arr['item_list'][0]['video']['duration']) ? $arr['item_list'][0]['video']['duration'] : 0;

//            echo '{"code":1,"data":{"赞":' . $zan .
//                ',"评论":' . $ping . ',"时长":' . $tm . '},"msg":"success"}';
                $data = [
                    'zan' => $zan,
                    'ping' => $ping,
                    'tm' => $tm
                ];
                return $data;

            } else {
                return '{"code":0,"data":null,"msg":"fail"}';
            }
        } catch (\Exception $e) {
            return '{"code":0,"data":null,"msg":"' . $e->getMessage() . '"}';
        }
    }

    static function http_request($url, $data = null, $header = null)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_HTTPGET, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
        }
        curl_setopt($curl, CURLOPT_USERAGENT, $header);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        $output = curl_exec($curl);

        curl_close($curl);
        return $output;
    }

    static function RmFun($url, $data = null, $header = null)
    {

        $curl = curl_init();
        if (!empty($header)) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        }
        curl_setopt($curl, CURLOPT_HEADER, 1);//返回response头部信息
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_HTTPGET, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        $output = curl_exec($curl);
        echo self::getmidstr($output, "https", "\r\n");
        echo "</br>";
        curl_close($curl);
        return $output;
    }

    static function getmidstr($str, $a, $b, $n, $m)
    {
        $num1 = strpos($str, $a);
        $temp1 = "";
        $temp2 = "";
        $temp3 = "";
        if ($num1 > 0) {
            $num2 = strpos($str, $b, $num1 + sizeof($a) + $n);
            if ($num2 > $num1) {
                return substr($str, $num1 + $n, $num2 - $num1 - $m);
            } else {
                return "";
            }
        } else {
            return "";
        }

    }
}