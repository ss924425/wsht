<?php

use think\Db;

//前台发任务工具方法类，基于thinkphp5.0和thinkcmf5.0
class TbUtil
{

    static function deleteImage($attachment)
    {
        @unlink('./upload/' . cmf_asset_relative_url($attachment));
        return true;
    }

    //获取客户端IP
    static function getClientIp()
    {
        $ip = "";
        if (!empty($_SERVER["HTTP_CLIENT_IP"])) {
            $ip = $_SERVER["HTTP_CLIENT_IP"];
        }
        if (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
            $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
        }
        if (!empty($_SERVER["REMOTE_ADDR"])) {
            $ip = $_SERVER["REMOTE_ADDR"];
        }
        return $ip;
    }


    static function getRandom()
    {
        return time() . rand(10000, 99999);
    }

    static function getRand($arg1, $arg2)
    {
        $min = min($arg1, $arg2);
        $max = max($arg1, $arg2);
        return rand($min, $max);
    }


    //格式化时间,多久之前
    static function formatTime($time)
    {
        $difftime = time() - $time;

        if ($difftime < 60) {
            return $difftime . '秒前';
        }
        if ($difftime < 120) {
            return '1分钟前';
        }
        if ($difftime < 3600) {
            return intval($difftime / 60) . '分钟前';
        }
        if ($difftime < 3600 * 24) {
            return intval($difftime / 60 / 60) . '小时前';
        }
        if ($difftime < 3600 * 24 * 2) {
            return '昨天';
        }
        return intval($difftime / 60 / 60 / 24) . '天前';
    }

    //剩余时间
    static function lastTime($time, $secondflag = true, $isday = false)
    {

        $diff = strtotime($time)  - time();
     //   $diff = $time  - time();
        if ($diff <= 0) return '0天0时0分';
        $day = intval($diff / 24 / 3600);
        if ($isday) {
            return $day . '天';
        }
        $hour = intval(($diff % (24 * 3600)) / 3600);
        $minutes = intval(($diff % (24 * 3600)) % 3600 / 60);
        $second = $diff % 60;
        if ($secondflag) {
            return $day . '天' . $hour . '时' . $minutes . '分' . $second . '秒';
        } else {
            return $day . '天' . $hour . '时' . $minutes . '分';
        }
    }

    static function lock($tablename, $type = 'WRITE')
    {
        $sql = "LOCK TABLES " . $tablename . ' ' . $type;
        return Db::query($sql);
    }

    static function unlock()
    {
        $sql = "UNLOCK TABLES";
        return Db::query($sql);
    }


    //获取cookie 传入cookie名 //解决js与php的编码不一致情况。
    static function getCookie($str)
    {
        return urldecode($_COOKIE[$str]);
    }


    //共用先查询缓存数据
    static function getDataByCacheFirst($key, $name, $funcname, $valuearray)
    {
        $data = self::getCache($key, $name);

        if (empty($data)) {

            $data = call_user_func_array($funcname, $valuearray);
            self::setCache($key, $name, $data);
        }

        return $data;
    }

    //查询缓存
    static function getCache($key, $name)
    {
        if (empty($key) || empty($name)) return false;

        return cache('tstb:' . $key . ':' . $name);
    }

    //设置缓存
    static function setCache($key, $name, $value)
    {
        if (empty($key) || empty($name)) return false;
        $res = cache('tstb:' . $key . ':' . $name, $value);
        return $res;
    }

    //删除缓存
    static function deleteCache($key, $name)
    {
        if (empty($key) || empty($name)) return false;
        return cache('tstb:' . $key . ':' . $name, null);
    }


    //创建目录
    static function mkdirs($path)
    {
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        return is_dir($path);
    }

    // 删除目录及所有子文件
    function rmdirs($path, $clean = false)
    {
        if (!is_dir($path)) {
            return false;
        }
        $files = glob($path . '/*');
        if ($files) {
            foreach ($files as $file) {
                is_dir($file) ? self::rmdirs($file) : @unlink($file);
            }
        }
        return $clean ? true : @rmdir($path);
    }

    //截取字符串,截取start-end之间的,结果不包含start和end；
    static function cut($from, $start, $end, $lt = false, $gt = false)
    {
        $str = explode($start, $from);
        if (isset($str['1']) && $str['1'] != '') {
            $str = explode($end, $str['1']);
            $strs = $str['0'];
        } else {
            $strs = '';
        }
        if ($lt) {
            $strs = $start . $strs;
        }
        if ($gt) {
            $strs .= $end;
        }
        return $strs;
    }

    //处理空格
    static function trimWithArray($array)
    {
        if (!is_array($array)) {
            return trim($array);
        }
        foreach ($array as $k => $v) {
            $res[$k] = self::trimWithArray($v);
        }
        return $res;
    }
}