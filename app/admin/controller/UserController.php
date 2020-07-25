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
use think\Validate;

/**
 * Class UserController
 * @package app\admin\controller
 * @adminMenuRoot(
 *     'name'   => '管理组',
 *     'action' => 'default',
 *     'parent' => 'user/AdminIndex/default',
 *     'display'=> true,
 *     'order'  => 10000,
 *     'icon'   => '',
 *     'remark' => '管理组'
 * )
 */
class UserController extends AdminBaseController
{
    private $fenxiao_info = [
        'fenxiao_switch' => '1',  //分销开关
        'fenxiao_level' => '2',  //二级分销
        'fenxiao_name' => '分销商',  //二级分销
        'first_per' => '6',  //一级分销率
        'second_per' => '3',  //二级分销率
        'agent_per' => '38',  //代理商固返金额
        'fx_usertype_list' => ['4,5', '5'] //参与一级佣金的用户类型
    ];

    //团队
    public function team()
    {
        $user_id = input('user_id');
        if (!$user_id) {
            exit;
        }
        $users = model('admin/user');
        //用户信息
        $user_info = $users->get_user_info_by_user_id($user_id);
        $this->assign('user_info', $user_info);
        //团队人数-第一层
        $fenxiao_team_num = $users->where('pid', $user_id)->count();
        $childids = $users->where('pid', $user_id)->column('id');
        $child_level2 = $users->where('pid', 'in', $childids)->select()->each(function ($item, $key) {
            switch ($item['vip_type']) {
                case 1:
                    $item['user_type_name'] = '普通会员';
                    break;
                case 2:
                    $item['user_type_name'] = 'VIP会员';
                    break;
                case 3:
                    $item['user_type_name'] = '代理商';
                    break;
                case 4:
                    $item['user_type_name'] = '股东';
                    break;
            }
            return $item;
        })->toArray();
        //团队人数-第二层
        foreach ($child_level2 as $k => $v) {
            $pid = $users->where('id', $v['id'])->value('pid');
            $p_agentId = $users->where('id', $pid)->value('agentId');
            if ($v['agentId'] > 0 && $v['agentId'] == $p_agentId) {
                $fenxiao_team_num++;
                continue;
            }
        }
        $this->assign('fenxiao_team_num', $fenxiao_team_num);

        //获取团队信息
        $user_list = array();
        //获取第一级分销团队
        $user_list[1] = $users->get_team_level($user_id, 1);
        //获取第二级分销团队
        foreach ($child_level2 as $k => $v) {
            $pid = $users->where('id', $v['id'])->value('pid');
            $p_agentId = $users->where('id', $pid)->value('agentId');
            if ($v['agentId'] > 0 && $v['agentId'] == $p_agentId) {
                $user_list[2][] = $child_level2[$k];
                continue;
            }
        }
        $this->assign('user_list', $user_list);
        $fenxiao_info = $this->fenxiao_info;
        $this->assign('fenxiao_info', $fenxiao_info);
        return $this->fetch();
    }

    /**
     * 员工列表
     * @adminMenu(
     *     'name'   => '员工',
     *     'parent' => 'default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '员工管理',
     *     'param'  => ''
     * )
     */
    public function index()
    {
        $vip = ['1'];
        $where['mc_user.user_type'] = ['in', $vip];
        /**搜索条件**/
        $user_id = $this->request->param('user_id');
        $userLogin = $this->request->param('user_login');
        $mobile = $this->request->param('mobile');
        $agentId = $this->request->param('agentId');
        $vip_type = $this->request->param('vip_type');
        $this->assign('vip_type', $vip_type);
        $important = $this->request->param('important');
        $this->assign('important', $important);
        $repeat = $this->request->param('repeat');
        $this->assign('repeat', $repeat);

        if ($vip_type) {
            $where['mc_user.vip_type'] = $vip_type;
        }

        if ($user_id) {
            $where['mc_user.id'] = $user_id;
        }

        if ($userLogin) {
            $where['mc_user.user_login'] = ['like', "%$userLogin%"];
        }

        if ($mobile) {
            $where['mc_user.mobile'] = ['like', "%$mobile%"];
        }

        if ($agentId) {
            $where['mc_user.agentId'] = $agentId;
        }

        if ($important) {
            $where['u.important'] = $important - 1;
        }

        if ($repeat) {
            $where['u.repeat'] = $repeat - 1;
        }

        //每页显示数量
        $limit = 20;

        $admin_id = cmf_get_current_admin_id();
        $this->assign('sign_id', $admin_id);

        $admin_user = db('user')->where(['id' => $admin_id])->value('user_type');

        $field = 'u.id,u.user_login,u.mobile,p.province,u.user_type,u.create_time,u.last_login_ip,u.last_login_time,u.user_money,u.deposit,u.yong_money,u.apply_name,u.apply_account,u.agentId,up.user_login up_name,ug.user_login ug_name,u.user_status,u.user_url,c.city,a.area,u.pid,u.vip_type,u.important,u.credit_score';

        if (!in_array($admin_user, $vip)) {
            $users = Db::name('user')->alias('u')
                ->join('province p', 'u.province = p.provinceID', 'left')
                ->join('city c', 'u.city = c.cityID', 'left')
                ->join('area a', 'u.area = a.areaID', 'left')
                ->join('mc_user up', 'u.pid = up.id', 'left')
                ->join('mc_user ug', 'u.agentId = ug.id', 'left')
                ->where($where)
                ->field($field)
                ->order("u.id DESC")
                ->paginate($limit, false, ['query' => array("user_id" => $user_id, 'user_login' => $userLogin, 'mobile' => $mobile, 'vip_type' => $vip_type, 'agentId' => $agentId, 'important' => $important, 'repeat' => $repeat)]);

        } else {
            $users = Db::name('user')->alias('u')
                ->join('province p', 'u.province = p.provinceID', 'left')
                ->join('city c', 'u.city = c.cityID', 'left')
                ->join('area a', 'u.area = a.areaID', 'left')
                ->join('mc_user up', 'u.pid = up.id', 'left')
                ->join('mc_user ug', 'u.agentId = ug.id', 'left')
                ->where($where)
                ->where('u.pid|u.agentId', 'eq', $admin_id)
                ->field($field)
                ->order("u.id DESC")
                ->paginate($limit, false, ['query' => array("user_id" => $user_id, 'user_login' => $userLogin, 'mobile' => $mobile, 'vip_type' => $vip_type, 'agentId' => $agentId, 'important' => $important)]);
        }
        $users->appends(['user_login' => $userLogin]);

        // 获取分页显示
        $page = $users->render();

        $rolesSrc = Db::name('role')->select();
        $roles = [];
        foreach ($rolesSrc as $r) {
            $roleId = $r['id'];
            $roles["$roleId"] = $r;
        }
        unset($where['contacts']);

        $num = 1;
        if ($this->request->isGet()) {
            $p = input('get.');
            if (!empty($p['page'])) {
                $num = $p['page'];
            }
        }
        $beginToday = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
        $endToday = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;
        $nowsum = db('user')
            ->where('last_login_time','>',$beginToday)
            ->where('last_login_time','>','logout_time')
            ->where('user_status','<>',0)
            ->where('user_type','not in',[2,3])
            ->count();
        $this->assign("num", $num);
        $this->assign("limit", $limit);

        $this->assign("page", $page);
        $this->assign("roles", $roles);
        $this->assign("users", $users);
        $this->assign("nowsum", $nowsum);
        return $this->fetch();
    }

    //会员金额统计
    public function count_money()
    {
        $id = $this->request->param('uid');
        if (!empty($id)) {
            //账户余额
            $totalmoney = db('user_money_log')->where(['user_id' => $id, 'channel' => ['in', [31, 32, 34, 35]]])->sum('coin');
            if (empty($totalmoney))
                return 0.00;
            else
                return $totalmoney;
        } else {
            $this->error('请求错误');
        }
    }

    /**
     * 管理员列表
     */
    public function index2()
    {
        $where['user_type'] = ['not in', [1,4,5]];
        /**搜索条件**/
        $userLogin = $this->request->param('user_login');
        $userEmail = trim($this->request->param('user_email'));

        if ($userLogin) {
            $where['user_login'] = ['like', "%$userLogin%"];
        }

        if ($userEmail) {
            $where['user_email'] = ['like', "%$userEmail%"];;
        }
        $users = Db::name('user')->alias('A')
            ->where($where)
            ->join('__ROLE_USER__ B', 'A.id = B.user_id','left')
            ->join('__ROLE__ C', 'B.role_id = C.id')
            ->where('A.id','>',1)
            ->field('A.*,C.name')
            ->order("id DESC")
            ->paginate(10, false, ['query' => array('user_login' => $userLogin, 'user_email' => $userEmail)]);
        $users->appends(['user_login' => $userLogin, 'user_email' => $userEmail]);
        // 获取分页显示
        $page = $users->render();
        $this->assign("page", $page);
        $this->assign("users", $users);
        return $this->fetch();
    }

    /**
     * 员工添加
     * @adminMenu(
     *     'name'   => '员工添加',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '员工添加',
     *     'param'  => ''
     * )
     */
    public function add()
    {
        $roles = Db::name('role')->where(['status' => 1, 'type' => 1])->order("id DESC")->select();
        $this->assign("roles", $roles);
        return $this->fetch();
    }

    /**
     * 员工添加提交
     * @adminMenu(
     *     'name'   => '员工添加提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '员工添加提交',
     *     'param'  => ''
     * )
     */
    public function addPost()
    {
        if ($this->request->isPost()) {
            if (!empty($_POST['role_id']) && is_array($_POST['role_id'])) {
                $role_ids = $_POST['role_id'];
                unset($_POST['role_id']);
                $result = $this->validate($this->request->param(), 'User');
                if ($result !== true) {
                    $this->error($result);
                } else {
                    $_POST['user_type'] = 2;
                    $_POST['user_pass'] = cmf_password($_POST['user_pass']);
                    $result = DB::name('user')->insertGetId($_POST);
                    if ($result !== false) {
                        foreach ($role_ids as $role_id) {
                            if (cmf_get_current_admin_id() != 1 && $role_id == 1) {
                                $this->error("为了网站的安全，非网站创建者不可创建超级管理员！");
                            }
                            Db::name('RoleUser')->insert(["role_id" => $role_id, "user_id" => $result]);
                        }
                        $this->success("添加成功！", url("user/index2"));
                    } else {
                        $this->error("添加失败！");
                    }
                }
            } else {
                $this->error("请为此用户指定角色！");
            }

        }
    }

    /**
     * 员工编辑
     * @adminMenu(
     *     'name'   => '员工编辑',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '员工编辑',
     *     'param'  => ''
     * )
     */
    public function edit()
    {
        $adminid = session('ADMIN_ID');
        $adminuser = db('user')->find($adminid);
        $this->assign('admin',$adminuser);
        $id = $this->request->param('id', 0, 'intval');
        $type = db('user')->alias('u')
            ->join('role_user r', 'u.id = r.user_id')
            ->join('role ro', 'r.role_id = ro.id')
            ->where(['u.id' => $id])
            ->value('ro.type');

        $roles = DB::name('role')->where(['status' => 1, 'type' => $type])->order("id DESC")->select();
        $this->assign("roles", $roles);
        $role_ids = DB::name('RoleUser')->where(["user_id" => $id])->column("role_id");
        $this->assign("role_ids", $role_ids);

        $user = DB::name('user')->where(["id" => $id])->find();

        $this->assign('user', $user);
        return $this->fetch();
    }

    /**
     * 员工编辑提交
     * @adminMenu(
     *     'name'   => '员工编辑提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '员工编辑提交',
     *     'param'  => ''
     * )
     */
    public function editPost1()
    {
        if ($this->request->isPost()) {
            $role_id = $this->request->param('role_id');

            if (empty($role_id)) {
                $user_type = db('user')->where(['id' => $_POST['id']])->value('user_type');
                $_POST['role_id'] = $user_type;
            }

            if (!empty($_POST['role_id'])) {
                if (empty($_POST['user_pass'])) {
                    unset($_POST['user_pass']);
                } else {
                    $tmp = $_POST['user_pass'];
                }
                $role_ids = $_POST['role_id'];
                unset($_POST['role_id']);
                $param = $this->request->param();
                $user = db('user')->find($param['id']);
                if ($user['user_pass'] == $_POST['user_pass']) {
                    $param['user_pass'] = '123456';
                    unset($_POST['user_pass']);//不更新密码
                }
                $result = $this->validate($param, 'User.edit');

                if ($result !== true) {
                    // 验证失败 输出错误信息
                    $this->error($result);
                } else {
                    if ($user['user_pass'] != $tmp) {
                        $_POST['user_pass'] = cmf_password($tmp);
                    }
                    $_POST['user_type'] = $role_ids;
                    $result = DB::name('user')->update($_POST);
                    if ($result !== false) {
                        $uid = $this->request->param('id', 0, 'intval');
                        DB::name("RoleUser")->where(["user_id" => $uid])->delete();

                        if (cmf_get_current_admin_id() != 1 && $role_ids == 1) {
                            $this->error("为了网站的安全，非网站创建者不可创建超级管理员！");
                        }
                        DB::name("RoleUser")->insert(["role_id" => $role_ids, "user_id" => $uid]);

                        $this->success("保存成功！");
                    } else {
                        $this->error("保存失败！");
                    }
                }
            } else {
                $this->error("请为此用户指定角色！");
            }
        }
    }

    public function editPost()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $uid = $this->request->param('id');
            $user = db('user')->where(['id' => $uid])->find();

            $data['change_user_money'] = isset($data['change_user_money']) ? $data['change_user_money'] : 0; //用户余额

            $data['change_deposit'] = isset($data['change_deposit']) ? $data['change_deposit'] : 0;  // 用户保证金

            $data['change_cl_money'] = isset($data['change_cl_money']) ? $data['change_cl_money'] : 0;  // 丛林用户余额

            if ($data['change_user_money'] < 0) {
                $surplus = $user['user_money'] - abs($data['change_user_money']);//剩余
                if ($surplus < 0) {
                    $this->error('余额不足，不能扣掉这么多');
                }
            }
            if ($data['change_deposit'] < 0) {
                $surplus = $user['deposit'] - abs($data['change_deposit']);//剩余
                if ($surplus < 0) {
                    $this->error('保证金不足，不能扣掉这么多');
                }
            }
            if ($data['change_cl_money'] < 0) {
                $surplus = $user['cl_money'] - abs($data['change_cl_money']);//剩余
                if ($surplus < 0) {
                    $this->error('余额不足，不能扣掉这么多');
                }
            }

            //提交的信息是否有角色信息
            $role_id = $this->request->param('role_id');
            if (empty($role_id)) {
                $user_type = db('user')->where(['id' => $data['id']])->value('user_type');
                $data['role_id'] = $user_type;
            }
            Db::startTrans();
            try {
                if (empty($data['user_pass'])) {
                    unset($data['user_pass']);
                } else {
                    $tmp = $data['user_pass'];
                }
                $role_ids = $data['role_id'];
                unset($data['role_id']);
                unset($data['id']);
                //将信息提取出来进行验证

                $param = $data;
                if ($user['user_pass'] == $data['user_pass']) {
                    $param['user_pass'] = '123456';
                    unset($_POST['user_pass']);//不更新密码
                }

                $result = $this->validate($param, 'User.edit');
                if ($result) {
                    if ($user['user_pass'] != $tmp) {
                        $data['user_pass'] = cmf_password($tmp);
                    }
                    $data['user_type'] = $role_ids;

                    if($data['change_user_money']>0){
                        //加钱
                        $surplus = $user['user_money'] + $data['change_user_money'];
                        $data['user_money'] = $surplus;
                        $money_log['user_id'] = $uid;
                        $money_log['agentId'] = $user['agentId'];
                        $money_log['create_time'] = time();
                        $money_log['coin'] = $data['change_user_money'];
                        $money_log['type'] = 0;//收入
                        $money_log['status'] = 0;//金额
                        $money_log['notes'] = '系统加钱';
                        $money_log['user_money'] = $surplus;
                        $money_log['deposit'] = $user['deposit'];
                        $money_log['integral'] = $user['integral'];
                        $money_log['log_type'] = 3;
                        $res = db('user_money_log')->insert($money_log);
                        if(!$res){
                            Db::rollback();
                            $this->error('写入日志出错，请稍后重试');
                        }
                        $res = db('user')->where('id',$uid)->setField('user_money',$data['user_money']);
                        if($res === false){
                            Db::rollback();
                            $this->error('更改余额出错');
                        }
                    }else if($user['user_money']>0 && $data['change_user_money']<0){
                        //减钱
                        $surplus = $user['user_money'] - abs($data['change_user_money']);//剩余
                        if ($surplus < 0.01) {
                            $this->error('余额不足，不能扣掉这么多');
                        }
                        $data['user_money'] = $surplus;
                        $money_log['user_id'] = $uid;
                        $money_log['agentId'] = $user['agentId'];
                        $money_log['create_time'] = time();
                        $money_log['coin'] = $data['change_user_money'];
                        $money_log['type'] = 1;//支出
                        $money_log['status'] = 0;//金额
                        $money_log['notes'] = '系统扣钱';
                        $money_log['user_money'] = $surplus;
                        $money_log['deposit'] = $user['deposit'];
                        $money_log['integral'] = $user['integral'];
                        $money_log['log_type'] = -4;
                        $res = db('user_money_log')->insert($money_log);
                        if(!$res){
                            Db::rollback();
                            $this->error('写入日志出错，请稍后重试');
                        }
                        $res = db('user')->where('id',$uid)->setField('user_money',$data['user_money']);
                        if($res === false){
                            Db::rollback();
                            $this->error('更改余额出错');
                        }
                    }
                    $user = db('user')->where('id',$uid)->find();
                    if($data['change_deposit']>0){
                        //加钱
                        $surplus = $user['deposit'] + $data['change_deposit'];
                        $data['deposit'] = $surplus;
                        $money_log['user_id'] = $uid;
                        $money_log['agentId'] = $user['agentId'];
                        $money_log['create_time'] = time();
                        $money_log['coin'] = $data['change_deposit'];
                        $money_log['type'] = 0;//收入
                        $money_log['status'] = 0;//金额
                        $money_log['notes'] = '系统加钱';
                        $money_log['user_money'] = $user['user_money'];
                        $money_log['deposit'] = $surplus;
                        $money_log['integral'] = $user['integral'];
                        $money_log['log_type'] = 3;
                        $res = db('user_money_log')->insert($money_log);
                        if(!$res){
                            Db::rollback();
                            $this->error('写入日志出错，请稍后重试');
                        }
                    }else if($user['deposit']>0 && $data['change_deposit']<0){
                        //减钱
                        $surplus = $user['deposit'] - abs($data['change_deposit']);//剩余
                        if ($surplus < 0.01) {
                            $this->error('保证金不足，不能扣掉这么多');
                        }
                        $data['deposit'] = $surplus;
                        $money_log['user_id'] = $uid;
                        $money_log['agentId'] = $user['agentId'];
                        $money_log['create_time'] = time();
                        $money_log['coin'] = $data['change_deposit'];
                        $money_log['type'] = 1;//支出
                        $money_log['status'] = 0;//金额
                        $money_log['notes'] = '系统扣钱';
                        $money_log['user_money'] = $user['user_money'];
                        $money_log['deposit'] = $surplus;
                        $money_log['integral'] = $user['integral'];
                        $money_log['log_type'] = -4;
                        $res = db('user_money_log')->insert($money_log);
                        if(!$res){
                            Db::rollback();
                            $this->error('写入日志出错，请稍后重试');
                        }
                    }

                    $user = db('user')->where('id',$uid)->find();
                    if($data['change_cl_money']>0){
                        //加钱
                        $surplus = $user['cl_money'] + $data['change_cl_money'];
                        $data['cl_money'] = $surplus;
                        $money_log['user_id'] = $uid;
                        $money_log['agentId'] = $user['agentId'];
                        $money_log['create_time'] = time();
                        $money_log['coin'] = $data['change_cl_money'];
                        $money_log['type'] = 0;//收入
                        $money_log['status'] = 0;//金额
                        $money_log['notes'] = '系统加钱';
                        $money_log['user_money'] = $surplus;
                        $money_log['deposit'] = $user['deposit'];
                        $money_log['integral'] = $user['integral'];
                        $money_log['log_type'] = 3;
                        $res = db('user_money_log')->insert($money_log);
                        if(!$res){
                            Db::rollback();
                            $this->error('写入日志出错，请稍后重试');
                        }
                        $res = db('user')->where('id',$uid)->setField('cl_money',$data['cl_money']);
                        if($res === false){
                            Db::rollback();
                            $this->error('更改余额出错');
                        }
                    }else if($user['cl_money']>0 && $data['change_cl_money']<0){
                        //减钱
                        $surplus = $user['cl_money'] - abs($data['change_cl_money']);//剩余
                        if ($surplus < 0.01) {
                            $this->error('余额不足，不能扣掉这么多');
                        }
                        $data['cl_money'] = $surplus;
                        $money_log['user_id'] = $uid;
                        $money_log['agentId'] = $user['agentId'];
                        $money_log['create_time'] = time();
                        $money_log['coin'] = $data['change_cl_money'];
                        $money_log['type'] = 1;//支出
                        $money_log['status'] = 0;//金额
                        $money_log['notes'] = '系统扣钱';
                        $money_log['user_money'] = $surplus;
                        $money_log['deposit'] = $user['deposit'];
                        $money_log['integral'] = $user['integral'];
                        $money_log['log_type'] = -4;
                        $res = db('user_money_log')->insert($money_log);
                        if(!$res){
                            Db::rollback();
                            $this->error('写入日志出错，请稍后重试');
                        }
                        $res = db('user')->where('id',$uid)->setField('cl_money',$data['cl_money']);
                        if($res === false){
                            Db::rollback();
                            $this->error('更改余额出错');
                        }
                    }

                    unset($data['change_user_money']);
                    unset($data['change_deposit']);
                    unset($data['change_cl_money']);
                    Db::name('user')->where(['id' => $uid])->update($data);

                    $uid = $this->request->param('id', 0, 'intval');
                    if (cmf_get_current_admin_id() != 1 && $role_ids == 1) {
                        $this->error("为了网站的安全，非网站创建者不可创建超级管理员！");
                    } else {
                        Db::name("RoleUser")->where(["user_id" => $uid])->delete();
                        Db::name("RoleUser")->insert(["role_id" => $role_ids, "user_id" => $uid]);
                    }
                }
                Db::commit();//提交事务

            } catch (\Exception $e) {
                Db::rollback(); //事务回滚
                $this->error('操作失败'.$e->getMessage());
            }
            $this->success("保存成功！");
        } else {
            $this->error('请求错误');
        }
    }

    /**
     * 员工个人信息修改
     * @adminMenu(
     *     'name'   => '个人信息',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '员工个人信息修改',
     *     'param'  => ''
     * )
     */
    public function userInfo()
    {
        $id = cmf_get_current_admin_id();
        $user = Db::name('user')->where(["id" => $id])->find();
        $this->assign($user);
        return $this->fetch();
    }

    /**
     * 员工个人信息修改提交
     * @adminMenu(
     *     'name'   => '员工个人信息修改提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '员工个人信息修改提交',
     *     'param'  => ''
     * )
     */
    public function userInfoPost()
    {
        if ($this->request->isPost()) {

            $data = $this->request->post();
            $data['birthday'] = strtotime($data['birthday']);
            $data['id'] = cmf_get_current_admin_id();
            $create_result = Db::name('user')->update($data);;
            if ($create_result !== false) {
                $this->success("保存成功！");
            } else {
                $this->error("保存失败！");
            }
        }
    }

    /**
     * 员工删除
     * @adminMenu(
     *     'name'   => '员工删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '员工删除',
     *     'param'  => ''
     * )
     */
    public function delete()
    {
        $id = $this->request->param('id', 0, 'intval');
        if ($id == 1) {
            $this->error("最高员工不能删除！");
        }

        if (Db::name('user')->delete($id) !== false) {
            Db::name("RoleUser")->where(["user_id" => $id])->delete();
            $this->success("删除成功！");
        } else {
            $this->error("删除失败！");
        }
    }

    /**
     * 停用员工
     * @adminMenu(
     *     'name'   => '停用员工',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '停用员工',
     *     'param'  => ''
     * )
     */
    public function ban()
    {
        $id = $this->request->param('id', 0, 'intval');

        if (!empty($id)) {
            Db::startTrans();
            try {
                Db::name('user')->where(["id" => $id])->setField('user_status', '0');
                Db::name("user_token")->where(["user_id" => $id])->delete();

                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                $this->error('员工停用失败！');
            }
            $this->success("员工停用成功！", url("user/index"));
//            $result = Db::name('user')->where(["id" => $id, "user_type" => 1])->setField('user_status', '0');
//            Db::name("RoleUser")->where(["user_id" => $id])->delete();
//            if ($result !== false) {
//                $this->success("员工停用成功！", url("user/index"));
//            } else {
//                $this->error('员工停用失败！');
//            }
        } else {
            $this->error('数据传入失败！');
        }
    }


    /**
     * 启用员工
     * @adminMenu(
     *     'name'   => '启用员工',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '启用员工',
     *     'param'  => ''
     * )
     */
    public function cancelBan()
    {
        $id = $this->request->param('id', 0, 'intval');
        if (!empty($id)) {
            $result = Db::name('user')->where(["id" => $id])->setField('user_status', '1');
            if ($result !== false) {
                $this->success("员工启用成功！", url("user/index"));
            } else {
                $this->error('员工启用失败！');
            }
        } else {
            $this->error('数据传入失败！');
        }
    }

    /**
     * 标记会员
     */
    public function postImportant()
    {
        $id = $this->request->param('id', 0, 'intval');
        $type = $this->request->param('type');
        if (!empty($id)) {
            if ($type == 1) {
                $result = Db::name('user')->where(["id" => $id])->setField('important', '1');
                if ($result !== false) {
                    $this->success("标记成功！", url("user/index"));
                } else {
                    $this->error('标记失败！');
                }
            } else {
                $result = Db::name('user')->where(["id" => $id])->setField('important', '0');
                if ($result !== false) {
                    $this->success("取消成功！", url("user/index"));
                } else {
                    $this->error('取消失败！');
                }
            }

        } else {
            $this->error('数据传入失败！');
        }
    }

    //会员审核页面
    public function userReview()
    {
        $where["user_type"] = 1;
        $where['user_status'] = 3;
        /**搜索条件**/
        $userLogin = $this->request->param('user_login');
        $userEmail = trim($this->request->param('user_email'));

        if ($userLogin) {
            $where['user_login'] = ['like', "%$userLogin%"];
        }

        if ($userEmail) {
            $where['user_email'] = ['like', "%$userEmail%"];;
        }
        $field = 'u.*,p.province,c.city,a.area';
        $users = Db::name('user')
            ->alias('u')
            ->join('province p', 'u.province = p.provinceID', 'left')
            ->join('city c', 'u.city = c.cityID', 'left')
            ->join('area a', 'u.area = a.areaID', 'left')
            ->where($where)
            ->field($field)
            ->order("id DESC")
            ->paginate(10, false, ['query' => array('user_login' => $userLogin, 'user_email' => $userEmail)]);
        $users->appends(['user_login' => $userLogin, 'user_email' => $userEmail]);
        // 获取分页显示
        $page = $users->render();

        $rolesSrc = Db::name('role')->select();
        $roles = [];
        foreach ($rolesSrc as $r) {
            $roleId = $r['id'];
            $roles["$roleId"] = $r;
        }
        $this->assign("page", $page);
        $this->assign("roles", $roles);
        $this->assign("users", $users);
        return $this->fetch();
    }

    //会员审核操作
    public function userReviewDo()
    {
        $request = $this->request;
        if ($request->param('id')) {
            $id = $request->param('id');
            $data['user_status'] = 1;
            $re = Db::name("user")->where(["id" => $id])->update($data);
            $re ? $this->success('审核成功') : $this->error('审核失败');
        } else {
            $this->error('非法请求');
        }
    }


    public function exportUserlistExcel()
    {
//        $where['mc_user.user_type'] = ['in', ['1', '4', '5']];
//        /**搜索条件**/
//        $user_id = $this->request->param('user_id');
//        $userLogin = $this->request->param('user_login');
//        $mobile = $this->request->param('mobile');
//        $agentId = $this->request->param('agentId');
//        $vip_type = $this->request->param('vip_type');
//        $this->assign('vip_type', $vip_type);
//        $important = $this->request->param('important');
//        $this->assign('important', $important);
//        $repeat = $this->request->param('repeat');
//        $this->assign('repeat', $repeat);
//
//        if ($vip_type) {
//            $where['mc_user.vip_type'] = $vip_type;
//        }
//
//        if ($user_id) {
//            $where['mc_user.id'] = $user_id;
//        }
//
//        if ($userLogin) {
//            $where['mc_user.user_login'] = ['like', "%$userLogin%"];
//        }
//
//        if ($mobile) {
//            $where['mc_user.mobile'] = ['like', "%$mobile%"];
//        }
//
//        if ($agentId) {
//            $where['mc_user.agentId'] = $agentId;
//        }
//
//        if ($important) {
//            $where['u.important'] = $important - 1;
//        }
//
//        if ($repeat) {
//            $where['u.repeat'] = $repeat - 1;
//        }
//        $admin_id = cmf_get_current_admin_id();
//        $this->assign('sign_id', $admin_id);
//
//        $field = 'u.id,u.user_login,u.mobile,p.province,u.user_type,u.create_time,u.last_login_ip,u.last_login_time,u.user_money,u.yong_money,u.apply_name,u.apply_account,u.agentId,up.user_login up_name,ug.user_login ug_name,u.user_status,u.user_url,c.city,a.area,u.pid,u.vip_type,u.important';
//        if ($admin_id == 1) {
//            $data = Db::name('user')
//                ->alias('u')
//                ->join('province p', 'u.province = p.provinceID', 'left')
//                ->join('city c', 'u.city = c.cityID', 'left')
//                ->join('area a', 'u.area = a.areaID', 'left')
//                ->join('mc_user up', 'u.pid = up.id', 'left')
//                ->join('mc_user ug', 'u.agentId = ug.id', 'left')
//                ->where($where)
//                ->field($field)
//                ->order("u.id DESC")
//                ->select()->toarray();
//        } else {
//            $data = Db::name('user')
//                ->alias('u')
//                ->join('province p', 'u.province = p.provinceID', 'left')
//                ->join('city c', 'u.city = c.cityID', 'left')
//                ->join('area a', 'u.area = a.areaID', 'left')
//                ->join('mc_user up', 'u.pid = up.id', 'left')
//                ->join('mc_user ug', 'u.agentId = ug.id', 'left')
//                ->where($where)
//                ->where('u.pid|u.agentId', 'eq', $admin_id)
//                ->field($field)
//                ->order("u.id DESC")
//                ->select()->toarray();
//        }
//
//        foreach ($data as $k => $v) {
//            $data[$k]['province'] = $v['province'] . $v['city'] . $v['area'];
//            $data[$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
//
//            if ($v['last_login_ip'] == '') {
//                $data[$k]['last_login_ip'] = '暂无登陆记录';
//            }
//            if ($v['last_login_time'] == '') {
//                $data[$k]['last_login_time'] = '暂无登陆记录';
//            } else {
//                $data[$k]['last_login_time'] = date('Y-m-d H:i:s', $v['last_login_time']);
//            }
//
//            if ($v['vip_type'] == 1) {
//                $data[$k]['user_type'] = '普通会员';
//            } elseif ($v['vip_type'] == 2) {
//                $data[$k]['user_type'] = 'VIP会员';
//            } elseif ($v['vip_type'] == 3) {
//                $data[$k]['user_type'] = '代理商';
//            } elseif ($v['vip_type'] == 4) {
//                $data[$k]['user_type'] = '股东';
//            }
//
//            if ($v['user_status'] == 0) {
//                $data[$k]['user_status'] = '禁用';
//            } elseif ($v['user_status'] == 1) {
//                $data[$k]['user_status'] = '正常';
//            } elseif ($v['user_status'] == 2) {
//                $data[$k]['user_status'] = '未验证';
//            } else {
//                $data[$k]['user_status'] = '待审核';
//            }
//
//            unset($data[$k]['user_url'], $data[$k]['city'], $data[$k]['area'], $data[$k]['pid'], $data[$k]['agentId'], $data[$k]['vip_type']);
        $field = 'id,user_nickname,mobile,create_time,last_login_ip,last_login_time,user_money,deposit,income,apply_account,apply_name,pid,user_status';
        $data = db('user')
            ->field($field)
            ->order('id desc')
            ->select()->each(function ($item) {
                $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
                $item['last_login_time'] = date('Y-m-d H:i:s', $item['last_login_time']);
                if ($item['user_status'] == 0) {
                    $item['user_status'] = '禁用';
                } elseif ($item['user_status'] == 1) {
                    $item['user_status'] = '正常';
                }
                return $item;
            });
//            dump($data);die;

        $fileName = '会员列表_' . date('YmdHis');
        $header = array(
            '用户ID', '用户名', '手机号', '创建时间', '最后登录IP',
            '最后登录时间', '账户余额', '账户佣金', '收入','支付宝姓名', '支付宝账号', '上级昵称', '代理商昵称', '状态',
        );

        $this->exportExcel($data, $fileName, $header, '会员列表');
//        }
    }


    /**
     * 导出excel
     * @param array $data 导入数据
     * @param string $savefile 导出excel文件名
     * @param array $fileheader excel的表头
     * @param string $sheetname sheet的标题名
     */
    public function exportExcel($data, $savefile, $fileheader, $sheetname)
    {
        //引入phpexcel核心文件，不是tp，你也可以用include（‘文件路径’）来引入
        import("Org.Util.PHPExcel");
        import("Org.Util.PHPExcel.Reader.Excel2007");
        //或者excel5，用户输出.xls，不过貌似有bug，生成的excel有点问题，底部是空白，不过不影响查看。
        //import("Org.Util.PHPExcel.Reader.Excel5");
        //new一个PHPExcel类，或者说创建一个excel，tp中“\”不能掉
        $excel = new \PHPExcel();
        if (is_null($savefile)) {
            $savefile = time();
        } else {
            //防止中文命名，下载时ie9及其他情况下的文件名称乱码
            iconv('UTF-8', 'GB2312', $savefile);
        }
        //设置excel属性
        $objActSheet = $excel->getActiveSheet();
        //根据有生成的excel多少列，$letter长度要大于等于这个值
        $letter = array(
            'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M',
            'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
            'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM',
            'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ'
        );
        //设置当前的sheet
        $excel->setActiveSheetIndex(0);
        //设置sheet的name
        $objActSheet->setTitle($sheetname);
        //设置表头
        for ($i = 0; $i < count($fileheader); $i++) {
            //单元宽度自适应,1.8.1版本phpexcel中文支持勉强可以，自适应后单独设置宽度无效
            //$objActSheet->getColumnDimension("$letter[$i]")->setAutoSize(true);
            //设置表头值，这里的setCellValue第二个参数不能使用iconv，否则excel中显示false
            $objActSheet->setCellValue("$letter[$i]1", $fileheader[$i]);
            //设置表头字体样式
            $objActSheet->getStyle("$letter[$i]1")->getFont()->setName('微软雅黑');
            //设置表头字体大小
            $objActSheet->getStyle("$letter[$i]1")->getFont()->setSize(12);
            //设置表头字体是否加粗
            $objActSheet->getStyle("$letter[$i]1")->getFont()->setBold(true);
            //设置表头文字垂直居中
            $objActSheet->getStyle("$letter[$i]1")->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            //设置文字上下居中
            $objActSheet->getStyle($letter[$i])->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
            //设置表头外的文字垂直居中
            $excel->setActiveSheetIndex(0)->getStyle($letter[$i])->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        }
        //单独设置D列宽度为15
        $objActSheet->getColumnDimension('B')->setWidth(25);
        $objActSheet->getColumnDimension('D')->setWidth(25);
        $objActSheet->getColumnDimension('H')->setWidth(30);
        $objActSheet->getColumnDimension('I')->setWidth(30);
        $objActSheet->getColumnDimension('J')->setWidth(30);
        $objActSheet->getColumnDimension('K')->setWidth(30);
        $objActSheet->getColumnDimension('L')->setWidth(30);
        //这里$i初始值设置为2，$j初始值设置为0，自己体会原因
        for ($i = 2; $i <= count($data) + 1; $i++) {
            $j = 0;
            foreach ($data[$i - 2] as $key => $value) {
                //不是图片时将数据加入到excel，这里数据库存的图片字段是img
                if ($key != 'img') {
                    $objActSheet->setCellValue("$letter[$j]$i", $value);
                }
                //是图片是加入图片到excel
                if ($key == 'img') {
                    if ($value != '') {
                        $value = iconv("UTF-8", "GB2312", $value); //防止中文命名的文件
                        // 图片生成
                        $objDrawing[$key] = new \PHPExcel_Worksheet_Drawing();
                        // 图片地址
                        $objDrawing[$key]->setPath('.\Uploads' . $value);
                        // 设置图片宽度高度
                        $objDrawing[$key]->setHeight('80px'); //照片高度
                        $objDrawing[$key]->setWidth('80px'); //照片宽度
                        // 设置图片要插入的单元格
                        $objDrawing[$key]->setCoordinates('D' . $i);
                        // 图片偏移距离
                        $objDrawing[$key]->setOffsetX(12);
                        $objDrawing[$key]->setOffsetY(12);
                        //下边两行不知道对图片单元格的格式有什么作用，有知道的要告诉我哟^_^
                        //$objDrawing[$key]->getShadow()->setVisible(true);
                        //$objDrawing[$key]->getShadow()->setDirection(50);
                        $objDrawing[$key]->setWorksheet($objActSheet);
                    }
                }
                $j++;
            }
            //设置单元格高度，暂时没有找到统一设置高度方法
            $objActSheet->getRowDimension($i)->setRowHeight('80px');
        }
        header('Content-Type: application/vnd.ms-excel');
//        下载的excel文件名称，为Excel5，后缀为xls，不过影响似乎不大
        header('Content-Disposition: attachment;filename="' . $savefile . '.xlsx"');
        header('Cache-Control: max-age=0');
        // 用户下载excel
        ob_clean();
        $objWriter = \PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
        $objWriter->save('php://output');
        // 保存excel在服务器上
        //$objWriter = new PHPExcel_Writer_Excel2007($excel);
        //或者$objWriter = new PHPExcel_Writer_Excel5($excel);
        //$objWriter->save("保存的文件地址/".$savefile);
    }

    //指派任务
    public function release_task()
    {
        $post = input();
        //指派的用户
        $user = $post['uid'];
        //指派的任务
        $task = explode(',', str_replace("，", ",", $post['task']));

        foreach ($task as $k => $v) {
            $time = db('task_branch')->where(['id' => $v])->value('b_begin_time');
            $ss[$k] = substr($time, 0, 10);

            foreach (array_count_values($ss) as $kk => $vv) {
                if ($vv > 2) {
                    unset($task[$k]);
                }
            }
        }

        //查看用户当天是否已经接过任务
        $eli = [];
        foreach ($user as $k => $v) {
            foreach ($task as $kt => $vt) {
                $cond = db('task_receive')->where(['uid' => $v, 'bid' => $vt])->find();
                if ($cond) {
                    $eli[$kt]['user'] = $v;
                    $eli[$kt]['task'] = $vt;
                }
            }
        }

        if (empty($task) || empty($user)) {
            $this->error('请确认所选用户及任务id无误');
        }
        //首先假设所有条件无误
        foreach ($task as $k => $v) {
            foreach ($user as $k1 => $v1) {
                $aa['task'] = $v;
                $aa['user'] = $v1;
                if (!in_array($aa, $eli)) {
                    $add[$k1]['uid'] = $v1;
                    $add[$k1]['bid'] = $v;
                    $add[$k1]['receive_time'] = time();
                    $add[$k1]['receive_type'] = -1;
                } else {
                    unset($add[$k1]);
                }
            }
            $res = db('task_receive')->insertAll($add);
            $tid = db('task_branch')->where(['id' => $v])->value('tid');
            $rec = Db::name('task')->where(['id' => $tid])->setInc('com_num', $res);
        }

        if ($res) {
            $this->success('指派成功');
        } else {
            $this->error('指派失败');
        }
    }

    //提升会员等级操作
    public function top_vip()
    {
        $request = $this->request;
        $id = $request->param('uid');
        if ($id) {
            $type = $request->param('vip_type');
            $user = db('user')->where(['id' => $id, 'user_status' => 1])->find();
            if ($user) {

                if ($user['vip_type'] == 4) {
                    $this->error('该用户已经是股东，无法再进行升级了');
                }
                if ($type == $user['vip_type']) {
                    $this->error('您已经是这个身份的会员了');
                }
                Db::startTrans();

                try {
                    if ($type) {
                        $add['vip_type'] = $type;
                        if ($type != 2) {
                            $add['user_type'] = $type + 1;
                        } else {
                            $add['user_type'] = 1;
                        }
                        if ($type == 4) {
                            $add['vip_end_time'] = strtotime("+100 year");
                        } else {
                            $add['vip_end_time'] = strtotime("+1 year");
                        }

                        $res = Db::name('user')->where(['id' => $id])->update($add);

                        if (cmf_get_current_admin_id() != 1 && $add['user_type'] == 1) {
                            $this->error("为了网站的安全，非网站创建者不可创建超级管理员！");
                        }

                        Db::name("RoleUser")->where(["user_id" => $id])->delete();
                        Db::name("RoleUser")->insert(["role_id" => $add['user_type'], "user_id" => $id]);
                        Db::commit();
                    } else {
                        $this->error('请选择等级！');
                    }

                } catch (\Exception $e) {
                    Db::rollback();
                    $this->error('升级失败！');
                }
                $this->error('升级成功！');

            } else {
                $this->error('请先确认用户信息！');
            }
        } else {
            $this->error('非法请求！');
        }
    }

    //会员游戏收益记录
    public function index_game()
    {
        $id = $this->request->param('user_id');
        $user = db('user')->where(['id' => $id])->field('mobile,id')->find();

        $list = db('game')->alias('A')
            ->join('mc_task B', 'A.id = B.game_id', 'left')
            ->join('mc_task_branch C', 'B.id = C.tid', 'left')
            ->join('mc_task_receive D', 'C.id = D.bid')
            ->field('A.id,A.title,sum(C.b_official_money) as official,sum(C.b_money) as platform')
            ->where(['D.uid' => $id, 'D.receive_type' => 3])
            ->group('A.title')
            ->paginate(100);

        $list = $list->toArray();
        $arr = ['id' => '总计', 'title' => '', 'official' => 0, 'platform' => 0];
        foreach ($list['data'] as $k => $v) {
            $arr['official'] += $v['official'];
            $arr['platform'] += $v['platform'];

        }
        $list['data'][] = $arr;

        session('userGame', $list['data']);

        $this->assign('user', $user);
        $this->assign('list', $list);
        return $this->fetch();
    }


    //会员游戏收益记录
    public function exportUserGameExcel()
    {
        $data = session('userGame');

        foreach ($data as $k => $v) {

            $fileName = '会员收益记录表_' . date('YmdHis');
            $header = array(
                '游戏ID', '游戏名称', '官方金额合计', '任务金额合计',
            );

            $this->exportExcel($data, $fileName, $header, '会员收益记录表');
        }
    }

    // 从林会员分类管理
    function sortindex()
    {

        $uid = $this->request->param('id');
        $sorts = db('self_task_sort')->alias('s')
            ->join('task_sort_money m','s.id = m.sortid and m.uid = '.$uid,'left')
            ->where('s.pid','<>',0)
            ->field("s.*,m.fabumoney,m.appmoney")
            ->paginate(15);
        $this->assign('userid',$this->request->param('id'));
        $this->assign('sorts',$sorts);
        $this->assign('page',$sorts->render());
        return $this->fetch();
    }

    // 修改金额
    function editmoney()
    {
        if ($this->request->isPost()){
            $data = $this->request->param();
            $sortdata = [
                'fabumoney' => $data['dmoney'],
                'appmoney' => $data['falsemoney'],
            ];
            if (db('task_sort_money')->where('uid',$data['uid'])->where('sortid',$data['sid'])->find()){

                db('task_sort_money')->where('uid',$data['uid'])->where('sortid',$data['sid'])->update($sortdata);
                $this->success('修改成功');
            } else {
                $sortdata['uid'] = $data['uid'];
                $sortdata['sortid'] = $data['sid'];
                db("task_sort_money")->insert($sortdata);
                $this->success('修改成功');
            }
        }
    }
}