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
namespace app\admin\validate;

use think\Validate;

class SelftaskValidate extends Validate
{
    protected $rule = [
        'user_login' => 'require|unique:user,user_login|min:2|max:16',
        'user_pass'  => 'require|min:6|max:16',
        'mobile' => 'require|checkMobile',
        'user_email' => 'email|unique:user,user_email',
    ];
    protected $message = [
        'user_login.require' => '用户不能为空',
        'user_login.unique'  => '用户名已存在',
        'user_login.min'  => '用户名最小长度为3',
        'user_login.max'  => '用户名最大长度为16',
        'user_pass.require'  => '密码不能为空',
        'user_pass.min'  => '密码最小长度为6',
        'user_pass.max'  => '密码最大长度为16',
        'mobile.require' => '手机号不能为空',
        'user_email.email'   => '邮箱不正确',
        'user_email.unique'  => '邮箱已经存在',
    ];

    protected $scene = [
        'add'  => ['user_login', 'user_pass', 'mobile','user_email'],
        'edit' => ['user_login', 'user_pass', 'mobile','user_email'],
    ];
    protected function checkMobile($mobile){
        $reg = '/^(13[0-9]|14[579]|15[0-3,5-9]|16[6]|17[0135678]|18[0-9]|19[89])\d{8}$/';
        if(preg_match($reg,$mobile)){
            return true;
        }
        return '手机号不正确';
    }
}