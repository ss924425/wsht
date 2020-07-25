<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace api\pay\controller;

use think\Db;
use think\Validate;
use cmf\controller\RestBaseController;

class CommonController extends RestBaseController
{
    public function payList()
    {
        $data['paylist'] = cmf_get_option('paylist_setting');
        $data['upgrade'] = cmf_get_option('upgrade_setting');
        $data['pay_setting'] = cmf_get_option('pay_setting');
        $data['upgrade']['vip_desc'] = isset($data['upgrade']['vip_desc']) ? htmlspecialchars_decode($data['upgrade']['vip_desc']) : '';
        $data['charge'] = cmf_get_option('charge_setting');
        $data['charge']['yue_desc'] = isset($data['charge']['yue_desc']) ? htmlspecialchars_decode($data['charge']['yue_desc']) : '';
        $data['charge']['deposit_desc'] = isset($data['charge']['deposit_desc']) ? htmlspecialchars_decode($data['charge']['deposit_desc']) : '';
        return json($data);
    }


    public function payListnew()
    {
        $data = [];
        $setting = cmf_get_option('pay_setting');
        $weixin = '';
        $alipay = '';
        if (isset($setting['wxpay']['isopen']) && $setting['wxpay']['isopen']) {
//            array_push($data, [
//                'pay_id' => 'wxpay',
//                'pay_name' => '微信支付',
//                'pay_thumb' => "images/wxpay.png"
//            ]);

            $weixin = [
                'pay_id' => 'wxpay',
                'pay_name' => '微信支付',
                'pay_thumb' => "images/wxpay.png"
            ];


        }
        if (isset($setting['alipay']['isopen']) && $setting['alipay']['isopen']) {
//            array_push($data, [
//                'pay_id' => 'alipay',
//                'pay_name' => '支付宝支付',
//                'pay_thumb' => "images/alipay.png"
//            ]);

            $alipay = [
                'pay_id' => 'alipay',
                'pay_name' => '支付宝支付',
                'pay_thumb' => "images/alipay.png"
            ];

        }

        $data['paylist'] = [$weixin,$alipay];

        $interests = '';
        $user_setting = cmf_get_option('user_setting');
        $upgrade_setting = cmf_get_option('upgrade_setting');

//        if (!empty($user_setting)) {
//            $interests = htmlspecialchars_decode($user_setting['interests']);
//        }


        $data['upgrade'] = cmf_get_option('upgrade_setting');
        $data['upgrade']['vip_desc'] = isset($data['upgrade']['vip_desc']) ? htmlspecialchars_decode($data['upgrade']['vip_desc']) : '';
        $data['charge'] = cmf_get_option('charge_setting');
        $data['charge']['yue_desc'] = isset($data['charge']['yue_desc']) ? htmlspecialchars_decode($data['charge']['yue_desc']) : '';
        $data['charge']['deposit_desc'] = isset($data['charge']['deposit_desc']) ? htmlspecialchars_decode($data['charge']['deposit_desc']) : '';

        return json(['data' => $data, 'upgrade_price' => $upgrade_setting['upgrade_price']]);
    }
}
