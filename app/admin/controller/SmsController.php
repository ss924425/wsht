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
namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Validate;

class SmsController extends AdminBaseController
{

    /**
     * 短信配置
     */
    public function index()
    {
        $smsSetting = cmf_get_option('sms_setting');
        $this->assign($smsSetting);
        return $this->fetch();
    }

    /**
     * 短信配置
     * @adminMenu(
     *     'name'   => '短信配置提交保存',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '短信配置提交保存',
     *     'param'  => ''
     * )
     */
    public function indexPost()
    {
        $post = array_map('trim', $this->request->param());

        if (in_array('', $post)) {
            $this->error("不能留空！");
        }

        cmf_set_option('sms_setting', $post);

        $this->success("保存成功！");
    }

    /**
     * 短信模板
     * @adminMenu(
     *     'name'   => '短信模板',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '短信模板',
     *     'param'  => ''
     * )
     */
    public function template()
    {
        $allowedTemplateKeys = ['verification_code'];
        $templateKey         = $this->request->param('template_key');

        if (empty($templateKey) || !in_array($templateKey, $allowedTemplateKeys)) {
            $this->error('非法请求！');
        }

        $template = cmf_get_option('sms_template_' . $templateKey);
        $this->assign($template);
        return $this->fetch('template_verification_code');
    }

    /**
     * 短信模板提交
     * @adminMenu(
     *     'name'   => '短信模板提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '短信模板提交',
     *     'param'  => ''
     * )
     */
    public function templatePost()
    {
        $allowedTemplateKeys = ['verification_code'];
        $templateKey         = $this->request->param('template_key');

        if (empty($templateKey) || !in_array($templateKey, $allowedTemplateKeys)) {
            $this->error('非法请求！');
        }

        $data = $this->request->param();

        $data['template'] = strip_tags($data['template']);
        unset($data['template_key']);

        cmf_set_option('sms_template_' . $templateKey, $data);

        $this->success("保存成功！");
    }

    /**
     * 短信发送测试
     * @adminMenu(
     *     'name'   => '短信发送测试',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '短信发送测试',
     *     'param'  => ''
     * )
     */
    public function test()
    {
        if ($this->request->isPost()) {

            $validate = new Validate([
                'to'      => 'require|regex:mobile',
                'content' => 'require',
            ]);
            $validate->message([
                'to.require'      => '收件人不能为空！',
                'to.regex'        => '收件人格式不正确！',
                'content.require' => '内容不能为空！',
            ]);

            $data = $this->request->param();
            if (!$validate->check($data)) {
                $this->error($validate->getError());
            }

            $result = cmf_send_sms($data['to'], $data['content']);
            if ($result && empty($result['error'])) {
                $this->success('发送成功！');
            } else {
                $this->error('发送失败：' . $result['message']);
            }
        } else {
            return $this->fetch();
        }
    }
}

