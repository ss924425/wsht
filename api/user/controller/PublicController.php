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
use think\Exception;
use think\Log;
use think\Model;
use think\Request;
use think\Validate;
use cmf\phpqrcode\QRcode;
use cmf\controller\RestBaseController;

class PublicController extends RestBaseController
{
    // 用户注册
    public function register()
    {
        $validate = new Validate([
            'mobile' => 'require',
            'password' => 'require',
            'captcha' => 'require'
        ]);

        $validate->message([
            'mobile.require' => '请输入手机号!',
            'password.require' => '请输入您的密码!',
            'captcha' => '请输入数字验证码!'
        ]);

        $data = $this->request->param();
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }

        $user = [];

        $findUserWhere = [];

        if (cmf_check_mobile($data['mobile'])) {
            $user['mobile'] = $data['mobile'];
            $findUserWhere['mobile'] = $data['mobile'];
        } else {
            $this->error("请输入正确的手机!");
        }

        $type = [1, 4, 5];
        $findUserWhere['user_type'] = ['in', $type];

        $errMsg = cmf_check_verification_code($data['mobile'], $data['captcha']);
        if (!empty($errMsg)) {
            $this->error($errMsg);
        }

        $findUserCount = Db::name("user")->where($findUserWhere)->count();

        if ($findUserCount > 0) {
            $this->error("此账号已存在!");
        }

        $user['user_login'] = $user['mobile'];
        $user['create_time'] = time();
        $user['user_status'] = 1;
        $user['user_type'] = 1;
        $user['vip_type'] = 1;
        $user['user_pass'] = cmf_password($data['password']);

        $result = Db::name("user")->insertGetId($user);

        Db::name('RoleUser')->insert(["role_id" => $user['user_type'], "user_id" => $result]);

        if (empty($result)) {
            $this->error("注册失败,请重试!");
        }

        $this->success("注册成功!");
    }

    // 用户登录 TODO 增加最后登录信息记录,如 ip
    public function login()
    {
        try {
            $data = $this->request->param();

            $findUserWhere = [];

            if (Validate::is($data['username'], 'email')) {
                $findUserWhere['user_email'] = $data['username'];
            } else if (cmf_check_mobile($data['username'])) {
                $findUserWhere['mobile'] = $data['username'];
            } else {
                $findUserWhere['user_login'] = $data['username'];
            }

            $findUser = Db::name("user")
                ->where($findUserWhere)
                ->whereOr(['user_type'=>1,'user_type'=>4,'user_type'=>5])->find();

            if (empty($findUser)) {
                $this->error("用户不存在!");
            } else {

                switch ($findUser['user_status']) {
                    case 0:
                        $this->error('您已被封号!');
                    case 2:
                        $this->error('账户还没有验证成功!');
                    case 3:
                        $this->error('请联系客服审核');
                }

                if (!cmf_compare_password($data['password'], $findUser['user_pass'])) {
                    $this->error("密码不正确!");
                }
            }

            if ($findUser['user_status'] == 0) $this->error('用户已被拉黑');

            unset($findUser['last_login_time'], $findUser['last_login_ip']);

            if ($findUser['vip_type'] == 1) {
                $findUser['name_type'] = '普通用户';
            } else if ($findUser['vip_type'] == 2) {
                $findUser['name_type'] = 'VIP会员';
            }

            $allowedDeviceTypes = $this->allowedDeviceTypes;

            if (empty($data['device_type']) || !in_array($data['device_type'], $allowedDeviceTypes)) {
                $this->error("请求错误,未知设备!");
            }

            $u_token = db('user_token')->where(['user_id' => $findUser['id']])->field('id')->find();
            if ($u_token) {
                db('user_token')->where(['id' => $u_token['id']])->delete();
            }

            $userTokenQuery = Db::name("user_token")
                ->where('user_id', $findUser['id'])
                ->where('device_type', $data['device_type']);
            $findUserToken = $userTokenQuery->field('token')->find();
            $currentTime = time();
            $expireTime = $currentTime + 24 * 3600 * 180;
            $token = md5(uniqid()) . md5(uniqid());
            $return_token = $findUserToken['token'];
            $result = true;
            if (empty($findUserToken)) {
                $result = $userTokenQuery->insert([
                    'token' => $token,
                    'user_id' => $findUser['id'],
                    'expire_time' => $expireTime,
                    'create_time' => $currentTime,
                    'device_type' => $data['device_type']
                ]);
                $return_token = $token;
            }

            if (empty($result)) {
                $this->error("登录失败!");
            }

            $results['id'] = $findUser['id'];
            $results['last_login_ip'] = get_client_ip(0, true);
            $results['last_login_time'] = time();
            //确定用户登录时所用的手机编号
//            $arr = $this->equipment_type($findUser['id'], $data['equipment']);
//
//            if ($arr) {
//                $results['equipment'] = $arr['equipment'];
//                if (isset($arr['repeat'])) {
//                    $results['repeat'] = $arr['repeat'];
//                }
//            }

            $results['repeat'] = '';

            Db::name('user')->update($results);

            unset($findUser['last_login_time'], $findUser['last_login_ip']);
            $findUser['money'] = round($findUser['user_money'] + $findUser['yong_money'] + $findUser['deposit'], 2);
            $findUser['vip_end_time'] = $findUser['vip_end_time'] > time() ? date('Y.m.d', $findUser['vip_end_time']) : 0;
            $this->success("登录成功!", ['token' => $return_token, 'user' => $findUser]);
        } catch (Exception $ex) {
//            Log::error("Exception：" . $ex);
            $this->error("登录失败，系统异常".$ex->getMessage());
        }
    }

    //手机编号处理  private
    public function equipment_type($id, $equipment)
    {
//        $id = 169092;
//        $equipment = '861980031572630';
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

    // 用户退出
    public function logout()
    {
        $userId = $this->getUserId();
        Db::name('user_token')->where([
            'token' => $this->token,
            'user_id' => $userId,
            'device_type' => $this->deviceType
        ])->update(['token' => '']);
        $time = time();
        Db::name('user')->where(['user_id' => $userId])->setField('logout_time', $time);

        $this->success("退出成功!");
    }

    // 用户密码重置
    public function passwordReset()
    {
        $validate = new Validate([
            'username' => 'require',
            'password' => 'require',
            'captcha' => 'require'
        ]);

        $validate->message([
            'username.require' => '请输入手机号',
            'password.require' => '请输入您的密码!',
            'captcha.require' => '请输入数字验证码!'
        ]);

        $data = $this->request->param();
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }

        $userWhere = [];
        if (Validate::is($data['username'], 'email')) {
            $userWhere['user_email'] = $data['username'];
        } else if (cmf_check_mobile($data['username'])) {
            $userWhere['mobile'] = $data['username'];
        } else {
            $this->error("请输入正确的手机号码");
        }

        $errMsg = cmf_check_verification_code($data['username'], $data['captcha']);
        if (!empty($errMsg)) {
            $this->error($errMsg);
        }

        $userPass = cmf_password($data['password']);
        Db::name("user")->where($userWhere)->update(['user_pass' => $userPass]);

        $this->success("密码重置成功,请使用新密码登录!");

    }

    //VIP会员推荐用户列表
    public function recommend_user()
    {
        $request = $this->request->param();

        //页数
        $p = $request['p'];
        if (empty($p)) {
            $p = 1;
        }

        $token = $request['token'];
        $user_id = db('user_token')->where(['token' => $token])->field('user_id')->find();
        $uid = $user_id['user_id'];
        if ($uid) {
            $list = db('user')->where(['pid' => $uid])->field('user_login,avatar,mobile,create_time,vip_type')->order('id desc')->limit(10)->page($p)->select()->toarray();

            foreach ($list as $k => $v) {
                $list[$k]['create_time'] = date('Y-m-d', $v['create_time']);
                if ($v['vip_type'] == 1) {
                    $list[$k]['vip_type'] = '普通会员';
                } elseif ($v['vip_type'] == 2) {
                    $list[$k]['vip_type'] = 'VIP会员';
                } elseif ($v['vip_type'] == 3) {
                    $list[$k]['vip_type'] = '代理商';
                } elseif ($v['vip_type'] == 4) {
                    $list[$k]['vip_type'] = '股东';
                }
            }

            if (!empty($list)) {
                $this->success('成功', ['list' => $list]);
            } else {
                $this->error('该用户暂没有推荐会员', ['list' => []]);
            }
        } else {
            $err = ['code' => -1, 'msg' => 'token无效!'];
            $this->error($err);
        }
    }

    //代理商推荐会员列表
    public function recommend_agent()
    {
        $request = $this->request->param();

        //页数
        $p = $request['p'];
        if (empty($p)) {
            $p = 1;
        }

        $token = $request['token'];
        $user_id = db('user_token')->where(['token' => $token])->field('user_id')->find();
        $uid = $user_id['user_id'];
        if ($uid) {
            $users = db('user')->where(['pid' => $uid])->field('id')->select()->toArray();
            $u = [];
            foreach ($users as $k => $v) {
                $u[$k] = $v['id'];
            }
            $list = db('user')->where(['agentId' => $uid])->where(['pid' => ['in', $u]])->field('user_login,avatar,mobile,create_time,vip_type')->order('id desc')->limit(10)->page($p)->select()->toarray();
            foreach ($list as $k => $v) {
                $list[$k]['create_time'] = date('Y-m-d', $v['create_time']);
                if ($v['vip_type'] == 1) {
                    $list[$k]['vip_type'] = '普通会员';
                } elseif ($v['vip_type'] == 2) {
                    $list[$k]['vip_type'] = 'VIP会员';
                } elseif ($v['vip_type'] == 3) {
                    $list[$k]['vip_type'] = '代理商';
                } elseif ($v['vip_type'] == 4) {
                    $list[$k]['vip_type'] = '股东';
                }
            }

            if (!empty($list)) {
                $this->success('成功', ['list' => $list]);
            } else {
                $this->error('该用户暂没有推荐会员', ['list' => []]);
            }
        } else {
            $err = ['code' => -1, 'msg' => 'token无效!'];
            $this->error($err);
        }
    }

    //团队推荐会员
    public function recommend_all()
    {
        $request = $this->request->param();

        //页数
        $p = $request['p'];
        if (empty($p)) {
            $p = 1;
        }

        $token = $request['token'];
        $user_id = db('user_token')->where(['token' => $token])->field('user_id')->find();
        $uid = $user_id['user_id'];
        if ($uid) {
            $list = db('user')->where('pid|agentId', 'eq', $uid)->field('user_login,avatar,mobile,create_time,vip_type')->order('id desc')->limit(10)->page($p)->select()->toarray();

            foreach ($list as $k => $v) {
                $list[$k]['create_time'] = date('Y-m-d', $v['create_time']);
                if ($v['vip_type'] == 1) {
                    $list[$k]['vip_type'] = '普通会员';
                } elseif ($v['vip_type'] == 2) {
                    $list[$k]['vip_type'] = 'VIP会员';
                } elseif ($v['vip_type'] == 3) {
                    $list[$k]['vip_type'] = '代理商';
                } elseif ($v['vip_type'] == 4) {
                    $list[$k]['vip_type'] = '股东';
                }
            }
            if (!empty($list)) {
                $this->success('成功', ['list' => $list]);
            } else {
                $this->error('该用户暂没有推荐会员', ['list' => []]);
            }
        } else {
            $err = ['code' => -1, 'msg' => 'token无效!'];
            $this->error($err);
        }
    }

    //团队中心
    public function team_info()
    {
        $request = $this->request->param();
        if (empty($request['token'])) {
            $this->error('失败');
            exit;
        }
        $token = $request['token'];
        $data = db('user_token')->where(['token' => $token])->field('user_id')->find();
        $uid = $data['user_id'];
        if ($uid) {
            $vip_type = db('user')->where(['id' => $uid])->value('vip_type');
            if ($vip_type == 2) {
                $people = db('user')->where('pid|agentId', 'eq', $uid)->count();
                $bonus = db('user_yong_log')->where(['user_id' => $uid, 'yong_type' => ['neq', 1], 'type' => 0])->sum('fxyj');
                $money = db('user_yong_log')->where(['user_id' => $uid, 'yong_type' => 1, 'type' => 0])->sum('fxyj');
            } elseif ($vip_type == 3 || $vip_type == 4) {
                $people = db('user')->where('pid|agentId', 'eq', $uid)->count();
                $bonus = db('user_yong_log')->where(['user_id' => $uid, 'yong_type' => ['neq', 1], 'type' => 0])->sum('fxyj');

                $money = db('user_yong_log')->where(['user_id' => $uid, 'yong_type' => 1, 'type' => 0])->sum('fxyj');
            } else {
                $people = 0;
                $money = 0;
                $bonus = 0;
            }

            $this->success('请求成功', [
                'people' => $people,
                'money' => $money,
                'bonus' => $bonus
            ]);
        } else {
            $err = ['code' => -1, 'msg' => 'token无效!'];
            $this->error($err);
        }
    }


    //用户二维码生成接口
    public function user_code()
    {
        $request = $this->request->param();
        $token = $request['token'];
        $user_id = db('user_token')->where(['token' => $token])->field('user_id')->find();
        $uid = $user_id['user_id'];
        if ($uid > 0) {
            $code = db('user')->where(['id' => $uid])->value('user_code');

            if (empty($code)) {
                //如果当前用户没有二维码，则立即生成
                $code = $this->qrcode($uid);

                if ($code) {
                    $this->success('生成二维码成功！', $code);
                } else {
                    $this->error('生成二维码失败！', '');
                }
            } else {
                //如果用户有二维码，则直接读取
                $this->success('用户二维码！', $code);
            }
        } else {
            $err = ['code' => -1, 'msg' => 'token无效!'];
            $this->error($err);
        }
    }

    //生成二维码
    public function qrcode($id)
    {
        //TODO'完善路径信息'
        $value = "http://" . $_SERVER['HTTP_HOST'] . url('index/Agent/order', ['top' => $id]);
        $errorCorrectionLevel = 'L';//容错级别
        $matrixPointSize = 50;//生成图片大小
        $savehader = $_SERVER['DOCUMENT_ROOT'] . '/upload/';
        $savefoote = 'qrcode/' . date('Ymd') . $id . '.png';

        $savepath = $savehader . $savefoote;

        //生成二维码图片
        QRcode::png($value, $savepath, $errorCorrectionLevel, $matrixPointSize, 2);

        $res = db('user')->where(['id' => $id])->update(['user_code' => $savefoote]);
        if ($res) {
            return $savefoote;
        } else {
            return false;
        }

    }

    public function poster()
    {
        $uid = $this->getUserId();

        if (empty($uid)) $this->error(['code' => 10001, 'msg' => $this->standard_code['10001']]);
        $userinfo = $this->getUserInfo();

        if (!empty($userinfo['poster'])) {
            $img = $userinfo['poster'];
            $this->success('成功', ['img' => $img]);
        }
        if (!empty($userinfo['user_nickname'])) {
            $vip = $userinfo['user_nickname'];
        } else {
            $vip = '';
        }

        $img = '';
        try {
            //背景图
            $poster = cmf_get_option('poster_setting')['thumb'];
            $bg = $_SERVER['DOCUMENT_ROOT']."/upload/".$poster;
            $bigImgPath = $bg ? $bg : $_SERVER['DOCUMENT_ROOT'] . "/upload/back/background.jpg";
//            $bigImgPath = $_SERVER['DOCUMENT_ROOT'] . "/upload/back/background.jpg";
            //获取用户二维码
            if (!empty($userinfo['user_code'])) {
                $qCodePath = $_SERVER['DOCUMENT_ROOT'] . '/upload/' . $userinfo['user_code'];
            } else {
                $code = $this->qrcode($uid);
                $qCodePath = $_SERVER['DOCUMENT_ROOT'] . '/upload/' . $code;
            }

            $bigImg = imagecreatefromstring(file_get_contents($bigImgPath));
            $qCodeImg = imagecreatefromstring(file_get_contents($qCodePath));

            list($qCodeWidth, $qCodeHight, $qCodeType) = getimagesize($qCodePath);
            list($qCodeWidth1, $qCodeHight1, $qCodeType1) = getimagesize($bigImgPath);

            //合成图片
            imagecopyresampled($bigImg, $qCodeImg, 335, 700, 0, 0, 730, 730, $qCodeWidth, $qCodeHight);

            //写入用户名
            $fonttype = $_SERVER['DOCUMENT_ROOT'] . '/upload/back/wqy-microhei.ttc';
            $fontcolor = imagecolorallocate($bigImg, 0x00, 0x00, 0x00);

            imagettftext($bigImg, 90, 0, 640, 450, $fontcolor, $fonttype, $vip);

            //压缩图片
            $percent = 0.3;

            $newwidth = $qCodeWidth1 * $percent;
            $newheight = $qCodeHight1 * $percent;

            $dst_im = imagecreatetruecolor($newwidth, $newheight);
            imagecopyresized($dst_im, $bigImg, 0, 0, 0, 0, $newwidth, $newheight, $qCodeWidth1, $qCodeHight1);


            $savehader = $_SERVER['DOCUMENT_ROOT'] . '/upload/';
            $savefoote = 'qrcode/' . time() . $uid . '.jpg';

            $savepath = $savehader . $savefoote;
            imagejpeg($dst_im, $savepath);

            $res = db('user')->where(['id' => $uid])->update(['poster' => $savefoote]);
            if ($res === false) {
                $this->error('生成失败');
            }
            $img = $savefoote;
        } catch (\Exception $e) {
            $this->error('生成失败'.$e->getMessage());
        }
        $this->success('成功', ['img' => $img]);
    }


    //完善个人信息页面
    public function massign()
    {
        $request = $this->request->param();
        $token = $request['token'];
        $user_id = db('user_token')->where(['token' => $token])->field('user_id')->find();
        $uid = $user_id['user_id'];
        if ($uid > 0) {
            $user = db('user')->where(['id' => $uid])->field('user_login,mobile')->find();
            $province = db('province')->select();
            if ($user) {
                $this->success('成功', ['user' => $user, 'province' => $province]);
            } else {
                $this->error('失败');
            }
        } else {
            $err = ['code' => -1, 'msg' => 'token无效!'];
            $this->error($err);
        }
    }


    //省市区三级联动
    public function address()
    {
        $request = $this->request->param();
        if ($request) {
            //市级单位
            $provinceId = $this->request->param('provinceId');
            if ($provinceId) {
                $city = db('city')->where(['father' => $provinceId])->select();
                if ($city) {
                    $this->success('成功', ['city' => $city]);
                } else {
                    $this->error('失败');
                }
            }
            //区县级单位
            $cityId = $this->request->param('cityId');
            if ($cityId) {
                $area = db('area')->where(['father' => $cityId])->select();
                if ($area) {
                    $this->success('成功', ['area' => $area]);
                } else {
                    $this->error('失败');
                }
            }
        }
    }

    // 完善信息
    public function complete()
    {
        $validate = new Validate([
            'user_nickname' => 'require',
            'sex' => 'require',
            // 'truename' => 'require',
            // 'apply_account' => 'require',
            // 'apply_name' => 'require',
            'id' => 'require',
        ]);

        $validate->message([
            'user_nickname.require' => '请输入您的昵称',
            // 'truename.require' => '请输入真实姓名',
            'sex.require' => '请选择性别',
            //'apply_account.require' => '请输入支付宝账号',
            // 'apply_name.require' => '请输入支付宝真实姓名',
            'id.require' => '缺少参数id',
        ]);

        $data = $this->request->param();
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }

        $user = db('user')->where(['id' => $data['id']])->find();


        $res = Db::name('user')->where(['id' => $data['id']])->update($data);

        if ($res === false) {
            $this->error('修改失败');
        } else {
            $this->success('修改成功');
        }
    }

    /**
     * 查询团队信息
     * @uid 用户ID
     * @return 返回格式json
     */
    public function getteamperformance()
    {
        $id = input('uid');
        if (empty($id))
            $this->error('缺少参数');
        else {
            $sql = "SELECT 
                      MM.hao,
                      MM.title,
                      SUM(IFNULL(MM.teamnum, 0)) teamnum,
                      SUM(IFNULL(HH.ordernum, 0)) ordernum,
                      SUM(IFNULL(NN.cointotal, 0)) cointotal,
                      SUM(IFNULL(NN.cointoday, 0)) cointoday 
                    FROM
                      (SELECT 
                        YY.hao,
                        YY.title,
                        YY.uid,
                        COUNT(YY.uid) teamnum 
                      FROM
                        (SELECT 
                          '1' hao,
                          '我' title,
                          id uid 
                        FROM
                          `mc_user` 
                        WHERE id = '{$id}'
                        UNION
                        ALL 
                        SELECT 
                          '2' hao,
                          '一级' title,
                          id uid 
                        FROM
                          `mc_user` 
                        WHERE pid = '{$id}'
                        UNION
                        ALL 
                        SELECT 
                          '3' hao,
                          '二级' title,
                          id uid 
                        FROM
                          `mc_user` 
                        WHERE pid IN 
                          (SELECT 
                            id 
                          FROM
                            `mc_user` 
                          WHERE pid = '{$id}')) YY 
                      GROUP BY YY.hao,
                        YY.title,
                        YY.uid) MM 
                      LEFT JOIN 
                        (SELECT 
                          B.`userid` uid,
                          COUNT(B.`id`) ordernum 
                        FROM
                          `mc_self_task_receive` B 
                        WHERE B.status <> 2 
                        GROUP BY B.`userid`) HH 
                        ON MM.uid = HH.uid 
                      LEFT JOIN 
                        (SELECT 
                          C.`user_id` uid,
                          SUM(C.`coin`) cointotal,
                          SUM(
                            CASE
                              WHEN DATE_FORMAT(
                                FROM_UNIXTIME(IFNULL(C.create_time, 0)),
                                '%Y-%m-%d'
                              ) = DATE_FORMAT(NOW(), '%Y-%m-%d') 
                              THEN C.coin 
                              ELSE 0 
                            END
                          ) cointoday 
                        FROM
                          `mc_user_money_log` C 
                        WHERE C.channel IN (31, 32, 34, 35) 
                        GROUP BY C.`user_id`) NN 
                        ON MM.uid = NN.uid 
                         GROUP BY MM.hao,MM.title ORDER  BY MM.hao DESC";
            $data = Db::query($sql);
            if (empty($data))
                $this->error('团队信息查询失败');
            else
                $this->success('查询成功', $data);
        }
    }

    /**
     * 查询今日新增下级
     */
    public function todayteamsum()
    {
        $beginToday = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
        $endToday = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;
        $uid = $this->request->param('uid');
        $res = Db::name('user_tuiguang_log')->where('pid', $uid)
            ->where('create_time', '>=', $beginToday)
            ->where('create_time', '<=', $endToday)
            ->select()->toArray();

        $list = [];

        foreach ($res as $v) {
            $list[] = Db::name('user_tuiguang_log')->where('pid', $v['user_id'])
                ->where('create_time', '>=', $beginToday)
                ->where('create_time', '<=', $endToday)
                ->select()->toArray();
        }
        foreach ($list as $item) {
            $i = 0;
            if (!empty($item)) {
                $i++;
            }
        }
        $sum = count($res) + $i;
        if ($sum > 0) {
            $this->success('查询成功', $sum);
        } else {
            $this->success('查询成功', 0);
        }

    }

    /**
     * 目前下级人数
     */
    public function teamSum()
    {
        $uid = $this->request->param('uid');
        $res = Db::name('user')->where('pid', '=', $uid)->select()->toArray();
        $list = [];
        foreach ($res as $v) {
            $list[] = Db::name('user')->where('pid', $v['id'])->select()->toArray();
        }
        foreach ($list as $item) {
            $i = 0;
            if (!empty($item)) {
                $i++;
            }
        }
        $sum = count($res) + $i;
        if ($sum > 0) {
            $this->success('查询成功', $sum);
        } else {
            $this->success('查询成功', 0);
        }
    }


    /**
     * 账号绑定
     */
    function bindAccount()
    {
        try {
            if ($this->request->isPost()) {
                $uid = $this->getUserId();
                $type = input('type');  // 5.爆音套餐 4.KS 3.小书本 2.火S 1.KS悬赏
                switch ($type) {
                    case 1 :
                        $account1 = input('account1');
                        if (db('user')->where('id', $uid)->setField('account1', $account1))
                            $this->success('绑定成功');
                        else
                            $this->error('绑定失败');
                        break;
                    case 2 :
                        $account2 = input('account2');
                        if (db('user')->where('id', $uid)->setField('account2', $account2))
                            $this->success('绑定成功');
                        else
                            $this->error('绑定失败');
                        break;
                    case 3 :
                        $account3 = input('account3');
                        if (db('user')->where('id', $uid)->setField('account3', $account3))
                            $this->success('绑定成功');
                        else
                            $this->error('绑定失败');
                        break;
                    case 4 :
                        $account4 = input('account4');
                        if (db('user')->where('id', $uid)->setField('account4', $account4))
                            $this->success('绑定成功');
                        else
                            $this->error('绑定失败');
                        break;
                    case 5 :
                        $account5 = input('account5');
                        if (db('user')->where('id', $uid)->setField('account5', $account5))
                            $this->success('绑定成功');
                        else
                            $this->error('绑定失败');
                        break;
                }
            }
        } catch (Exception $exception) {
            $this->error('系统异常' . $exception);
        }
    }


}
