<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace app\user\controller;

use cmf\controller\HomeBaseController;
use think\Validate;

class VerificationCodeController extends HomeBaseController
{
    public function send()
    {
        $validate = new Validate();
//
//        $validate->message([
//            'username.require' => '请输入手机号!',
//        ]);

        $data = $this->request->param();
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }
//        var_dump($data);die;
        if($data['p_type'] == 1){
            $data['username'] = $data['phone'];
        }
        if($data['p_type'] == 2){
            $data['username'] = db('user') -> where(['id' => session('user_id')]) -> value('mobile');
        }

        $accountType = '';

        if (Validate::is($data['username'], 'email')) {
            $accountType = 'email';
        } else if (cmf_check_mobile($data['username'])) {
            $accountType = 'mobile';
        } else {
            $this->error("请输入正确的手机格式!");
        }

        if (isset($data['type']) && $data['type'] == 'register') {
            if ($accountType == 'email') {
                $findUserCount = db('user')->where('user_email', $data['username'])->count();
            } else if ($accountType == 'mobile') {
                $findUserCount = db('user')->where('mobile', $data['username'])->count();
            }

            if ($findUserCount > 0) {
                $this->error('账号已注册！');
            }
        }

        //TODO 限制 每个ip 的发送次数

        $code = cmf_get_verification_code($data['username']);
        if (empty($code)) {
            $this->error("验证码发送过多,请明天再试!");
        }

        if ($accountType == 'email') {

            $emailTemplate = cmf_get_option('email_template_verification_code');

            $user = cmf_get_current_user();
            $username = empty($user['user_nickname']) ? $user['user_login'] : $user['user_nickname'];

            $message = htmlspecialchars_decode($emailTemplate['template']);
            $message = $this->view->display($message, ['code' => $code, 'username' => $username]);
            $subject = empty($emailTemplate['subject']) ? 'ThinkCMF验证码' : $emailTemplate['subject'];
            $result = cmf_send_email($data['username'], $subject, $message);

            if (empty($result['error'])) {
                cmf_verification_code_log($data['username'], $code);
                $this->success("验证码已经发送成功!");
            } else {
                $this->error("邮箱验证码发送失败:" . $result['message']);
            }

        } else if ($accountType == 'mobile') {

            $sms_template = cmf_get_option('sms_template_verification_code');
            $msg = str_replace('{$code}',$code,$sms_template['template']);
            file_put_contents('sms.log',$msg.PHP_EOL,FILE_APPEND);
            $result = cmf_send_sms($data['username'],$msg);
            //将验证码存入session
            session('code',$code);

            $expireTime = time()+120;

            cmf_verification_code_log($data['username'], $code, $expireTime);

            if ($result['error']) {
                $this->error($result['message']);
            } else {
                $this->success('验证码已经发送成功!');
            }
        }
    }
}
