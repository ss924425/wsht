<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace api\user\controller;

use cmf\controller\RestBaseController;
use think\Validate;
use think\View;

class VerificationController extends RestBaseController
{
    public function send()
    {
//        $validate = new Validate([
//            'username' => 'require',
//        ]);

//        $validate->message([
//            'username.require' => '请输入手机号!',
//        ]);

        $data = $this->request->param();
//        if (!$validate->check($data)) {
//            $this->error($validate->getError());
//        }
        if(empty($data['mobile'])){
            $this->error("请输入手机号!");
        }else{
            $data['username'] = $data['mobile'];
        }

        $accountType = '';

        if (Validate::is($data['username'], 'email')) {
            $accountType = 'email';
        } else if (cmf_check_mobile($data['username'])) {
            $accountType = 'mobile';
        } else {
            $this->error("请输入正确的手机号!");
        }

        //TODO 限制 每个ip 的发送次数

        $code = cmf_get_verification_code($data['username']);
        if (empty($code)) {
            $this->error("验证码发送过多,请明天再试!");
        }

        if ($accountType == 'email') {

            $emailTemplate = cmf_get_option('email_template_verification_code');

            $user     = cmf_get_current_user();
            $username = empty($user['user_nickname']) ? $user['user_login'] : $user['user_nickname'];

            $message = htmlspecialchars_decode($emailTemplate['template']);
            $view    = new View();
            $message = $view->display($message, ['code' => $code, 'username' => $username]);
            $subject = empty($emailTemplate['subject']) ? 'ThinkCMF验证码' : $emailTemplate['subject'];
            $result  = cmf_send_email($data['username'], $subject, $message);

            if (empty($result['error'])) {
                cmf_verification_code_log($data['username'], $code);
                $this->success("验证码已经发送成功!");
            } else {
                $this->error("邮箱验证码发送失败:" . $result['message']);
            }

        } else if ($accountType == 'mobile') {

            $template = cmf_get_option('sms_template_verification_code')['template'];
            $message = str_replace('{$code}',$code,$template);
            $result = cmf_send_sms($data['username'],$message);
            cmf_verification_code_log($data['username'], $code);

            if ($result['error']) {
                $this->error($result['message']);
            } else {
                $this->success('验证码已经发送成功!');
            }

        }


    }

}
