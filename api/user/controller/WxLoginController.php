<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace api\user\controller;

use think\Db;
use think\Model;
use think\Validate;
use cmf\phpqrcode\QRcode;
use cmf\controller\RestBaseController;

class WxLoginController extends RestBaseController
{

    const APPID = '';//APPID
    const RESPONSETYPE = 'code';//获取code
    const APPSECRET = '';
    const SCOPE = 'snsapi_userinfo';// 授权的方式

    public function redirect()
    {
        $data = $this->request->param();
        $code = $data['code'];
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=".self::APPID."&secret=".self::APPSECRET."&code=".$code."&grant_type=authorization_code";
        $res = self::http_curl($url);

        $access_token = $res['access_token'];

        $openid = $res['openid'];
        $url = "https://api.weixin.qq.com/sns/userinfo?access_token=".$access_token."&openid=".$openid."&lang=zh_CN";
        $userInfo = self::http_curl($url);

        if (empty($userInfo)) $this->error('授权失败');

        if (!empty($userInfo)){

            $user = db('user')->where('wechat_openid',$openid)->find();
            if (!$user){
                $userData['wechat_city'] = $userInfo['city'];
                $userData['wechat_country'] = $userInfo['country'];
                $userData['wechat_headimgurl'] = $userInfo['headimgurl'];
                $userData['wechat_openid'] = $openid;
                $userData['wechat_nickname'] = $userInfo['nickname'];
                $userData['wechat_province'] = $userInfo['province'];
                $userData['wechat_sex'] = $userInfo['sex'];
//            $userData['user_unionid'] = $userInfo['unionid'];
                $userData['wechat_code'] = $code;
                $userData['access_token'] = $res['access_token'];
                $userData['expires_in'] = $res['expires_in'];
                $userData['refresh_token'] = $res['refresh_token'];
                $userData['scope'] = $res['scope'];
                $userData['user_type'] = 1;
                $userData['last_login_ip'] = get_client_ip(0, true);
                $userData['last_login_time'] = time();
                $arr = $this->equipment_type($user['id'], $data['equipment']);
                if ($arr) {
                    $userData['equipment'] = $arr['equipment'];
                    if (isset($arr['repeat'])) {
                        $userData['repeat'] = $arr['repeat'];
                    }
                }

                $uid = db('user')->insertGetId($userData);

                $u_token = db('user_token')->where(['user_id' => $user['id']])->find();
                if ($u_token) {
                    db('user_token')->where(['id' => $u_token['id']])->delete();
                }

                $userTokenQuery = Db::name("user_token")
                    ->where('user_id', $uid)
                    ->where('device_type', $data['device_type']);
                $findUserToken = $userTokenQuery->find();
                $currentTime = time();
                $expireTime = $currentTime + 24 * 3600 * 180;
                $token = md5(uniqid()) . md5(uniqid());
                $return_token = $findUserToken['token'];
                $result = true;
                if (empty($findUserToken)) {
                    $result = $userTokenQuery->insert([
                        'token' => $token,
                        'user_id' => $uid,
                        'expire_time' => $expireTime,
                        'create_time' => $currentTime,
                        'device_type' => $data['device_type']
                    ]);
                    $return_token = $token;
                }
                if (empty($result)) {
                    $this->error("登录失败!");
                }

                if ($uid) $this->success("登录成功!", ['token' => $return_token, 'user' => $user]);
                else $this->error('添加微信用户失败');
            }

            $allowedDeviceTypes = $this->allowedDeviceTypes;

            if (empty($data['device_type']) || !in_array($data['device_type'], $allowedDeviceTypes)) {
                $this->error("请求错误,未知设备!");
            }

            $u_token = db('user_token')->where(['user_id' => $user['id']])->find();
            if ($u_token) {
                db('user_token')->where(['id' => $u_token['id']])->delete();
            }

            $userTokenQuery = Db::name("user_token")
                ->where('user_id', $user['id'])
                ->where('device_type', $data['device_type']);
            $findUserToken = $userTokenQuery->find();
            $currentTime = time();
            $expireTime = $currentTime + 24 * 3600 * 180;
            $token = md5(uniqid()) . md5(uniqid());
            $return_token = $findUserToken['token'];
            $result = true;
            if (empty($findUserToken)) {
                $result = $userTokenQuery->insert([
                    'token' => $token,
                    'user_id' => $user['id'],
                    'expire_time' => $expireTime,
                    'create_time' => $currentTime,
                    'device_type' => $data['device_type']
                ]);
                $return_token = $token;
            }

            if (empty($result)) {
                $this->error("登录失败!");
            }

            $results['id'] = $user['id'];
            $results['last_login_ip'] = get_client_ip(0, true);
            $results['last_login_time'] = time();
            //确定用户登录时所用的手机编号
            $arr = $this->equipment_type($user['id'], $data['equipment']);

            if ($arr) {
                $results['equipment'] = $arr['equipment'];
                if (isset($arr['repeat'])) {
                    $results['repeat'] = $arr['repeat'];
                }
            }

            Db::name('user')->update($results);

            unset($user['last_login_time'], $user['last_login_ip']);
            $user['money'] = round($user['user_money'] + $user['yong_money'] + $user['deposit'], 2);
            $user['vip_end_time'] = $user['vip_end_time'] > time() ? date('Y.m.d', $user['vip_end_time']) : 0;
            $this->success("登录成功!", ['token' => $return_token, 'user' => $user]);
        }

    }

    public static function http_curl($url)
    {
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);

        $output = curl_exec($ch);
        curl_close($ch);
        return json_decode($output,true);
    }

    public function equipment_type($id, $equipment)
    {
        $user = db('user')->where(['id' => $id])->value('equipment');
        $arr = [];
        if ($user && $user != $equipment) {
            //将当前用户已经使用过的设备
            $equ_arr = explode(',', $user);
            foreach ($equ_arr as $k => $v) {
                if ($v == $equipment) {
                    $equ_res = 1;
                } else {
                    $equ_res = 0;
                }
            }
            if ($equ_res == 0) {
                $arr['equipment'] = $user . ',' . $equipment;
            } else {
                $arr['equipment'] = $user;
            }
        } else {
            $arr['equipment'] = $equipment;
        }

        $where['equipment'] = ['like', '%' . $equipment . '%'];
        $repeat_num = db('user')->where($where)->field('id,equipment')->select()->toArray();
        if (count($repeat_num) > 3) {
            foreach ($repeat_num as $k => $v) {
                if ($v['equipment']) {
                    $arr['repeat'] = 1;
                    db('user')->where(['id' => $v['id']])->update(['repeat' => 1]);
                } else {
                    $arr['repeat'] = 0;
                }
            }
        }
        return $arr;
    }

}
