<?php

namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;


class TaskController extends AdminBaseController
{
    /**
     * 任务列表
     */
    public function index()
    {
        $request = $this->request;

        $title = $request->param('title');//搜索任务名称

        $game_id = $request->param('game_id');//搜索游戏编号

        $create_time = $request->param('create_time');

        $recharge = $request->param('recharge');//搜索任务类型
        $this->assign('recharge', $recharge);

        $invalid = $request->param('invalid');//搜索任务状态
        $this->assign('invalid', $invalid);

        if ($title != '') {
            $where['a.title'] = ['like', '%' . $title . '%'];
        }
        if ($game_id != '') {
            $where['a.game_id'] = ['like', '%' . $game_id . '%'];
        }
        if($create_time){
            $beg = $create_time;
            $end = date('Y-m-d',strtotime($beg."+1 day"));
            $where['a.create_time'] = ['between time', [$beg,$end]];
        }
        if ($recharge != '') {
            $where['a.recharge'] = $recharge;
        }
        if($invalid != ''){
            $where['a.invalid'] = $invalid;
        }

        $where['a.isdelete'] = 0;
        $list = db('task')
            ->alias('a')
            ->field('a.*,b.title gtitle')
            ->where($where)
            ->join('__GAME__ b', 'a.game_id=b.id','left')
            ->order('a.id desc')
            ->paginate(50, false, ['query' => array('title' => $title, 'game_id' => $game_id, 'recharge' => $recharge,'invalid' => $invalid,'create_time' => $create_time)]);
        $page = $list->render();

        $this->assign('list', $list);
        $this->assign('page', $page);
        return $this->fetch();
    }

    public function add()
    {
        $cat = db('cat')->where(['isdelete' => 0])->select();
        $this->assign('cat', $cat);
        return $this->fetch();
    }

    public function addPost()
    {
        $post = input('post.');
        $data = $post['post'];

        if (empty($data['title'])) {
            $this->error('标题不能为空');
        }
        if(empty($data['game_id'])){
            $this->error('请搜索并选择一个游戏');
        }
        if (empty($data['thumb'])) {
            $this->error('请上传图像');
        }
        if (empty($data['platform'])) {
            $this->error('请输入游戏平台');
        }
        if (empty($data['task_money'])) {
            $this->error('任务金额');
        }
        if (empty($data['recharge'])) {
            $this->error('请选择任务类型');
        }
        if ($data['recharge'] == 1) {
            if (empty($data['settle_type'])) {
                $this->error('请选择结算方式');
            }
        } else {
            $data['settle_type'] = 1;
        }

        if ($data['recharge'] != 3) {
            if (empty($data['garea'])) {
                $this->error('请输入游戏区服');
            }
        }

        if (empty($data['task_link'])) {
            $this->error('请填写下载地址');
        }
        if (empty($data['begin_time'])) {
            $this->error('请填写开始时间');
        }
        if (empty($data['end_time'])) {
            $this->error('请填写结束时间');
        }
        if(strtotime($data['begin_time'])>=strtotime($data['end_time'])){
            $this->error('结束时间应大于开始时间');
        }
        if (empty($data['remark'])) {
            $this->error('请填写任务描述');
        }
        $data['create_id'] = cmf_get_current_admin_id();
        $data['create_time'] = time();
        $data['recharge'] = $data['recharge'] - 1;

        $res = db('task')->insert($data);
        if ($res) {
            $this->success('任务添加成功', url('task/index'));
        } else {
            $this->error('任务添加失败');
        }
    }

    public function edit()
    {
        $id = input('id');
        if ($id > 0) {
            $cat = db('cat')->where(['isdelete' => 0])->select();
            $this->assign('cat', $cat);

            $task = db('task')->where(['id' => $id])->find();
            if (empty($task) || $task['isdelete'] == 1) {
                $this->error('该任务已失效', url('task/index'));
            }
            $this->assign('task', $task);
            return $this->fetch();
        } else {
            $this->error('请求错误', url('task/index'));
        }
    }

    public function editPost()
    {
        $post = input();
        $data = $post['post'];

        if ($data['id'] > 0) {
            if(empty($data['game_id'])){
                $this->error('请搜索并选择一个游戏');
            }
            if ($data['thumb'] == "") {
                unset($data['thumb']);
            }
            if (empty($data['begin_time'])) {
                $this->error('请填写开始时间');
            }
            if (empty($data['end_time'])) {
                $this->error('请填写结束时间');
            }
            if(strtotime($data['begin_time'])>=strtotime($data['end_time'])){
                $this->error('结束时间应大于开始时间');
            }
            if ($data['recharge'] == 2) {
                $stage = db('task')->where(['id' => $data['id']])->value('stage');
                if ($stage > 1) {
                    $this->error('该任务不符合标准，不可修改为充值任务');
                }
            }



            $data['recharge'] = $data['recharge'] - 1;

            $data['modify_id'] = cmf_get_current_admin_id();
            $data['modify_time'] = time();

            $res = db('task')->update($data);
            if ($res) {
                $this->success('任务编辑成功', url('task/index'));
            } else {
                $this->error('任务编辑失败');
            }
        } else {
            $this->error('请求错误', url('task/index'));
        }
    }

    public function delete()
    {
        $id = input('id');
        if ($id > 0) {
            $t_count = db('task_receive') -> where(['tid' => $id]) -> count();
            if($t_count > 0){
                $this->error('此任务已被接取，无法删除');
            }
            $data['isdelete'] = 1;
            $data['modify_id'] = cmf_get_current_admin_id();
            $data['modify_time'] = time();

            $res = db('task')->where(['id' => $id])->update($data);
            if ($res) {
                $this->success('删除成功', url('task/index'));
            } else {
                $this->error('删除失败');
            }
        } else {
            $this->error('请求错误', url('task/index'));
        }
    }

    //任务上架下架操作
    public function invalid()
    {
        $id = $this->request->param('id');
        if($id){
            $invalid = db('task') -> where(['id' => $id]) -> value('invalid');

            Db::startTrans();
            try{
                if($invalid == 0){
                    //下架
                    $data['invalid'] = 1;
                    $branch = db('task_branch') -> alias('A')
                        -> join('task_receive B','A.id = B.bid')
                        -> join('task C','C.id = A.tid')
                        -> where(['A.tid' => $id,'receive_type' => 0])
                        -> field('A.id,B.uid,B.termination,C.title')
                        -> select();
                    foreach($branch as $k => $v){
                        $end = $v['termination'];
                        if($end - time() > 60*60*2){
                            Db::name('task_receive') -> where(['bid' => $v['id']]) -> update(['termination' => time()+7200]);
                        }
                        //任务下架 通知已接任务的用户消息
                        $adds['uid'] = $v['uid'];
                        $adds['bid'] = $v['id'];
                        $adds['time'] = time();
                        $adds['type'] = 0;
                        $adds['status'] = 0;
                        $adds['news'] = '您接取的任务"'.$v['title'].'"已经下架，请在两个小时之内提交，以便结算奖励';

                        Db::name('news') -> insert($adds);
                    }
                }else{
                    //上架
                    $data['invalid'] = 0;
                }
                $data['modify_id'] = cmf_get_current_admin_id();
                $data['modify_time'] = time();

                $res = Db::name('task')->where(['id' => $id])->update($data);

                Db::commit();
            }catch(\Exception $e){
                Db::rollback();
                $this->error('编辑失败');
            }
            $this->success('编辑成功', url('task/index'));
        }else{
            $this->error('请求错误', url('task/index'));
        }
    }
    //任务期数列表
    public function branch()
    {
        $id = input('id');
        if (empty($id)) {
            $id = session('task_id');
        } else {
            session('task_id', $id);
        }
        $this->assign('id', $id);

        $request = $this->request;

        $title = $request->post('title');//搜索用户姓名
        $game_id = $request->post('game_id');

        if ($title != '') {
            $where['b_title'] = ['like', '%' . $title . '%'];
        }
        if ($game_id != '') {
            $where['game_id'] = ['like', '%' . $game_id . '%'];
        }


        $where['b.tid'] = $id;
        $where['b.b_isdelete'] = 0;

        $branch = db('task_branch')->alias('b')
            ->join('mc_task t', 'b.tid = t.id', 'left')
            ->where($where)
            ->field('b.*,t.game_id,t.thumb,t.task_link,t.rel_num')
            ->order('b.id desc')->paginate(20, false, ['query' => array('title' => $title, 'game_id' => $game_id)]);
        $page = $branch->render();

        $list = $branch -> toArray();
        foreach($list['data'] as $k => $v){
            $list['data'][$k]['com_num'] = db('task_receive') -> where(['bid' => $v['id'],'receive_type' => 3]) -> count();
        }

        $this->assign('list', $list['data']);
        $this->assign('page', $page);
        return $this->fetch();
    }

    public function bran_add()
    {
        $id = input('id');
        $task = db('task')->where(['id' => $id])->value('recharge');
        $branch = db('task_branch')->where(['tid' => $id, 'b_isdelete' => 0])->count();
        if ($task == 1 || $task == 2) {
            if ($branch >= 1) {
                $this->error('充值任务只可存在一条分支任务');
            }
        }
        $thefirst = $branch ? 0 : 1;
        $this->assign('id', $id);
        $task = db('task')->where(['id' => $id])->field('title,begin_time')->find();
        $this->assign('task', $task);
        $this->assign('thefirst',$thefirst);
        return $this->fetch();
    }

    public function bran_addpost()
    {
        $request = $this->request;
        if ($request->isPost()) {
            $post = $request->param();
            $data = $post['post'];
            $task = db('task')->where(['id' => $data['tid']])->find();
            if (empty($data['b_title'])) {
                $this->error('请填写任务期数');
            }
            if (empty($data['b_money'])) {
                $this->error('请填写任务金额');
            }
            if (empty($data['b_official_money'])) {
                $this->error('请填写官方金额');
            }
            if (empty($data['b_brokerage'])) {
                $this->error('请填写佣金');
            }
            if (empty($data['b_money'])) {
                $this->error('请填写任务金额');
            }
            if (empty($data['b_begin_time'])) {
                $this->error('请确认任务开始时间');
            }
            if (empty($data['b_end_time'])) {
                $this->error('请确认任务结束时间');
            }

            if(strtotime($data['b_end_time'])>strtotime($task['end_time'])){
                $this->error('任务分期结束时间不能大于主任务结束时间');
            }
            if (empty($data['b_remark'])) {
                $this->error('请填写任务描述');
            }

            //验证图片信息
            if (empty($data['explain1']) != empty($data['e_photo1'])) {
                $this->error('请完善依据一');
            }
            if (empty($data['explain2']) != empty($data['e_photo2'])) {
                $this->error('请完善依据二');
            }
            if (empty($data['explain3']) != empty($data['e_photo3'])) {
                $this->error('请完善依据三');
            }
            if (empty($data['explain4']) != empty($data['e_photo4'])) {
                $this->error('请完善依据四');
            }

            $data['b_create_time'] = time();
            $data['b_create_id'] = cmf_get_current_admin_id();

            // 启动事务
            Db::startTrans();

            $res = Db::name('task_branch')->insert($data);
            $rec = Db::name('task')->where(['id' => $data['tid']])->setInc('stage');

            if ($res && $rec) {
                // 提交事务
                Db::commit();
                $this->success('添加成功', url('task/branch'));
            } else {
                // 回滚事务
                Db::rollback();
                $this->error('添加失败');
            }
        } else {
            $this->error('请求错误');
        }
    }

    public function bran_edit()
    {
        $id = input('id');
        $this->assign('id', session('task_id'));
        if ($id > 0) {
            $name = db('task')->where(['id' => session('task_id')])->value('title');
            $this->assign('name', $name);
            $task = db('task_branch')->where(['id' => $id])->find();
            if (empty($task) || $task['b_isdelete'] == 1) {
                $this->error('该任务已失效', url('task/index'));
            }
            $this->assign('task', $task);
            return $this->fetch();
        } else {
            $this->error('请求错误', url('task/branch'));
        }
    }

    public function bran_editpost()
    {
        $post = input('post.');
        $data = $post['post'];
        $task = db('task')->where(['id' => $data['tid']])->find();
        if ($data['id'] > 0) {
            $branch = db('task_branch') -> where(['id' => $data['id']]) -> find();
            if($data['quantity'] > $branch['quantity']){
                $data['b_invalid'] = 0;
            }

            if ($data['b_begin_time'] == "") {
                unset($data['b_begin_time']);
            }else{
                if(strtotime($data['b_end_time'])>strtotime($task['end_time'])){
                    $this->error('任务分期结束时间不能大于主任务结束时间');
                }
            }
            if ($data['b_end_time'] == "") {
                unset($data['b_end_time']);
            }
            $res = db('task_branch')->update($data);
            if ($res) {
                $this->success('编辑成功', url('task/branch'));
            } else {
                $this->error('编辑失败');
            }
        } else {
            $this->error('请求错误', url('task/branch'));
        }
    }

    public function bran_delete()
    {
        $id = input('id');
        $tid = db('task_branch') -> where(['id' => $id]) -> value('tid');
        $t_count = db('task_receive') -> where(['tid' => $tid]) -> count();
        if($t_count > 0){
            $this->error('此主任务已被接取，无法删除');
        }
        // 启动事务
        Db::startTrans();
        $data['b_isdelete'] = 1;
        $data['b_modify_id'] = cmf_get_current_admin_id();
        $data['b_modify_time'] = time();

        $res = Db::name('task_branch')->where(['id' => $id])->update($data);
        $rec = Db::name('task')->where(['id' => session('task_id')])->setDec('stage');

        if ($res && $rec) {
            // 提交事务
            Db::commit();
            $this->success('删除成功', url('task/branch'));
        } else {
            // 回滚事务
            Db::rollback();
            $this->error('删除失败');
        }
    }

    //任务下载记录
    public function download()
    {
        $request = $this->request;

        $title = $request->param('title');//搜索任务名称
        $name = $request->param('name');//搜索游戏编号

        $where['t.title'] = ['like', '%' . $title . '%'];

        if ($name) {
            $where['u.user_login'] = ['like', '%' . $name . '%'];
        }

        $list = db('loading_log')->alias('l')
            ->join('mc_task t', 'l.task_id = t.id')
            ->join('mc_user u', 'l.user_id = u.id')
            ->where($where)
            ->field('l.*,t.title,u.user_login')
            ->order('l.id desc')
            ->paginate(50, false, ['query' => array('title' => $title, 'name' => $name)]);

        $this->assign('list', $list);
        $this->assign('page', $list);
        return $this->fetch();
    }
}