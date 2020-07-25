<?php

namespace app\index\controller;

use cmf\controller\HomeBaseController;
use think\Db;

class AgentController extends HomeBaseController
{
    public function order()
    {
        $request = $this->request;
        $top_id = $request->param('top');

        if (!$request->isGet()) {
            die("<h2 align='center'>非法请求</h2>");
        } else {
            if (!$top_id) {
                die("<h2 align='center'>缺少ID参数</h2>");
            }
            session('top_id', $top_id);

            return $this->fetch();
        }
    }

    //用户注册
    public function register()
    {
        if ($this->request->isPost()) {
            //指定当前用户身份为普通会员
            $_POST['role_id'] = [1];
            if (!empty($_POST['role_id']) && is_array($_POST['role_id'])) {
                $role_ids = $_POST['role_id'];
                unset($_POST['role_id']);
                //查看当前手机号是否已经注册
                $count = db('user')->where(['mobile' => $_POST['mobile'], 'user_type' => 1])->count();
                if ($count > 0) {
                    $this->error('该手机号已注册');
                }
                if (empty($_POST['user_pass'])) {
                    $this->error('密码不能为空');
                }
                if ($_POST['user_pass'] !== $_POST['password']) {
                    $this->error('两次密码不一致');
                }
                if (empty($_POST['yzm'])) {
                    $this->error('请输入验证码');
                }
                if ($_POST['yzm'] != session('code')) {
                    $this->error('验证码不正确');
                }
                unset($_POST['password']);
                unset($_POST['yzm']);

                $_POST['user_login'] = $_POST['mobile'];
                $_POST['user_type'] = 1;
                $_POST['vip_type'] = 1;
                $_POST['vip_end_time'] = strtotime("+1 year");
                $_POST['create_time'] = time();
                $_POST['user_status'] = 1;
                $_POST['user_pass'] = cmf_password($_POST['user_pass']);

                $pid = session('top_id');
                if ($pid) {
                    $_POST['pid'] = $pid;
                    //查询被扫描用户的情况
                    $agent = db('user')->where(['id' => $pid])->field('pid,vip_type,agentId')->find();
                    if ($agent) {
                        //如果被扫描的用户是代理商或股东身份，则直接录入
                        if ($agent['vip_type'] >= 3) {
                            $_POST['agentId'] = $pid;
                        }
                        //如果被扫描的用户不是代理商或股东身份，而其agentId不为空，则继承agentId
                        if ($agent['vip_type'] < 3 && $agent['agentId']) {
                            $_POST['agentId'] = $agent['agentId'];
                        }
                        //查询上上级
                        if ($agent['pid'] > 0) {
                            $_POST['ppid'] = $agent['pid'];
                        }
                    }
                }

                $result = DB::name('user')->insertGetId($_POST);
                //插入推广日志
                $logdata['pid'] = $pid;
                $logdata['user_id'] = $result;
                $logdata['create_time'] = time();
                $res = Db::name('user_tuiguang_log')->insert($logdata);
                if (!$res) {
                    $this->error('插入推广日式失败');
                }
                if ($result !== false) {
                    foreach ($role_ids as $role_id) {
                        Db::name('RoleUser')->insert(["role_id" => $role_id, "user_id" => $result]);
                    }
                    $res = db('user')->where('id',$pid)->setInc('tuiguang_sum');
                    if (!$res) {
                        $this->error('上级增加推广数量失败');
                    }

                    $site = cmf_get_option('site_info');
                    $this->success("注册成功！", $site['site_link']);
                } else {
                    $this->error("注册失败！");
                }
            } else {
                $this->error("请为此用户指定角色！");
            }
        }
    }


    //---------------------------------------------以下代码作废---------------------------------------------

    //验证操作
    public function order_info()
    {
        $request = $this->request;
        $mobile = $request->param('mobile');
        $yzm = $request->param('yzm');
        if ($yzm == session('code')) {
            session('phone', $mobile);
            $this->success('', 'transfer');

        } else {
            $this->error('验证码不正确');
        }
    }

    //中转页面
    public function transfer()
    {
        $comp_id = session('comp_id');
        $top_id = session('top_id');
        if ($comp_id || $top_id) {
            return $this->fetch();
        } else {
            die("<h2 align='center'>缺少ID参数</h2>");
        }
    }

    //-------------------------------------------------------------------------------------------
    //选择开户数页面
    public function accountnum()
    {
        $comp_id = session('comp_id');
        $top_id = session('top_id');
        if ($comp_id || $top_id) {
            if ($comp_id) {
                $number = db('agent')->where(['id' => $comp_id])->value('max_num');
                $this->assign('number', $number);
            }
            return $this->fetch();
        } else {
            die("<h2 align='center'>缺少ID参数</h2>");
        }
    }


    public function order_index()
    {
        $comp_id = session('comp_id');
        $top_id = session('top_id');
        if ($comp_id || $top_id) {
            $province = db('province')->select();
            $this->assign('province', $province);

            $num = $this->request->param('num');
            $this->assign('mobile', session('phone'));
            $this->assign('comp_id', $comp_id);
            $this->assign('top_id', $top_id);
            $this->assign('num', $num);
            return $this->fetch();
        } else {
            die("<h2 align='center'>缺少ID参数</h2>");
        }
    }

    public function orderMake()
    {
        $request = $this->request;
        if ($request->post()) {
            $data = $request->post();
            /* 验证姓名 Start */
            $checkName = $this->checkName($data['name']);
            if (!$checkName) {
                $info = [
                    'status' => 0,
                    'msg' => '用户名格式错误'
                ];
                return json($info);
            }
            /* 验证姓名 End */

            /* 验证手机号码 Start */
            $reg = "/^(13[0-9]|14[57]|15[012356789]|17[0-9]|18[0-9])\d{8}$/";
            if (!preg_match($reg, $data['mobile'])) {
                $info = [
                    'status' => 0,
                    'msg' => '手机号码格式错误'
                ];
                return json($info);
            }
            /* 验证手机号码 End */

            /* 验证密码 Start */
            if (!$data['user_pass']) {
                $info = [
                    'status' => 0,
                    'msg' => '请输入密码'
                ];
                return json($info);
            }

            if (!$data['password']) {
                $info = [
                    'status' => 0,
                    'msg' => '请确认密码'
                ];
                return json($info);
            }

            if ($data['password'] !== $data['user_pass']) {
                $info = [
                    'status' => 0,
                    'msg' => '两次密码不一致'
                ];
                return json($info);
            }
            /* 验证密码 End */

            /* 验证身份证 Start */
            if (!$this->isCreditNo($data['idno'])) {
                $info = [
                    'status' => 0,
                    'msg' => '身份证号码格式错误'
                ];
                return json($info);
            }
            /* 验证身份证 End */

            /* 验证支付宝账号 Start */
            if (!$data['aliCard']) {
                $info = [
                    'status' => 0,
                    'msg' => '请填写支付宝账号！'
                ];
                return json($info);
            }
            /* 验证支付宝账号 End */

            /* 验证支付宝姓名 Start */
            if (!$data['aliName']) {
                $info = [
                    'status' => 0,
                    'msg' => '请填写支付宝姓名！'
                ];
                return json($info);
            }
            /* 验证支付宝姓名 End */

            /* 验证开户行 Start */
            if (!$this->checkStr($data['bank'])) {
                $info = [
                    'status' => 0,
                    'msg' => '开户行格式不正确！'
                ];
                return json($info);
            }
            /* 验证开户行 End */

            /* 验证银行卡号 Start */
            if (!$this->checkIdno($data['bankCard'])) {
                $info = [
                    'status' => 0,
                    'msg' => '银行卡号格式不正确！'
                ];
                return json($info);
            }
            /* 验证银行卡号 End */

            /* 验证地址 Start */
            if (!$data['province'] || !$data['area'] || !$data['city']) {
                $info = [
                    'status' => 0,
                    'msg' => '请填写地址信息！'
                ];
                return json($info);
            }
            /* 验证地址 End */

            /* 验证支行信息 Start */
//            if (!$data['bankBranch']) {
//                $info = [
//                    'status' => 0,
//                    'msg' => '请填写支行名字！'
//                ];
//                return json($info);
//            }
            /* 验证支行信息 End */

            /* 验证密保问题 Start */
            if (!$data['security']) {
                $info = [
                    'status' => 0,
                    'msg' => '请填写密保问题！'
                ];
                return json($info);
            }
            /* 验证密保问题 End */

            /* 验证密保答案 Start */
            if (!$data['answer']) {
                $info = [
                    'status' => 0,
                    'msg' => '请填写密保答案！'
                ];
                return json($info);
            }
            /* 验证密保答案 End */

            /* 验证协议 Start */
            if (!$data['ty']) {
                $info = [
                    'status' => 0,
                    'msg' => '请先同意协议'
                ];
                return json($info);
            }
            /* 验证协议 End */

            // user_type user_status vip_end_time

            /* 生成数据 Start */
            $userData['user_login'] = $data['name'];
            $userData['user_pass'] = cmf_password($data['user_pass']);
            $userData['user_nickname'] = $data['trueName'];
            $userData['mobile'] = $data['mobile'];
            $userData['sex'] = $data['sex'];
            $userData['pid'] = $data['top_id'];
            $userData['idno'] = $data['idno'];
            $userData['province'] = $data['province'];
            $userData['city'] = $data['city'];
            $userData['area'] = $data['area'];
            $userData['createTime'] = time();
            $userData['apply_account'] = $data['aliCard'];
            $userData['apply_name'] = $data['aliName'];
            $userData['agentid'] = $data['comp_id'];
            if ($data['top_id']) {
                $top_two = db('user')->where(['id' => $data['top_id']])->value('top_one');
                $userData['top_two'] = $top_two;
            }


            $userData['bank'] = $data['bank'];
            $userData['bankCard'] = $data['bankCard'];

            $p = db('province')->where(['provinceID' => $data['province']])->value('province');
            $c = db('city')->where(['cityID' => $data['city']])->value('city');
            $a = db('area')->where(['areaID' => $data['area']])->value('area');
            $userData['bankAddress'] = $p . $c . $a;
            $userData['bankBranch'] = $data['bankBranch'];

            $userData['security'] = $data['security'];
            $userData['answer'] = $data['answer'];

            $_POST['role_id'] = [1];
            if (!empty($_POST['role_id']) && is_array($_POST['role_id'])) {
                $role_ids = $_POST['role_id'];
                unset($_POST['role_id']);


                $userData['user_type'] = 1;//普通会员
                $userData['vip_end_time'] = strtotime("+1 year");
                $userData['user_status'] = 1;//1正常 3待审核状态

                $result = DB::name('user')->insertGetId($userData);

                //如果会员注册不用审核，则执行返佣金
//                if($userData['user_status'] == 1){
//                    $this -> rebate($result);
//                }

                if ($result !== false) {
                    foreach ($role_ids as $role_id) {
//                            if (cmf_get_current_admin_id() != 1 && $role_id == 1) {
//                                $this->error("为了网站的安全，非网站创建者不可创建超级管理员！");
//                            }
                        Db::name('RoleUser')->insert(["role_id" => $role_id, "user_id" => $result]);
                    }
                    $info = [
                        'status' => 1,
                        'msg' => '申请成功!请耐心等待审核',
                        'id' => $result
                    ];
                    return json($info);
                } else {
                    $this->error("添加失败！");
                }

            } else {
                $this->error("请为此用户指定角色！");
            }

        } else {
            die('禁止外部访问');
        }
    }

    //提交结束页面
    public function trailer()
    {
        $id = $this->request->param('id');
        if ($id > 0) {
            $detail = db('agent_order')->alias('o')
                ->join('agent a', 'o.agentid = a.id')
                ->field('o.orderid,o.batchNumber,o.createTime,a.comp_name,a.contacts,a.cont_phone')
                ->where(['o.id' => $id])->find();
//            var_dump($detail);
            $this->assign('data', $detail);
            return $this->fetch();
        } else {
            die("<h2 align='center'>缺少ID参数</h2>");
        }
    }

    //返利
    protected function rebate($id)
    {
        $top_id = db('user')->where(['id' => $id])->field('pid,top_two')->find();
        $user_setting = cmf_get_option('user_setting');
        $user_setting['distribution1'];
        $user_setting['distribution2'];
        Db::startTrans();
        //向上一级返利
        if ($top_id['pid']) {
            //变动金额
            $score1 = $user_setting['registerMoney'] * $user_setting['distribution1'] / 100;
            //变动前金额
            $front = db('user')->where(['id' => $top_id['pid']])->value('yong_money');
            //变动后金额
            $after = $front + $score1;
            //会员佣金变动
            Db::name('user')->where(['id' => $top_id['pid']])->update(['yong_money' => $after]);

            //组织会员表插入
            $add['1']['user_id'] = $top_id['pid'];
            $add['1']['sup_id'] = $id;
            $add['1']['create_time'] = time();
            $add['1']['front_score'] = $front;
            $add['1']['score'] = $score1;
            $add['1']['after_score'] = $after;
            $add['1']['type'] = 0;
            $add['1']['notes'] = '会员注册，奖励佣金';
        }
        //向上两级返利
        if ($top_id['top_two']) {

            //变动金额
            $score2 = $user_setting['registerMoney'] * $user_setting['distribution2'] / 100;
            //变动前金额
            $front = db('user')->where(['id' => $top_id['top_two']])->value('yong_money');
            //变动后金额
            $after = $front + $score2;
            //会员佣金变动
            Db::name('user')->where(['id' => $top_id['top_two']])->update(['yong_money' => $after]);

            $add['2']['user_id'] = $top_id['top_two'];
            $add['2']['sup_id'] = $id;
            $add['2']['create_time'] = time();
            $add['2']['front_score'] = $front;
            $add['2']['score'] = $score2;
            $add['2']['after_score'] = $after;
            $add['2']['type'] = 0;
            $add['2']['notes'] = '会员注册，奖励佣金';
        }

        //添加分销日志
        $res = Db::name('user_yong_log')->insertAll($add);
        if ($res) {
            return true;
        } else {
            return false;
        }
    }


    public function orderSuccess()
    {
        $request = $this->request;
        if ($request->isGet()) {
            $order_id = $request->param('order_id');
            if (!$order_id) {
                die("<h2 align='center'>缺少ID参数</h2>");
            } else {
                $order = Db::name('agent_order');
                $orderInfo = $order->where('id=' . $order_id)->find();
                if (!$orderInfo) {
                    die("<h2 align='center'>订单信息出错</h2>");
                } else {
                    $this->assign('orderInfo', $orderInfo);
                    return $this->fetch();
                }
            }
        } else {
            die('禁止外部请求');
        }
    }


    public function checkNum()
    {
        $request = $this->request;
        if ($request->isAjax()) {
            $data = $request->param();
            $id = $data['id'];
            $num = $data['num'];
            $m = Db::name('agent');
            $data = $m->where("id={$id}")->find();
            $maxNum = $data['max_num'];
            if ($maxNum < $num) {
                $info = [
                    'status' => 0,
                    'msg' => '此代理商名下剩余人数不足,请选择其他选项'
                ];
                return json($info);
            } else {
                $info = [
                    'status' => 1,
                    'msg' => ''
                ];
                return json($info);
            }
        } else {
            die('禁止外部访问');
        }
    }


    protected function isCreditNo($vStr)
    {
        $vCity = array(
            '11', '12', '13', '14', '15', '21', '22',
            '23', '31', '32', '33', '34', '35', '36',
            '37', '41', '42', '43', '44', '45', '46',
            '50', '51', '52', '53', '54', '61', '62',
            '63', '64', '65', '71', '81', '82', '91'
        );
        if (!preg_match('/^([\d]{17}[xX\d]|[\d]{15})$/', $vStr)) return false;
        if (!in_array(substr($vStr, 0, 2), $vCity)) return false;
        $vStr = preg_replace('/[xX]$/i', 'a', $vStr);
        $vLength = strlen($vStr);
        if ($vLength == 18) {
            $vBirthday = substr($vStr, 6, 4) . '-' . substr($vStr, 10, 2) . '-' . substr($vStr, 12, 2);
        } else {
            $vBirthday = '19' . substr($vStr, 6, 2) . '-' . substr($vStr, 8, 2) . '-' . substr($vStr, 10, 2);
        }
        if (date('Y-m-d', strtotime($vBirthday)) != $vBirthday) return false;
        if ($vLength == 18) {
            $vSum = 0;
            for ($i = 17; $i >= 0; $i--) {
                $vSubStr = substr($vStr, 17 - $i, 1);
                $vSum += (pow(2, $i) % 11) * (($vSubStr == 'a') ? 10 : intval($vSubStr, 11));
            }
            if ($vSum % 11 != 1) return false;
        }
        return true;
    }

    protected function checkName($name)
    {
        if (strlen($name) < 6) {
            return false;
        }
        //新疆等少数民族可能有·
        if (strpos($name, '·')) {
            //将·去掉，看看剩下的是不是都是中文
            $str = str_replace("·", '', $name);
            if (!preg_match('/^[\x7f-\xff]+$/', $name)) {
                //不全是中文
                return false;
            }
        } else {
            if (!preg_match('/^[\x7f-\xff]+$/', $name)) {
                //不全是中文
                return false;
            }
        }
        return true;
    }

    protected function checkStr($str)
    {
        if (!preg_match('/^[\x7f-\xff]+$/', $str)) {
            //不全是中文
            return false;
        } else {
            return true;
        }
    }

    protected function checkIdno($no)
    {
        if (!$no) {
            return false;
        }
        $arr_no = str_split($no);

        $last_n = $arr_no[count($arr_no) - 1];
        krsort($arr_no);

        $i = 1;
        $total = 0;
        foreach ($arr_no as $n) {
            if ($i % 2 == 0) {
                $ix = $n * 2;
                if ($ix >= 10) {
                    $nx = 1 + ($ix % 10);
                    $total += $nx;
                } else {
                    $total += $ix;
                }
            } else {
                $total += $n;
            }
            $i++;
        }
        $total -= $last_n;
        $total *= 9;
        if ($last_n == ($total % 10)) {
            return true;
        }
    }

}