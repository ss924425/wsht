<?php

namespace app\admin\controller;

use app\admin\model\UserModel;
use cmf\controller\AdminBaseController;
use think\Db;

class OrderController extends AdminBaseController
{

    /* 用户接受任务列表 */
    public function index()
    {
        $receive_type = '';
        $request = $this->request;

        $truename = $request->param('truename');
        if(!empty($truename)){
            $where['u.user_login'] = ['like','%'.$truename.'%'];
        }
        $title = $request->param('title');
        if($title){
            $where['k.title'] = ['like','%'.$title.'%'];
        }
        $b_title = $request->param('b_title');
        if($b_title){
            $where['b.b_title'] = ['like','%'.$b_title.'%'];
        }
        $receive_time = $request->param('receive_time');
        if(!empty($receive_time)){
            $tomorrow = date('Y-m-d H:i:s',strtotime("$receive_time   +1   day"));
            $where['receive_time'] = ['between time',[$receive_time,$tomorrow]];
        }
        $receive_type = $request->param('receive_type');
        if(!empty($receive_type)){
            $where['t.receive_type'] = $receive_type;
        }


        $this -> assign('receive_type',$receive_type);

        $where['t.id'] = ['>',0];

        $list = db('task_receive') -> alias('t')
            -> join('mc_user u','t.uid = u.id')
            -> join('mc_task_branch b','t.bid = b.id')
            -> join('mc_task k','b.tid = k.id')
            -> join('mc_user us','t.handle_id = us.id','left')
            -> where($where)
            -> field('t.*,u.user_login,b.b_title,b.b_money,k.title,us.user_login handle_name')
            -> order('id desc') -> paginate(50,false,['query'  => array('truename' => $truename,'title' => $title,'b_title' => $b_title,'receive_time' => $receive_time,'receive_type' => $receive_type)]);
        $page = $list->render();
        $this->assign('list', $list);
        $this->assign('page', $page);
        return $this -> fetch();
    }

    //任务审核列表
    public function mine()
    {

        $request = $this->request;

        $truename = $request->param('truename');
        if (!empty($truename)) {
            $where['u.mobile'] = ['like', '%' . $truename . '%'];
        }
        $title = $request->param('title');//接收id参数
        if ($title) {
            $where['k.title'] = ['like', '%' . $title . '%'];
        }
        $b_title = $request->param('b_title');
        if ($b_title) {
            $where['b.b_title'] = ['like', '%' . $b_title . '%'];
        }

        $receive_time = $request->param('receive_time');
        if (!empty($receive_time)) {
            $tomorrow = date('Y-m-d H:i:s', strtotime("$receive_time   +1   day"));
            $where['receive_time'] = ['between time', [$receive_time, $tomorrow]];
        }

        $submit_time = $request->param('submit_time');
        if (!empty($submit_time)) {
            $tomorrow1 = date('Y-m-d H:i:s', strtotime("$submit_time   +1   day"));
            $where['submit_time'] = ['between time', [$submit_time, $tomorrow1]];
        }
        //获取查询时间段
        $interval = $request->param('interval');
        if ($interval){
            $int = $interval * 60;
            $where['time_diff'] = ['between',['0',$int]];
        }

        $receive_type = $request->param('receive_type');
        $this->assign('receive_type', $receive_type);
        if ($receive_type) {
            $where['t.receive_type'] = $receive_type - 1;
        } else {
            $where['t.receive_type'] = ['>', -1];
        }

        $incubator = $request->param('incubator');
        $this->assign('incubator', $incubator);
        if($incubator){
            if($incubator == 3){
                $where['t.incubator'] = ['not in',[0,1]];
            }else{
                $where['t.incubator'] = $incubator - 1;
            }
        }

        $list = db('task_receive')->alias('t')
            ->join('mc_user u', 't.uid = u.id','LEFT')
            ->join('mc_task_branch b', 't.bid = b.id','LEFT')
            ->join('mc_task k', 'b.tid = k.id','LEFT')
            ->where($where)
            ->field('t.*,u.user_nickname,u.mobile,u.apply_name,u.apply_account,b.b_title,b.b_money,k.title,b.b_validate,incubator')
            ->order('id desc')
            ->paginate(50, false, ['query' => array('truename' => $truename, 'title' => $title, 'b_title' => $b_title, 'receive_time' => $receive_time, 'receive_type' => $receive_type, 'submit_time' => $submit_time, 'interval' => $interval,'incubator' => $incubator)]);

        $page = $list->render();
        $this->assign('page', $page);

        $list = $list->toArray();
        foreach ($list['data'] as $k => $v) {
            $num = db('loading_log')->where(['user_id' => $v['uid'], 'task_id' => $v['tid']])->count();
            $list['data'][$k]['download_num'] = $num;
        }

        //excel导出数据查询     'ID', '姓名','手机号', '任务名', '验证依据', '验证电话', '验证订单号', '备注'
        $orderlist = db('task_receive')->alias('t')
            ->join('mc_user u', 't.uid = u.id')
            ->join('mc_task_branch b', 't.bid = b.id')
            ->join('mc_task k', 'b.tid = k.id')
            ->where($where)
            ->field('t.id,u.user_nickname,u.mobile,k.title,t.receive_time,t.submit_time,t.submit,t.submit_phone,t.submit_order,t.handle_notes')
            ->order('id desc')
            ->fetchSql()
            ->select();

        session('orderlist', $orderlist);
        session('orderlist_interval', $interval);

        $this->assign('list', $list);
        return $this->fetch();
    }

    public function edit()
    {
        $request = $this->request;

        $id = $request->param('id');
        $type = $request->param('type');
        $submit = $request->param('keyword');

        $receive = db('task_receive') -> alias('t')
            -> join('mc_task_branch b','t.bid = b.id')
            -> join('mc_task k','b.tid = k.id')
            -> field('t.uid,b.b_money,b.b_title,k.title,b.id,k.id as tid,k.settle_type,b.b_brokerage,t.receive_status')
            -> where(['t.id' => $id]) -> find();

        if($receive['receive_status'] > 0){
            $this -> error('该任务已处理过了，不可重复操作');
        }
        if(!empty($receive)){

            if($type == 1){
                $data['receive_type'] = 3;
                $data['handle_id'] = cmf_get_current_admin_id();
                $data['handle_time'] = time();
                $data['receive_status'] = $type;
            }else if($type == 2){
                $data['receive_type'] = 1;
                $data['handle_id'] = cmf_get_current_admin_id();
                $data['handle_time'] = time();
                $data['receive_status'] = $type;
                $data['handle_notes'] = $submit;
            }else{
                $this -> error('请求错误');
            }
            // 启动事务
            Db::startTrans();

            $res = Db::name('task_receive') -> where(['id' => $id]) -> update($data);
            if($type == 1){

                if($receive['settle_type'] == 1){
                    //每期结算
                    Db::name('user') -> where(['id'=>$receive['uid']]) -> setInc('user_money',$receive['b_money']);

                    //录入奖励金额日志
                    $add['user_id'] = $receive['uid'];
                    $add['rid'] = $id;
                    $add['create_time'] = time();
                    $add['coin'] = $receive['b_money'];
                    $add['status'] = 0;
                    $add['type'] = 0;
                    $add['notes'] = "完成".$receive['title']."任务".$receive['b_title'].",奖励金额".$receive['b_money'];

                    Db::name('user_money_log')-> insert($add);

                    //佣金金额
//                    $y_money = $receive['b_brokerage'] / 2;
                    //调用向股东奖励分红的方法
                    $this -> reward($receive['uid'],$receive['b_brokerage']);

                    //任务通过消息通知
                    $adds['uid'] = $receive['uid'];
                    $adds['bid'] = $receive['id'];
                    $adds['time'] = time();
                    $adds['type'] = 0;
                    $adds['status'] = 0;
                    $adds['news'] = $receive['title']."任务".$receive['b_title'].",已通过，奖励金额".$receive['b_money']."元";

                    $rec = Db::name('news')-> insert($adds);
                }elseif($receive['settle_type'] == 2){
                    //期满结算
                    $uid = $receive['uid'];//userid
                    $tid = $receive['tid'];
                    //该用户完成的当前主任务下的分期任务的数量
                    $over_num = db('task_receive') -> where(['uid' => $uid,'tid' => $tid,'receive_type' => 3,'receive_status' => 1]) -> count();
                    //当前主任务下的分期任务的总数量
                    $task_num = db('task_branch') -> where(['tid' => $receive['tid'],'b_isdelete' => 0,'b_invalid' => 0]) -> count();
                    //如果两个值相等说明用户完成了所有的分期任务
                    if($over_num == $task_num){
                        //用户应该获得的总金额
                        $z_money = db('task_branch') -> where(['tid' => $receive['tid'],'b_isdelete' => 0,'b_invalid' => 0]) -> sum('b_money');
                        //将金额奖励给用户
                        Db::name('user') -> where(['id'=>$receive['uid']]) -> setInc('user_money',$z_money);

                        //录入奖励金额日志
                        $add['user_id'] = $receive['uid'];
                        $add['tid'] = $receive['tid'];
                        $add['create_time'] = time();
                        $add['coin'] = $z_money;
                        $add['status'] = 0;
                        $add['type'] = 0;
                        $add['notes'] = "完成".$receive['title']."任务".$receive['b_title'].",奖励金额".$z_money."元";

                        $res_money = Db::name('user_money_log')-> insert($add);

                        //向股东奖励的佣金
                        $yong = db('task_branch') -> where(['tid' => $receive['tid'],'b_isdelete' => 0,'b_invalid' => 0]) -> sum('b_brokerage');
//                        $y_money = $yong / 2;
                        //调用向股东奖励分红的方法
                        $this -> reward($receive['uid'],$yong);
                    }
                    //任务通过消息通知
                    $adds['uid'] = $receive['uid'];
                    $adds['bid'] = $receive['id'];
                    $adds['time'] = time();
                    $adds['type'] = 0;
                    $adds['status'] = 0;
                    if($res_money){
                        $adds['news'] = $receive['title']."任务".$receive['b_title'].",已通过,奖励金额".$z_money."元";
                    }else{
                        $adds['news'] = $receive['title']."任务".$receive['b_title'].",已通过";
                    }


                    $rec = Db::name('news')-> insert($adds);
                }else{
                    $this -> error('任务类型错误');
                }

                //录入任务完成次数
                $rea = Db::name('task')->where(['id' => $receive['tid']])->setInc('com_num');
            }else if($type == 2){
                //任务驳回消息通知
                $adds['uid'] = $receive['uid'];
                $adds['bid'] = $receive['id'];
                $adds['time'] = time();
                $adds['type'] = 0;
                $adds['status'] = 0;
                $adds['news'] = $receive['title']."任务".$receive['b_title'].",已驳回！驳回原因：".$submit;

                $rec = Db::name('news')-> insert($adds);
            }

            if($res){
                // 提交事务
                Db::commit();
                $this -> success('操作成功');
            }else{
                // 回滚事务
                Db::rollback();
                $this -> error('操作失败');
            }

        }else{
            $this -> error('请求错误');
        }
    }

    //任务审核批量操作
    public function batch_edit()
    {
        $post = input();

        $id = $post['id'];
        $type = $post['type'];
        if($type == 2){
            $submit = $post['keyword'];
        }else{
            $submit = "";
        }

        if(empty($id)){
            $this -> error('请选择需要处理的申请');
        }

        foreach($id as $k => $v){
            $det = db('task_receive') -> where(['id' => $v]) -> value('receive_type');
            if((int)$det != 2){
                unset($id[$k]);
            }
        }

        if(empty($id)){
            $this -> error('请选择未处理的申请');
        }

        if($type == 1){
            Db::startTrans();
            try {
                foreach ($id as $k => $v) {
                    $receive = db('task_receive')->alias('t')
                        ->join('mc_task_branch b', 't.bid = b.id')
                        ->join('mc_task k', 'b.tid = k.id')
                        ->field('t.uid,b.b_money,b.b_brokerage,b.b_title,k.title,b.id,k.settle_type')
                        ->where(['t.id' => $v])->find();

                    $data['receive_type'] = 3;
                    $data['handle_id'] = cmf_get_current_admin_id();
                    $data['handle_time'] = time();
                    $data['receive_status'] = $type;

                    $res = Db::name('task_receive')->where(['id' => $v])->update($data);

                    if ($res) {

                        if ($receive['settle_type'] == 1) {
                            //每期结算
                            Db::name('user')->where(['id' => $receive['uid']])->setInc('user_money', $receive['b_money']);

                            //录入奖励金额日志
                            $add['user_id'] = $receive['uid'];
                            $add['rid'] = $id;
                            $add['create_time'] = time();
                            $add['coin'] = $receive['b_money'];
                            $add['status'] = 0;
                            $add['type'] = 0;
                            $add['notes'] = "完成" . $receive['title'] . "任务" . $receive['b_title'] . ",奖励金额" . $receive['b_money'];

                            Db::name('user_money_log')->insert($add);
                            //佣金金额
//                            $y_money = $receive['b_brokerage'] / 2;
                            //调用向股东奖励分红的方法
                            $this->reward($receive['uid'], $receive['b_brokerage']);

                            //任务通过消息通知
                            $adds['uid'] = $receive['uid'];
                            $adds['bid'] = $receive['id'];
                            $adds['time'] = time();
                            $adds['type'] = 0;
                            $adds['status'] = 0;
                            $adds['news'] = $receive['title'] . "任务" . $receive['b_title'] . ",已通过，奖励金额" . $receive['b_money'] . "元";

                            $rec = Db::name('news')->insert($adds);

                        } elseif ($receive['settle_type'] == 2) {
                            //期满结算
                            $uid = $receive['uid'];//userid
                            $tid = $receive['tid'];
                            //该用户完成的当前主任务下的分期任务的数量
                            $over_num = db('task_receive')->where(['uid' => $uid, 'tid' => $tid, 'receive_type' => 3, 'receive_status' => 1])->count();
                            //当前主任务下的分期任务的总数量
                            $task_num = db('task_branch')->where(['tid' => $receive['tid'], 'b_isdelete' => 0, 'b_invalid' => 0])->count();
                            //如果两个值相等说明用户完成了所有的分期任务
                            if ($over_num == $task_num) {
                                //用户应该获得的总金额
                                $z_money = db('task_branch')->where(['tid' => $receive['tid'], 'b_isdelete' => 0, 'b_invalid' => 0])->sum('b_money');
                                //将金额奖励给用户
                                Db::name('user')->where(['id' => $receive['uid']])->setInc('user_money', $z_money);

                                //录入奖励金额日志
                                $add['user_id'] = $receive['uid'];
                                $add['tid'] = $receive['tid'];
                                $add['create_time'] = time();
                                $add['coin'] = $z_money;
                                $add['status'] = 0;
                                $add['type'] = 0;
                                $add['notes'] = "完成" . $receive['title'] . "任务" . $receive['b_title'] . ",奖励金额" . $z_money . "元";

                                $res_money = Db::name('user_money_log')->insert($add);

                                //向股东奖励的佣金
                                $yong = db('task_branch')->where(['tid' => $receive['tid'], 'b_isdelete' => 0, 'b_invalid' => 0])->sum('b_brokerage');
//                                $y_money = $yong / 2;
                                //调用向股东奖励分红的方法
                                $this->reward($receive['uid'], $yong);
                            }
                            //任务通过消息通知
                            $adds['uid'] = $receive['uid'];
                            $adds['bid'] = $receive['id'];
                            $adds['time'] = time();
                            $adds['type'] = 0;
                            $adds['status'] = 0;
                            if ($res_money) {
                                $adds['news'] = $receive['title'] . "任务" . $receive['b_title'] . ",已通过,奖励金额" . $z_money . "元";
                            } else {
                                $adds['news'] = $receive['title'] . "任务" . $receive['b_title'] . ",已通过";
                            }

                            $rec = Db::name('news')->insert($adds);
                        } else {
                            $this->error('任务类型错误');
                        }

                    }
                }
                //提交事务
                Db::commit();
            }catch(\Exception $e){
//                回滚事务
                Db::rollback();
                $this -> error('操作失败');
            }
            $this -> success('操作成功');

        }else if($type == 2){
            Db::startTrans();

            try {
                foreach ($id as $k => $v) {
                    $receive = db('task_receive')->alias('t')
                        ->join('mc_task_branch b', 't.bid = b.id')
                        ->join('mc_task k', 'b.tid = k.id')
                        ->field('t.uid,b.b_money,b.b_title,k.title,b.id')
                        ->where(['t.id' => $v])->find();

                    $data['receive_type'] = 1;
                    $data['handle_id'] = cmf_get_current_admin_id();
                    $data['handle_time'] = time();
                    $data['receive_status'] = $type;
                    $data['handle_notes'] = $submit;


                    $res = Db::name('task_receive')->where(['id' => $v])->update($data);

                    if ($res) {
                        //任务驳回消息通知
                        $adds['uid'] = $receive['uid'];
                        $adds['bid'] = $receive['id'];
                        $adds['time'] = time();
                        $adds['type'] = 0;
                        $adds['status'] = 0;
                        $adds['news'] = $receive['title'] . "任务" . $receive['b_title'] . ",已驳回！驳回原因：" . $submit;
                    }
                    $rec = Db::name('news')->insert($adds);

                }
                Db::commit();
            }catch(\Exception $e){
                // 回滚事务
                Db::rollback();
                $this -> error('操作失败');
            }
            $this -> success('操作成功');
//            if($rec){
//                // 提交事务
//                Db::commit();
//                $this -> success('操作成功');
//            }else{
//                // 回滚事务
//                Db::rollback();
//                $this -> error('操作失败');
//            }
        }else{
            $this -> error('请求错误');
        }
    }

    //完成任务 向上级会员和股东分红
    /*
     * $id      完成任务的用户的会员id
     * $money   向股东的奖励金额
     */
    public function reward($id,$money)
    {
        $user_setting = cmf_get_option('user_setting');
        //上级佣金奖励
        $aa = $money * $user_setting['topMoney'] / 100;
        //股东佣金奖励
        $bb = $money * $user_setting['rewardMoney'] / 100;

        $user = db('user') -> where(['id' => $id]) -> field('agentId,user_login,pid') -> find();

        $agentId = $user['agentId'];
        $agent = db('user') -> where(['id' => $agentId]) -> field('vip_type,yong_money,user_login') -> find();
        //会员完成任务后向上级会员奖励佣金
        if($user['pid']){
            //一 确定当前用户有上一级
            if($user['pid'] != $user['agentId']){
                //二 当前用户的上一级不等于当前用户的代理商
                $this -> topmoney($id,$aa);
            }
//            else{
//                if($agent['vip_type'] != 4){
//                    //三 如果当前用户的上一级等于当前用户的代理商，但代理商的等级没有达到股东
//                    $this -> topmoney($id,$aa);
//                }
//            }
        }

        //判断完成任务的用户的代理商是否达到股东，如果达到则发放奖励
        if($agent['vip_type'] == 4 || $agent['vip_type'] == 3){
            Db::name('user') -> where(['id' => $agentId]) -> setInc('yong_money',$bb);
            $add['yong_type'] = 2;
            $add['user_id'] = $agentId;
            $add['user_login'] = $agent['user_login'];
            $add['sup_id'] = $id;
            $add['sup_login'] = $user['user_login'];
            $add['create_time'] = time();
            $add['fxyj'] = $bb;
            $add['type'] = 0;
            $add['notes'] = '推荐会员完成任务，向股东分红';
            Db::name('user_yong_log') -> insert($add);

            //推送消息
            $addNews['uid'] = $agentId;
            $addNews['time'] = time();
            $addNews['news'] = '团队中会员完成任务，奖励佣金'.$bb.'元';
            $addNews['type'] = 2;
            $addNews['status'] = 0;

            Db::name('news') -> insert($addNews);
        }
    }

    //向上级会员分红
    public function topmoney($id,$money)
    {
        //查询当前完成任务的会员的信息
        $user = db('user') -> where(['id' => $id]) -> field('pid,user_login') -> find();
        //查询会员的上级会员的信息
        $top = db('user') -> where(['id' => $user['pid']]) -> field('yong_money,user_login') -> find();

        //执行拥金写入并记录日志
        Db::name('user') -> where(['id' => $user['pid']]) -> setInc('yong_money',$money);
        $add['yong_type'] = 2;
        $add['user_id'] = $user['pid'];
        $add['user_login'] = $top['user_login'];
        $add['sup_id'] = $id;
        $add['sup_login'] = $user['user_login'];
        $add['create_time'] = time();
        $add['fxyj'] = $money;
        $add['type'] = 0;
        $add['notes'] = '推荐会员完成任务，向上级分红';
        Db::name('user_yong_log') -> insert($add);

        //推送消息
        $addNews['uid'] = $user['pid'];
        $addNews['time'] = time();
        $addNews['news'] = '推荐的会员完成任务，奖励佣金'.$money.'元';
        $addNews['type'] = 2;
        $addNews['status'] = 0;

        Db::name('news') -> insert($addNews);
    }

    //执行数据导出
    public function exportOrderlistExcel()
    {
        $sql = session('orderlist');
        $interval = session('orderlist_interval');
        $data = Db::query($sql);
        if(empty($data)){
            $this->error('暂无数据');
        }
        foreach($data as $k => $v){
            if ($interval && $v['submit_time']) {
                if ($v['submit_time']) {
                    if ($v['submit_time'] - $v['receive_time'] > $interval * 60) {
                        unset($data['data'][$k]);
                    }
                } else {
                    unset($data['data'][$k]);
                }
            }
            $data[$k]['receive_time'] = date('Y-m-d H:i:s',$v['receive_time']);
            if($v['submit_time']){
                $data[$k]['submit_time'] = date('Y-m-d H:i:s',$v['submit_time']);
            }
        }
        $fileName = '任务审核列表_' . date('YmdHis');
        $header = array(
            'ID', '姓名','手机号', '任务名','接取时间','提交时间', '验证依据', '验证电话', '验证订单号', '备注',
        );

        $this->exportExcel($data, $fileName, $header, '任务审核列表');
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






}