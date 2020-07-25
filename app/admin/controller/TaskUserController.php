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


class TaskUserController extends AdminBaseController
{

    /**
     * 后台发任务用户
     */
    function index()
    {
        $where['user_type'] = ['not in', [1,2,3]];
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
            ->join('__ROLE_USER__ B', 'A.id = B.user_id','left')
            ->join('__ROLE__ C', 'B.role_id = C.id')
            ->where($where)
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
     * 添加后台发任务用户
     */
    function addTaskUser()
    {
        if ($this->request->isPost()){
            if (!empty($_POST['role_id']) && is_array($_POST['role_id'])) {
                $role_ids = $_POST['role_id'];
                unset($_POST['role_id']);
                $result = $this->validate($this->request->param(), 'User');
                if ($result !== true) {
                    $this->error($result);
                } else {
                    $_POST['user_type'] = 4;
                    $_POST['user_pass'] = cmf_password($_POST['user_pass']);
                    $result = DB::name('user')->insertGetId($_POST);
                    if ($result !== false) {
                        foreach ($role_ids as $role_id) {
                            if (cmf_get_current_admin_id() != 1 && $role_id == 1) {
                                $this->error("为了网站的安全，非网站创建者不可创建超级管理员！");
                            }
                            Db::name('RoleUser')->insert(["role_id" => $role_id, "user_id" => $result]);
                        }
                        $this->success("添加成功！", url("TaskUser/index"));
                    } else {
                        $this->error("添加失败！");
                    }
                }
            } else {
                $this->error("请为此用户指定角色！");
            }
        }
        $roles = Db::name('role')->where(['status' => 1, 'type' => 1])->order("id DESC")->select();
        $this->assign("roles", $roles);
        return $this->fetch();
    }

    /**
     * 编辑任务会员
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

    public function editPost()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
//            dump($data);die;
            $uid = $this->request->param('id');
            $user = db('user')->where(['id' => $uid])->find();
            $data['change_user_money'] = isset($data['change_user_money']) ? $data['change_user_money'] : 0;
            $data['change_deposit'] = isset($data['change_deposit']) ? $data['change_deposit'] : 0;

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

            Db::startTrans();
            try {
                if (empty($data['user_pass'])) {
                    unset($data['user_pass']);
                } else {
                    $tmp = $data['user_pass'];
                }

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

                    unset($data['change_user_money']);
                    unset($data['change_deposit']);
                    Db::name('user')->where(['id' => $uid])->update($data);

                    $uid = $this->request->param('id', 0, 'intval');
                    if (cmf_get_current_admin_id() != 1 ) {
                        $this->error("为了网站的安全，非网站创建者不可创建超级管理员！");
                    } else {

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
     * 删除任务会员
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
            $this->success("员工停用成功！", url("TaskUser/index"));
        } else {
            $this->error('数据传入失败！');
        }
    }


    /**
     * 启用任务会员
     */
    public function cancelBan()
    {
        $id = $this->request->param('id', 0, 'intval');
        if (!empty($id)) {
            $result = Db::name('user')->where(["id" => $id])->setField('user_status', '1');
            if ($result !== false) {
                $this->success("员工启用成功！", url("TaskUser/index"));
            } else {
                $this->error('员工启用失败！');
            }
        } else {
            $this->error('数据传入失败！');
        }
    }


    /**
     *   任务会员信息主页
     */

    function info()
    {
        $adminUser = db('user')->where('id',session('ADMIN_ID'))->value('user_type');

        if ($adminUser == 3 || $adminUser == 2){
            $map = '';
        } else {
            $map =  session('ADMIN_ID');
            $adminUserinfo = db('user')->field('id,user_login,user_nickname,user_money,deposit')->find($map);
            $this->assign('userinfo',$adminUserinfo);
            return $this->fetch();
        }
    }

    function czmoney()
    {
        return $this->fetch();
    }

    /**
     *  任务会员充值记录
     */
    function order()
    {
        $param = $this->request->param();
        $oid = request()->param('orderid');
        $where = [];
        if ($oid) $where['WIDout_trade_no'] = $oid;
        $param['start_time'] = isset($param['start_time']) ? $param['start_time'] : '';
        $param['end_time'] = isset($param['end_time']) ? $param['end_time'] : '';

        $startTime = empty($param['start_time']) ? 0 : strtotime($param['start_time']);
        $endTime = empty($param['end_time']) ? 0 : strtotime($param['end_time']);

        $where = [];

        if (!empty($startTime) && !empty($endTime)) {
            $where['create_time'] = [['>= time', $startTime], ['<= time', $endTime]];
        } else {
            if (!empty($startTime)) {
                $where['create_time'] = ['>= time', $startTime];
            }
            if (!empty($endTime)) {
                $where['create_time'] = ['<= time', $endTime];
            }
        }

        $loglist = db('task_user_order')->where($where)->where('user_id',session('ADMIN_ID'))->order('id desc')->paginate(10);
        $this->assign([
            'page' => $loglist->render(),
            'list' => $loglist,
        ]);
        return $this->fetch();
    }

    /**
     * 金额日志
     */
    function moneylog()
    {
        $adminUser = db('user')->where('id',session('ADMIN_ID'))->value('user_type');

        if ($adminUser == 3 || $adminUser == 2){

        } else {
            $param = $this->request->param();

            $param['start_time'] = isset($param['start_time']) ? $param['start_time'] : '';
            $param['end_time'] = isset($param['end_time']) ? $param['end_time'] : '';

            $startTime = empty($param['start_time']) ? 0 : strtotime($param['start_time']);
            $endTime = empty($param['end_time']) ? 0 : strtotime($param['end_time']);

            $where = [];

            if (!empty($startTime) && !empty($endTime)) {
                $where['l.create_time'] = [['>= time', $startTime], ['<= time', $endTime]];
            } else {
                if (!empty($startTime)) {
                    $where['l.create_time'] = ['>= time', $startTime];
                }
                if (!empty($endTime)) {
                    $where['l.create_time'] = ['<= time', $endTime];
                }
            }

            $list = Db::name('user_money_log')->alias('l')
                ->join('user u','l.user_id = u.id','left')
                ->where('l.user_id',session('ADMIN_ID'))
                ->where($where)
                ->order('l.id desc')
                ->field('l.*,u.user_nickname,mobile')
                ->paginate(15);
            $this->assign('list', $list);
            $this->assign('page', $list->render());
            return $this->fetch();
        }
    }

    // 任务会员分类管理
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
            $res = db('task_sort_money')->where('uid',$data['uid'])->where('sortid',$data['sid'])->find();
            if ($res){
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