<?php

namespace app\admin\controller;

use think\Controller;

class TestController extends Controller
{

    function test()
    {
        $taked = db('self_task_receive')->alias('a')
            ->join('self_task b', 'b.id = a.taskid')
            ->where(array('a.status' => 1, 'a.taskid' => 16539))
            ->field("a.*,b.continueid,b.continue ,b.id AS sid,a.status as sstatus,b.start,b.end,a.taskid,b.sortid")
            ->select()
            ->toArray();
        dump($taked);die;
    }
    // 把链接或者文字链接转成从林下单id
    static function aa($str='')
    {
        if(empty($str)){exit('{"code":0,"data":null,"msg":"请设置str"}');}
        preg_match_all("/https:[\/]{2}[a-z]+[.]{1}[a-z\d\-]+[.]{1}[a-z\d]*[\/]*[A-Za-z\d]*[\/]*[A-Za-z\d]*/",$str,$data);
        $urlstr = self::http_request($data[0][0]);
        $newstr = mb_substr($urlstr,47,19);
        return $newstr;
    }
    // 将从林下单id拼接成为跳转url
    static function aaurl($str='')
    {
        if(empty($str)){exit('{"code":0,"data":null,"msg":"请设置str"}');}
        preg_match_all("/https:[\/]{2}[a-z]+[.]{1}[a-z\d\-]+[.]{1}[a-z\d]*[\/]*[A-Za-z\d]*[\/]*[A-Za-z\d]*/",$str,$data);
        $urlstr = self::http_request($data[0][0]);
        $newstr = mb_substr($urlstr,9,57);
        return $newstr;
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

    // 获取点赞
    static function RmD($uid='')
    {
        try {
            $ua = 'Mozilla/5.0 (Linux; Android 7.0; EVA-AL10 Build/HUAWEIEVA-AL10; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/62.0.3202.84 Mobile Safari/537.36 aweme_710 JsSdk/1.0 NetType/WIFI Channel/tianzhuo_dy_dsg app_version/7.1.0 ByteLocale/zh-Hans-CN Region/CN';
            $ret = self::http_request("https://www.dyshortvideo.com/share/video/" . $uid, null, $ua);
            $tk = self::getmidstr($ret, 'dytk: "', '"', 7, 7);
           
            if ($tk != "") {
//                $ret = self::http_request("https://www.iesdouyin.com/web/api/v2/aweme/iteminfo/?item_ids=" . $uid . "&dytk=" . $tk, null, $ua);
                $ret = self::http_request("https://www.iesdouyin.com/web/api/v2/aweme/iteminfo/?item_ids=" . $uid . "&dytk=" . $tk, null, $ua);
                https://live.kuaishou.com/u/3xrccqdwhspr3zs/3xkc753467vdiq9?did=web_29072713f2a142f49971dd2a63645ee5

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
                $data = json_encode($data);
                return $data;

            } else {
                return null;
            }
        } catch (\Exception $e) {
            return null;
        }
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

    // 获取关注
    static function RmSelf($uid)
    {
        @$ret = self::RmFun($uid);
        if (!empty($ret)) {
            @$ua = 'Mozilla/5.0 (Linux; Android 7.0; EVA-AL10 Build/HUAWEIEVA-AL10; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/62.0.3202.84 Mobile Safari/537.36 aweme_710 JsSdk/1.0 NetType/WIFI Channel/tianzhuo_dy_dsg app_version/7.1.0 ByteLocale/zh-Hans-CN Region/CN';
            $ret = str_replace("https", "http", $ret);
            $ret = str_replace("http", "https", $ret);
            $ret = self::http_request($ret);
            if (strrpos($ret, "抖音")) {
                $gz = self::changedata($ret, '/icon iconfont follow-num.+关注/U');
                $fs = self::changedata($ret, '/"text">关注<\/span>.+icon iconfont follow-num.+粉丝/U');
                $zz = self::changedata($ret, '/粉丝<\/span>.+icon iconfont follow-num.+赞/U');
                $zp = self::changedata($ret, '/作品<span.+喜欢/U');
                $xh = self::changedata($ret, '/喜欢<span.+<\/div>/U');
                $data = [
                    'gz' => $gz,
                    'fs' => $fs,
                    'zz' => $zz,
                    'zp' => $zp,
                    'xh' => $xh,
                ];
                $data = json_encode($data);
                return $data;
//                exit('{"code":1,"data":{"关注":' . $gz . ',"粉丝":' . $fs . ',"赞":' . $zz . ',"作品:' . $zp . ',"喜欢":' . $xh . '},msg:"success"}');
            } else {
                return null;
            }

        } else {
            return null;
        }
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
        curl_close($curl);
        return self::getmidstr($output, "http", "\r\n", 0, 0);
    }

    static function changedata($str, $str2)
    {
        $tmp = '{"xe602":"num_","xe603":"num_1","xe604":"num_2","xe605":"num_3","xe606":"num_4","xe607":"num_5",' .
            '"xe608":"num_6","xe609":"num_7","xe60a":"num_8","xe60b":"num_9","xe60c":"num_4","xe60d":"num_1",' .
            '"xe60e":"num_","xe60f":"num_5","xe610":"num_3","xe611":"num_2","xe612":"num_6","xe613":"num_8",' .
            '"xe614":"num_9","xe615":"num_7","xe616":"num_1","xe617":"num_3","xe618":"num_","xe619":"num_4",' .
            '"xe61a":"num_2","xe61b":"num_5","xe61c":"num_8","xe61d":"num_9","xe61e":"num_7","xe61f":"num_6"}';
        $tmp2 = '{"num_":1,"num_1":0,"num_2":3,"num_3":2,"num_4":4,"num_5":5,"num_6":6,"num_7":9,"num_8":7,"num_9":8}';
        @$arr = (array)json_decode($tmp, true);
        @$arr2 = (array)json_decode($tmp2, true);
        $tmp3 = "";
        preg_match($str2, $str, $t);
        //var_dump($t);
        if (!empty($t[0])) {
            preg_match_all('/&#([0-9 a-z]+);/', $t[0], $matches); //贪婪模式  &#xe603;&#xe604;&#xe605;
            //var_dump($matches);
            if (!empty($matches[1])) {
                for ($i = 0; $i < sizeof($matches[1]); $i++) {
                    foreach ($arr as $key => $value) {
                        if ($key == $matches[1][$i]) {
                            foreach ($arr2 as $key2 => $value2) {
                                if ($value == $key2) {
                                    $tmp3 = $tmp3 . $value2;
                                }
                            }
                        }
                    }
                }
            }
        }
        return $tmp3;
    }

}