<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace api\user\controller;

use cmf\controller\RestUserBaseController;
use think\Db;
use think\Validate;

class ProfileController extends RestUserBaseController
{
    // 用户密码修改
    public function changePassword()
    {
        $validate = new Validate([
            'old_password'     => 'require',
            'password'         => 'require',
            'confirm_password' => 'require|confirm:password'
        ]);

        $validate->message([
            'old_password.require'     => '请输入您的旧密码!',
            'password.require'         => '请输入您的新密码!',
            'confirm_password.require' => '请输入确认密码!',
            'confirm_password.confirm' => '两次输入的密码不一致!'
        ]);

        $data = $this->request->param();
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }

        $userId       = $this->getUserId();
        $userPassword = Db::name("user")->where('id', $userId)->value('user_pass');

        if (!cmf_compare_password($data['old_password'], $userPassword)) {
            $this->error('旧密码不正确!');
        }

        Db::name("user")->where('id', $userId)->update(['user_pass' => cmf_password($data['password'])]);

        $this->success("密码修改成功!");
    }
}
