<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace api\admin\controller;

use cmf\controller\RestBaseController;
use think\Db;
use think\Validate;

class UmiController extends RestBaseController
{
    //有米积分
    public function umi_info()
    {

        $request = $this->request->param();
        //如果不存在则写入记录


        $add['order'] = $request['order'];
        $add['app'] = $request['app'];
        $add['ad'] = $request['ad'];
        $add['pkg'] = $request['pkg'];
        $add['user'] = $request['user'];
        $add['chn'] = $request['chn'];
        $add['points'] = $request['points'];
        $add['price'] = $request['price'];
        $add['time'] = $request['time'];
        $add['device'] = $request['device'];
        $add['adid'] = $request['adid'];
        $add['trade_type'] = $request['trade_type'];
        $add['sig'] = $request['sig'];
        $add['create_time'] = time();
        $add['notes'] = json_encode($request);

        $order = db('umi_order') -> where(['order' => $request['order']]) -> find();
        if($add['order'] && !$order) {
            $rec = Db::name('umi_order')->insert($add);
            //将积分写入会员表
            $res = Db::name('user')->where(['id' => $request['user']])->setInc('integral', $request['points']);
        }

//        $sys['ip'] = get_client_ip();
        $sys['user_id'] = $request['user_id'];
        $sys['mark'] = '会员获得有米积分记录,订单号为:'.$request['order'];
        $sys['create_time'] = time();
        $sys['type'] = 10;
        Db::name('admin_syslog') -> insert($sys);
        //參數 : order=订单号 ，app=开发者应用ID ，ad=广告名 ，user=用户ID ，chn=渠道号 ，points=积分值 ,sig='参数签名' ，adid=广告id ，pkg='应用包名，price=开发者收入 ，time=有米订单创建时间 ，device=设备ID ，storeid=应用商店ID ，
        $url = 'http://api.youmi.com/callback/youmiios?order='.$request['order'].'&app='.$request['app'].'&ad='.$request['ad'].'&adid='.$request['adid'].'&user='.$request['user'].'&chn='.$request['chn'].'&points='.$request['points'].'&price='.$request['price'].'&time='.$request['time'].'&device='.$request['device'].'&storeid='.$request['storeid'];

        $dev_server_secret = 'b0b0b93c993aff63';
        $url .= '&sign='.$this -> getUrlSignature($url, $dev_server_secret);

        return $url;
    }

    function getUrlSignature($url, $secret){
        $params = array();
        $url_parse = parse_url($url);
        if (isset($url_parse['query'])){
            $query_arr = explode('&', $url_parse['query']);
            if (!empty($query_arr)){
                foreach($query_arr as $p){
                    if (strpos($p, '=') !== false){
                        list($k, $v) = explode('=', $p);
                        $params[$k] = urldecode($v);
                    }
                }
            }
        }
        return getSignature($params, $secret);
    }

    function getSignature($params, $secret){
        $str = '';
        ksort($params);
        foreach ($params as $k => $v) {
            $str .= "{$k}={$v}";
        }
        $str .= $secret;
        return md5($str);
    }

}
