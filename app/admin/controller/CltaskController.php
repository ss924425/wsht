<?php

namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;
use think\Session;

class CltaskController extends AdminBaseController
{

    function index()
    {
        $adminUser = db('user')->where('id',session('ADMIN_ID'))->value('user_type');

        if ($adminUser == 3){
            $map = '';
        } else {
            $map =  session('ADMIN_ID');
            $adminUserinfo = db('user')->find($map);
            $this->assign('userinfo',$adminUserinfo);

            return $this->fetch();
        }
    }

    function czmoney()
    {
        return $this->fetch();
    }

    function czlog()
    {
        $oid = request()->param('orderid');
        $where = [];
        if ($oid) $where['WIDout_trade_no'] = $oid;

        $loglist = db('clczorder_log')->where($where)->where('user_id',session('ADMIN_ID'))->order('id desc')->paginate(10);
        $this->assign([
            'page' => $loglist->render(),
            'list' => $loglist,
        ]);
        return $this->fetch();
    }

    function orderlist()
    {
        $oid = request()->param('orderid');
        $where = [];
        if ($oid) $where['cl_orderid'] = $oid;
        $orderlist = db('clorder_log')->where($where)->order('id desc')->paginate(10);
        $orderlist->appends($this->request->param());
        $this->assign([
            'page' => $orderlist->render(),
            'list' => $orderlist,
            'orderid' => $oid
        ]);
        return $this->fetch();
    }

    /**
     * 退单订单管理
     */
    function backorder(){
        $oid = request()->param('orderid');
        $where = [];
        if ($oid) $where['orderid'] = $oid;
        $loglist = db('cl_edit_task_log')->where($where)->order('id desc')->paginate(15);
        $loglist->appends($this->request->param());
        $this->assign([
            'page' => $loglist->render(),
            'list' => $loglist,
            'orderid' => $oid,
        ]);
        return $this->fetch();
    }

    /**
     * 处理订单
     */
    function edittask()
    {
        $id = input('id');
        $res = db('cl_edit_task_log')->where(['id'=>$id])->setField('status',2);
        if ($res)
            $this->success('处理成功');
        else
            $this->error('处理失败');
    }

}