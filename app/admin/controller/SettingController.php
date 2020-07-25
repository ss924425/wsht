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
use think\Db;

class SettingController extends AdminBaseController
{

    public function charge()
    {
        $setting = cmf_get_option('charge_setting');
        $this->assign('setting', $setting);
        return $this->fetch();
    }

    public function chargePost()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param();
            cmf_set_option('charge_setting', $param);
            $this->success("保存成功！", '');
        }
    }

    //商城设置
    public function shop()
    {
        $setting = cmf_get_option('shop_setting');
        $this->assign('setting', $setting);
        return $this->fetch();
    }

    public function shopPost()
    {
        if ($this->request->isPost()) {
            $options = $this->request->param('options/a');
            cmf_set_option('shop_setting', $options);
            $this->success("保存成功！", '');
        }
    }

    public function paylist()
    {
        $setting = cmf_get_option('paylist_setting');
        //1微信支付 2 支付宝支付
        $paylist = [];
        if ($setting) {
            foreach ($setting as $k => $v) {
                if ($v['pay_id'] == 'alipay') {
                    array_push($paylist, '2');
                } elseif ($v['pay_id'] == 'wxpay') {
                    array_push($paylist, '1');
                }
            }
        }
        $this->assign('paylist', $paylist);
        $this->assign('setting', $setting);
        return $this->fetch();
    }

    public function paylistPost()
    {
        if ($this->request->isPost()) {
            $paylist = $this->request->param('paylist/a');
            $paylist = $paylist ? $paylist : [];
            $setting = [];
            if (in_array('1', $paylist)) {
                $wxpay = ['pay_id' => 'wxpay', 'pay_name' => '微信支付', 'pay_thumb' => 'images/wxpay.png'];
                array_push($setting, $wxpay);
            }
            if (in_array('2', $paylist)) {
                $alipay = ['pay_id' => 'alipay', 'pay_name' => '支付宝支付', 'pay_thumb' => 'images/alipay.png'];
                array_push($setting, $alipay);
            }
            cmf_set_option('paylist_setting', $setting, true);
            $this->success("保存成功！", '');
        }
    }

    public function pay()
    {
        $setting = cmf_get_option('pay_setting');
        $set_saoma = cmf_get_option('paylist_setting');
        $this->assign('setting', $setting);
        $this->assign('set_saoma', $set_saoma);
        if (isset($setting['wxpay']['isopen'])&&$setting['wxpay']['isopen'])
            $this->assign('wxisopen', "1");
        else
            $this->assign('wxisopen', 0);

        if (isset($setting['alipay']['isopen'])&&$setting['alipay']['isopen'])
            $this->assign('alisopen', "1");
        else
            $this->assign('alisopen', 0);

        if (isset($set_saoma['isopen'])&&$set_saoma['isopen'])
            $this->assign('scisopen', "1");
        else
            $this->assign('scisopen', 0);

        return $this->fetch();
    }

    public function payPost()
    {
        if ($this->request->isPost()) {
            $wxpay = $this->request->param('wxpay/a');
            $alipay = $this->request->param('alipay/a');
//            $saoma = $this->request->param('set_saoma/a');
            $setting = cmf_get_option('pay_setting');
//            $set_saoma = cmf_get_option('paylist_setting');
            $setting['wxpay'] = $wxpay;
            $setting['alipay'] = $alipay;

//            $set_saoma['isopen'] = isset($saoma['isopen'])?$saoma['isopen']:0;
//            $set_saoma['wxpay']['pay_thumb'] = $saoma['wxpay']['pay_thumb'];
//            $set_saoma['alipay']['pay_thumb'] = $saoma['alipay']['pay_thumb'];
//            $set_saoma['alipay']['pay_name'] = $saoma['alipay']['pay_name'];
            cmf_set_option('pay_setting', $setting);
//            cmf_set_option('paylist_setting', $set_saoma);
            $this->success("保存成功！", '');
        }
    }

    public function upgrade()
    {
        $upgrade_setting = cmf_get_option('upgrade_setting');
        $this->assign('upgrade_setting', $upgrade_setting);
        return $this->fetch();
    }

    public function upgradePost()
    {
        if ($this->request->isPost()) {
            $options = $this->request->param();
            cmf_set_option('upgrade_setting', $options);
            $this->success("保存成功！", '');
        }
    }

    //海报设置
    public function poster()
    {
        $option = cmf_get_option('poster_setting');
        $option['default'] = isset($option['default']) ? $option['default'] : '';
        $this->assign('poster_setting', $option);
        return $this->fetch();
    }

    public function posterPost()
    {
        if ($this->request->isPost()) {
            $options = $this->request->param('options/a');
            $options['default'] = 'background.jpg';
            $options['thumb'] = cmf_asset_relative_url($options['thumb']);
            $bg = './upload/' . $options['thumb'];
            $poster = cmf_get_poster(0, $bg);
            $poster = $poster ? $poster : '';
            $options['thumb_preview'] = $poster;
            cmf_set_option('poster_setting', $options);

            $this->success("保存成功！", '');
        }
    }

    /**
     * 上传限制设置界面
     * @adminMenu(
     *     'name'   => '上传设置',
     *     'parent' => 'default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '上传设置',
     *     'param'  => ''
     * )
     */
    public function upload()
    {
        $uploadSetting = cmf_get_upload_setting();
        $this->assign('upload_setting', $uploadSetting);
        return $this->fetch();
    }

    /**
     * 上传限制设置界面提交
     * @adminMenu(
     *     'name'   => '上传设置提交',
     *     'parent' => 'upload',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '上传设置提交',
     *     'param'  => ''
     * )
     */
    public function uploadPost()
    {
        if ($this->request->isPost()) {
            //TODO 非空验证
            $uploadSetting = $this->request->post();

            cmf_set_option('upload_setting', $uploadSetting);
            $this->success('保存成功！');
        }
    }

    public function youmi()
    {
        $this->assign('youmi_setting', cmf_get_option('youmi_setting'));
        return $this->fetch();
    }

    public function youmiPost()
    {
        if ($this->request->isPost()) {
            $options = $this->request->param('options/a');
            isset($options['isTestModel']) ? $options['isTestModel'] = 1 : $options['isTestModel'] = 0;
            isset($options['isOpen']) ? $options['isOpen'] = 1 : $options['isOpen'] = 0;
            cmf_set_option('youmi_setting', $options);
            $this->success("保存成功！", '');
        }
    }

    public function user()
    {
        $user_setting = cmf_get_option('user_setting');
        $this->assign('user_setting', $user_setting);
        return $this->fetch();
    }

    public function userPost()
    {
        if ($this->request->isPost()) {
            $result = $this->validate($this->request->param(), 'SettingUser');
            if ($result !== true) {
                $this->error($result);
            }
            $options = $this->request->param();
            cmf_set_option('user_setting', $options);
            $this->success("保存成功！", '');
        }
    }

    public function site()
    {
        $site_info = cmf_get_option('site_info');
//        dump($site_info);die;
        $this->assign('site_info', $site_info);
        return $this->fetch();
    }

    public function sitePost()
    {
        if ($this->request->isPost()) {
            $result = $this->validate($this->request->param(), 'SettingSite');
            if ($result !== true) {
                $this->error($result);
            }

            $options = $this->request->param('options/a');
            cmf_set_option('site_info', $options);

            $this->success("保存成功！", '');

        }
    }

    /**
     * 密码修改
     * @adminMenu(
     *     'name'   => '密码修改',
     *     'parent' => 'default',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '密码修改',
     *     'param'  => ''
     * )
     */
    public function password()
    {
        return $this->fetch();
    }

    /**
     * 密码修改提交
     * @adminMenu(
     *     'name'   => '密码修改提交',
     *     'parent' => 'password',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '密码修改提交',
     *     'param'  => ''
     * )
     */
    public function passwordPost()
    {
        if ($this->request->isPost()) {

            $data = $this->request->param();
            if (empty($data['old_password'])) {
                $this->error("原始密码不能为空！");
            }
            if (empty($data['password'])) {
                $this->error("新密码不能为空！");
            }

            $userId = cmf_get_current_admin_id();

            $admin = Db::name('user')->where(["id" => $userId])->find();

            $oldPassword = $data['old_password'];
            $password = $data['password'];
            $rePassword = $data['re_password'];

            if (cmf_compare_password($oldPassword, $admin['user_pass'])) {
                if ($password == $rePassword) {

                    if (cmf_compare_password($password, $admin['user_pass'])) {
                        $this->error("新密码不能和原始密码相同！");
                    } else {
                        Db::name('user')->where('id', $userId)->update(['user_pass' => cmf_password($password)]);
                        $this->success("密码修改成功！");
                    }
                } else {
                    $this->error("密码输入不一致！");
                }

            } else {
                $this->error("原始密码不正确！");
            }
        }
    }

    /**
     * 清除缓存
     * @adminMenu(
     *     'name'   => '清除缓存',
     *     'parent' => 'default',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '清除缓存',
     *     'param'  => ''
     * )
     */
    public function clearCache()
    {
        cmf_clear_cache();
        return $this->fetch();
    }

    //轮播图管理
    public function hp_img()
    {
        $list = db('home_page_img')->select();
        $this->assign('list', $list);
        return $this->fetch();
    }

    //轮播图编辑
    public function edit()
    {
        $id = input('id');
        $list = db('home_page_img')->where(['id' => $id])->find();
        $this->assign('list', $list);
        return $this->fetch();
    }

    //执行编辑操作
    public function edit_img()
    {
        $post = input();
        $data = $post['post'];
        if ($data['type'] == 0) {
            if (empty($data['img'])) {
                $this->error('请上传图片');
            }
        }
        $data['modify_id'] = cmf_get_current_admin_id();
        $data['modify_time'] = time();

        $res = db('home_page_img')->update($data);
        if ($res) {
            $this->success('操作成功');
        } else {
            $this->error('操作失败');
        }
    }

    // 图标配置
    public function icon()
    {
        return $this->fetch();
    }

    public function iconPost()
    {
        $request = $this->request;
        if ($request->isPost()) {
            $data = $request->param();
            $data['create_time'] = time();
            $res = db('icon')->insert($data);
            if (empty($res)) {
                $this->error('添加图标失败');
            }
            $this->success('添加成功');
        }
    }


    // 发任务配置
    public function selfTask()
    {
        $settings = cmf_get_option('selftask_setting');
        $this->assign('settings', $settings);
        return $this->fetch();
    }

    public function selfTaskPost()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            cmf_set_option('selftask_setting', $data);
            $this->success('保存成功', '');
        }
    }

    //帮助中心设置
    public function helpCenter()
    {
        $help_setting = cmf_get_option('help_setting');
        $this->assign('help_setting', $help_setting);
        return $this->fetch();
    }

    public function helpPost()
    {
        if ($this->request->isPost()) {
            $options = $this->request->param();
            cmf_set_option('help_setting', $options);
            $this->success('保存成功', '');
        }
    }

    // 用户协议设置
    public function userXieyi()
    {
        $xieyi_setting = cmf_get_option('xieyi_setting');
        $this->assign('xieyi_setting', $xieyi_setting);
        return $this->fetch();
    }

    public function xieyiPost()
    {
        if ($this->request->isPost()) {
            $options = $this->request->param();
            cmf_set_option('xieyi_setting', $options);
            $this->success('保存成功', '');
        }
    }

    /**
     * 榜单金额设置
     */
    public function topMoney()
    {
        $settings = cmf_get_option('topmoney_setting');
//        dump($settings);die;
//        dump($settings['task']['top1']);die;
        $this->assign('settings', $settings);
        return $this->fetch();
    }

    public function topMoneyPost()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            cmf_set_option('topmoney_setting', $data);
            $this->success('保存成功', '');
        }
    }

    // 发任务步骤说明
    public function taskExplain()
    {
        $task_explain = cmf_get_option('task_explain' . input('tasktype'));
        $this->assign('task_explain', $task_explain);
        $this->assign('tasktype', input('tasktype'));
        return $this->fetch();
    }

    public function taskExplainPost()
    {
        if ($this->request->isPost()) {
            $options = $this->request->param();
            dump($options);die;
            cmf_set_option('task_explain' . input('tasktype'), $options);
            $this->success('保存成功', '');
        }
    }

    // 做任务步骤说明
    public function doTaskExplain()
    {
        $do_task_explain = cmf_get_option('do_task_explain' . input('tasktype'));
        $this->assign('do_task_explain', $do_task_explain);
        $this->assign('tasktype', input('tasktype'));
        return $this->fetch();
    }

    public function doTaskExplainPost()
    {
        if ($this->request->isPost()) {
            $options = $this->request->param();
            cmf_set_option('do_task_explain' . input('tasktype'), $options);
            $this->success('保存成功', '');
        }
    }

    /**
     * 账号绑定教程
     */
    function bindStep()
    {
        $bind_step = cmf_get_option('bind_step'.input('tasktype'));
        $this->assign('bind_step',$bind_step);
        $this->assign('tasktype',input('tasktype'));
        return $this->fetch();
    }

    function bindStepPost()
    {
        if ($this->request->isPost()){
            $options = $this->request->param();
            cmf_set_option('bind_step' . input('tasktype'),$options);
            $this->success('保存成功');
        }
    }

    /**
     * 积分设置
     */
    function score_setting()
    {
        if ($this->request->isPost()){
            $options = $this->request->param();
            cmf_set_option('score_setting',$options);
            $this->success('保存成功');
        }
        $score_setting = cmf_get_option('score_setting');
        $this->assign('score_setting',$score_setting);
        return $this->fetch();
    }

}