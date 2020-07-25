<?php

namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;

class LogController extends AdminBaseController
{
    public function upgradelog()
    {
        $user_login = request()->param('user_login');
        $mobile = request()->param('mobile');
        $is_true = request()->param('is_true');
        $state = request()->param('state');

        $list = null;
        $where = null;

        if (!empty($user_login)) {
            $where['y.user_login'] = ['like', '%' . $user_login . '%'];
        }

        if (!empty($mobile)) {
            $where['u.mobile'] = trim($mobile);
        }

        if ($is_true != '') {
            $where['y.is_true'] = $is_true;
        }
        if ($state != '') {
            $where['y.state'] = $state;
        }

        $list = Db::name('upgrade_order')
            ->alias('y')
            ->join('user u', 'y.user_id=u.id', 'left')
            ->field('y.*,u.user_login  as username,u.mobile')
            ->where($where)
            ->order('paytime', 'desc')
            ->paginate(15, false, ['user_login' => $user_login, 'mobile' => $mobile, 'is_true' => $is_true, 'state' => $state]);
        $list->appends($this->request->param());
        $this->assign('list', $list);
        $this->assign('mobile', $mobile);
        $this->assign('user_login', $user_login);
        $this->assign('is_true', $is_true);
        $this->assign('state', $state);
        $this->assign('page', $list->render());
        return $this->fetch();
    }

    //推荐日志
    public function tjlog()
    {

    }

    //佣金记录
    public function yonglog()
    {
        $param = $this->request->param();
        $to_user = request()->param('to_user');
        $from_user = request()->param('from_user');
        $param['start_time'] = isset($param['start_time']) ? $param['start_time'] : '';
        $param['end_time'] = isset($param['end_time']) ? $param['end_time'] : '';
        $param['type'] = isset($param['type']) ? $param['type'] : '';

        $list = null;
        $where = null;

        if (!empty($to_user)) {
            $where['y.user_id|y.user_login'] = ['like', '%' . $to_user . '%'];
        }

        if (!empty($from_user)) {
            $where['y.sup_id|y.sup_login'] = ['like', '%' . $from_user . '%'];
        }
        if ($param['type'] != '') {
            $where['y.yong_type'] = $param['type'];
        }
        $startTime = empty($param['start_time']) ? 0 : strtotime($param['start_time']);
        $endTime = empty($param['end_time']) ? 0 : strtotime($param['end_time']);
        if (!empty($startTime) && !empty($endTime)) {
            $where['y.create_time'] = [['>= time', $startTime], ['<= time', $endTime]];
        } else {
            if (!empty($startTime)) {
                $where['y.create_time'] = ['>= time', $startTime];
            }
            if (!empty($endTime)) {
                $where['y.create_time'] = ['<= time', $endTime];
            }
        }
        $where['type'] = 0;


        $uid = cmf_get_current_admin_id();

        $users = Db::name('user')->where(['id' => $uid])->find();

        $vip = ['1', '4', '5'];
        if (!in_array($users['user_type'], $vip)) {

            $list = Db::name('user_yong_log')
                ->alias('y')
                ->join('user u', 'y.user_id=u.id', 'left')
                ->field('y.*,u.user_login  as username,u.mobile')
                ->where($where)
                ->order('y.id', 'desc')
                ->paginate(15);
        } else if ($users['user_type'] == 4 || $users['user_type'] == 5) {
            $where['y.user_id'] = $uid;

            $list = Db::name('user_yong_log')->alias('y')
                ->join('mc_user u', 'y.user_id=u.id', 'left')
                ->field('y.*,u.user_login as username,u.mobile')
                ->where($where)
                ->order('y.id', 'desc')
                ->paginate(15);
        }

        if ($list) {
            $list->appends($param);
            $page = $list->render();
        } else {
            $page = '';
        }

        $this->assign([
            'list' => $list,
            'page' => $page,
            'to_user' => $to_user,
            'from_user' => $from_user,
            'start_time' => $param['start_time'],
            'end_time' => $param['end_time'],
            'type' => $param['type']
        ]);
        return $this->fetch();
    }

    //会员日志
    public function userlog()
    {

    }

    //后台会员日志
    public function syslog()
    {
        $request = $this->request;

        $truename = $request->param('truename');
        if (!empty($truename)) {
            $where['u.user_login'] = ['like', '%' . $truename . '%'];
        }
        $mobile = $request->param('mobile');
        if ($mobile) {
            $where['u.mobile'] = trim($mobile);
        }
        $mark = $request->param('mark');
        if ($mark) {
            $where['s.mark'] = ['like', "%" . $mark . "%"];
        }
        $start_time = strtotime(input('start_time'));
        $end_time = strtotime(input('end_time'));
        if ($start_time && !$end_time) {
            $end_time = time();
        }
        if ($start_time && $end_time) {
            $where['s.create_time'] = ['between', $start_time . ',' . $end_time];
        }

        $type = $request->param('type');
        if ($type != '') {
            $where['s.type'] = $type;
        }
        $this->assign([
            'user_login' => $truename,
            'mobile' => $mobile,
            'mark' => $mark,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'type' => $type
        ]);
        $where['s.id'] = ['>', 0];
        $list = db('admin_syslog')->alias('s')
            ->join('mc_user u', 's.user_id = u.id', 'left')
            ->where($where)
            ->field('s.*,u.user_login,u.mobile')
            ->order('id desc')->paginate(17, false, ['query' => array('truename' => $truename, 'mobile' => $mobile, 'mark' => $mark, 'start_time' => $start_time, 'end_time' => $end_time, 'type' => $type)]);
        $page = $list->render();
        $this->assign('list', $list);
        $this->assign('page', $page);
        return $this->fetch();
    }
}