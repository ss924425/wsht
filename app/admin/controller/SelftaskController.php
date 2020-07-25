<?php

namespace app\admin\controller;

use api\home\model\NewsModel;
use app\common\model\SelfTaskModel;
use app\admin\model\SelfTaskSortModel;
use app\admin\model\UserModel;
use app\admin\model\UserMoneyModel;
use cmf\controller\AdminBaseController;
use think\Db;
use think\Exception;
use think\Log;


class SelftaskController extends AdminBaseController
{
    /**
     * 任务列表
     */
    public function index()
    {
        $adminUser = db('user')->where('id',session('ADMIN_ID'))->value('user_type');
        $this->assign('adminuser',$adminUser);
        $user = db('user')->where('id',session('ADMIN_ID'))->field('user_type,yewuqudao,cl_admin')->find();
        $this->assign('user',$user);
        $map=[];
        $ma  = [];
        if ($adminUser == 3){
            $map = [];
        } else {
            if ($user['cl_admin'] == 1){
                $ma = ['A.admin_id' => session('ADMIN_ID')];
            } else {
                $ma = [];
                $map = ['A.userid' => session('ADMIN_ID')];
            }

        }

        $param = $this->request->param();
        $userid = request()->param('userid');
        $taskid = request()->param('taskid');
        $title = request()->param('title');
        $sortid = request()->param('sortid');
        $status = request()->param('status');
        $orderid = request()->param('orderid');

        $startTime = empty($param['start_time']) ? 0 : strtotime($param['start_time']);
        $endTime = empty($param['end_time']) ? 0 : strtotime($param['end_time']);
        $where['A.type'] = 0;
        if (!empty($userid)) {
            $where['A.userid'] = $userid;
        }
        if (!empty($taskid)) {
            $where['A.id'] = $taskid;
        }
        if (!empty($title)) {
            $where['A.title'] = ['like', "%$title%"];
        }
        if (!empty($sortid)) {
            $where['A.sortid'] = $sortid;
        }

        if (!empty($startTime) && !empty($endTime)) {
            $where['A.createtime'] = [['>= time', $startTime], ['<= time', $endTime]];
        } else {
            if (!empty($startTime)) {
                $where['A.createtime'] = ['>= time', $startTime];
            }
            if (!empty($endTime)) {
                $where['A.createtime'] = ['<= time', $endTime];
            }
        }
        if (!empty($status) || $status === 0) {
            $where['A.status'] = ['=',$status];
        }

        if (!empty($status) && $status == 3) {
            $where['A.status'] = ['=',0];
        }

        if (!empty($orderid)) {
            $where['A.orderid'] = $orderid;
        }
        $list = Db::name('self_task')->alias('A')
            ->field('A.*,B.name sort_name,C.mobile')
            ->join('__SELF_TASK_SORT__ B', 'A.sortid=B.id', 'left')
            ->join('__USER__ C', 'A.userid=C.id', 'left')
            ->where($where)
            ->where($map)
            ->where($ma)
            ->order('A.createtime', 'asc')
            ->paginate(20)
            ->each(function ($item, $key) {
                $deallist = Db::query("select status,count(id) num from mc_self_task_receive where taskid= " .$item['id']. " group by status");
                if (!empty($deallist)){
                    foreach ($deallist as $k=>$v){
                        $status[] = $v['status'];
                        $num[] = $v['num'];
                    }
                    $a = array_combine($status,$num);
                    if (isset($a[0])){
                        $item['stay'] = $a[0];
                    } else {
                        $item['stay'] = 0;
                    }
                    if (isset($a[1])){
                        $item['wait'] = $a[1];
                    } else {
                        $item['wait'] = 0;
                    }
                    if (isset($a[2])){
                        $item['comed'] = $a[2];
                    } else {
                        $item['comed'] = 0;
                    }
                    if (isset($a[3])){
                        $item['notpass'] = $a[3];
                    } else {
                        $item['notpass'] = 0;
                    }
                    $item['receive'] = array_sum($num);
                } else {
                    $item['receive'] = 0;
                    $item['stay'] = 0;
                    $item['wait'] = 0;
                    $item['comed'] = 0;
                    $item['notpass'] = 0;
                }
                return $item;
            });
        $this->assign([
            'page' => $list->render(),
            'list' => $list,
        ]);

        $setting = cmf_get_option('selftask_setting');

        $sorts = db('self_task_sort')->where('status', 1)->order(['number' => 'desc', 'id' => 'desc'])->field('id,name')->select();
        $sorts = $sorts ? $sorts : array();

        $list->appends($this->request->param());

        $this->assign('sorts', $sorts);
        $this->assign('start_time', isset($param['start_time']) ? $param['start_time'] : '');
        $this->assign('end_time', isset($param['end_time']) ? $param['end_time'] : '');
        $this->assign('setting', $setting);
        $this->assign([
            'page' => $list->render(),
            'list' => $list,
            'userid' => $userid,
            'taskid' => $taskid,
            'title' => $title,
            'sortid' => $sortid,
            'orderid' => $orderid
        ]);
        return $this->fetch();
    }

    public function addTask()
    {
        $adminid = session('ADMIN_ID');
        $adminuser = db('user')->find($adminid);
        $this->assign('adminuser',$adminuser['user_type']);

        $sorts = db('self_task_sort')
            ->where('status', 1)
            ->where('pid','<>',0)
            ->order(['number' => 'desc', 'id' => 'desc'])
            ->field('id,name')->select()->ToArray();
        if (empty($sorts)) {
            $this->error('请先添加任务分类');
        }

        $sid = input('sid') ? input('sid') : $sorts[0]['id'];

        $sql = 'SELECT a.id, a.name,a.pname,a.pid,a.pname,a.appname,a.order,a.title,a.content,a.number,a.status,a.img,a.other, ifnull(b.fabumoney,a.dmoney) fabumoney, ifnull(b.appmoney,a.falsemoney) appmoney FROM `mc_self_task_sort` a left join mc_task_sort_money b on a.id=b.sortid and b.uid='.$adminid . " where a.id=" . $sid;
        $info = Db::query($sql);
        $info[0]['other'] = json_decode($info[0]['other'], true);

        $this->assign('sorts', $sorts);
        $this->assign('info', $info[0]);
        return $this->fetch();
    }


    public function addTaskPost()
    {
        if ($this->request->isPost()) {
            $adminid = session('ADMIN_ID');
            $adminuser = db('user')->find($adminid);
            $param = $this->request->param();
            $setting = cmf_get_option('selftask_setting');

            // 用户类型不是任务会员 管理员发任务不扣钱
            if ($adminuser['user_type'] != 4){
                $data = array(
                    'title' => $param['title'],//任务标题
                    'task_type' => $param['task_type'],//任务类型
                    'num' => intval($param['num']),//任务总数
                    'oldnum' => intval($param['num']),//任务原始总数
                    'money' => sprintf('%.2f', $param['money']),//任务赏金
                    'falsemoney' => sprintf('%.2f', $param['money']),//任务成本价
                    'limitnum' => intval($param['limitnum']),//抢任务次数
                    'ishide' => intval($param['ishide']),//隐藏回复
                    'istop' => intval($param['istop']),//是否置顶
                    'content' => $param['content'],//任务内容
                    'hidecontent' => $param['hidecontent'],//隐藏内容
                    'sortid' => $param['sortid'],//分类id
                    'start' => strtotime($param['start']),//开始
                    'end' => strtotime($param['end']),//结束
                    'images' => empty($param['images']) ? '' : json_encode($param['images']),
                    'userid' => session('ADMIN_ID'),  //发布人
                    'chaolianjie' => $param['chaolianjie'],  //超链接
                    'usetime' => $param['usetime'], //接取任务有效时间
                );

                // 判断是否填写抖音视频id 同步处理量
                if (!empty($param['order_aa'])){
                    $numurl = 'https://www.iesdouyin.com/web/api/v2/aweme/iteminfo/?item_ids=' . $param['order_aa'];
                    $numdata = self::http_curl($numurl);
                    $start_num = 0;
                    if (!empty($numdata['item_list'])){
                        $start_num = $numdata['item_list'][0]['statistics']['digg_count'];  // 视频当前的点赞量
                    }
                    $data['start_num'] = $start_num;
                    $data['order_aa'] = $param['order_aa'];
                }


                if ($data['usetime'] <= 0) $data['usetime'] = 1;
                $sort = db('self_task_sort')->where('id','=',$data['sortid'])->find();
                $data['sort_img'] = isset($sort['img']) ? $sort['img'] : '';
                $data['appname'] = db('self_task_sort')->where('id','=',$sort['pid'])->value('appname');

                $data['istaskform'] = 0;//0不使用模板 1使用回复模板
                $data['isread'] = 0;//0不可看答案 1可看  readprice查看答案价格 ishide隐藏回复

//            if (!empty($param['urlname'])) {
//                $urlarr = array();
//                foreach ($param['urlname'] as $k => $v) {
//                    array_push($urlarr, ['text' => $v, 'url' => $param['urlurl'][$k]]);
//                }
//                $data['link'] = json_encode($urlarr);
//            }

                if ($data['num'] <= 0) $this->error('任务数量不能小于等于0');
                if ($data['money'] <= 0) $this->error('任务赏金不能小于等于0');
                if ($data['end'] <= time()) $this->error('结束时间必须大于现在时间');
                if ($data['end'] < $data['start']) $this->error('结束时间必须大于开始时间');

                $continueday = intval($param['days']);
                if ($param['continue'] == 1 && $continueday <= 0) $this->error('还没设置连续任务天数');

//            if ($data['replytime'] >= $setting['autoconfirm'] * 60) $this->error('停留时间不能大于平台设置的自动结束时间');

                $data['continue'] = intval($param['continue']);
                $data['createtime'] = time();

                Db::startTrans();

                if ($data['continue'] == 1) { //连续发布
                    $continue['money'] = sprintf('%.2f', $param['ewai']);
                    $continue['totalnum'] = $data['num'];
                    $continue['totalmoney'] = $data['num'] * $continue['money'];
                    $continue_id = db('self_task_continue')->insertGetId($continue);
                    if (!$continue_id) {
                        Db::rollback();
                        $this->error('设置连续发布时出错');
                    }
                    $data['continueid'] = $continue_id;
                }

                // 超级管理员发任务不需要审核
                if ($adminuser['user_type'] != 3){
                    // 审核人id
                    $listdata = Db::query('select b.id from mc_user b  left  join mc_self_task a on a.admin_id=b.id and a.`status`=1  where  b.cl_admin=1 GROUP BY b.id order by count(a.id) asc limit 1');
                    if (!empty($listdata)){
                        $data['admin_id'] = $listdata[0]['id'];
                    }
                }
                $res = db('self_task')->insertGetId($data);
                if (!$res) {
                    DB::rollback();
                    $this->error('写入任务数据时出错');
                }

                if ($data['continue'] == 1) {
                    $newdata = $data;
                    $newdata['isstart'] = 1;
                    $arr = array();
                    for ($i = 0; $i < $continueday; $i++) {
                        $newdata['start'] = $data['start'] + 24 * 3600 * ($i + 1);
                        $newdata['end'] = $newdata['end'] + 24 * 3600 * ($i + 1);
                        array_push($arr, $newdata);
                    }
                    $res = db('self_task')->insertAll($arr);
                    if (!$res) {
                        Db::rollback();
                        $this->error('写入连续任务时出错');
                    }
                }

                Db::commit();

                $this->success('添加成功');
            }
            // 用户为后台创建的发任务用户 需要扣钱发任务
            if ($adminuser['user_type'] == 4){
                $data = array(
                    'title' => $param['title'],//任务标题
                    'task_type' => $param['task_type'],//任务类型
                    'num' => intval($param['num']),//任务总数
                    'oldnum' => intval($param['num']),//任务原始总数
                    'limitnum' => intval($param['limitnum']),//抢任务次数
                    'ishide' => intval($param['ishide']),//隐藏回复
                    'istop' => intval($param['istop']),//是否置顶
                    'content' => $param['content'],//任务内容
                    'hidecontent' => $param['hidecontent'],//隐藏内容
                    'sortid' => $param['sortid'],//分类id
                    'start' => strtotime($param['start']),//开始
                    'end' => strtotime($param['end']),//结束
                    'images' => empty($param['images']) ? '' : json_encode($param['images']),
                    'userid' => session('ADMIN_ID'),  //发布人
                    'chaolianjie' => $param['chaolianjie'],  //超链接
                    'usetime' => $param['usetime'], //接取任务有效时间
                    'falsemoney' => sprintf('%.2f', $param['money']),
                );


                // 判断是否填写抖音视频id 同步处理量
                if (!empty($param['order_aa'])){
                    $numurl = 'https://www.iesdouyin.com/web/api/v2/aweme/iteminfo/?item_ids=' . $param['order_aa'];
                    $numdata = self::http_curl($numurl);
                    $start_num = 0;
                    if (!empty($numdata['item_list'])){
                        $start_num = $numdata['item_list'][0]['statistics']['digg_count'];  // 视频当前的点赞量
                    }
                    $data['start_num'] = $start_num;
                    $data['order_aa'] = $param['order_aa'];
                }

                if ($data['usetime'] <= 0) $data['usetime'] = 1;
//                // 平台收取任务赏金收益比例
//                $sortsetting = db('self_task_sort')->where('id',$param['sortid'])->value('falsemoney');
//
//                // 任务赏金需要减去平台差价
//                if ($sortsetting > 0){
//                    $data['money'] = sprintf('%.2f', $param['money'] - $param['money'] * $sortsetting/100); //任务赏金
//                }
                // 任务APP价格
                $fabumoney = db("task_sort_money")->where(['uid'=>$adminid,'sortid'=>$param['sortid']])->value('appmoney');

                if ($fabumoney) {
                    $data['money'] = $fabumoney;
                } else {
                    $data['money'] = sprintf('%.2f', $param['money']);
                }


                // 是否审核任务
                if ($setting['isverifytask']) {
                    $data['status'] = 1;//待审
                }

                // 审核人id
                $listdata = Db::query('select b.id from mc_user b  left  join mc_self_task a on a.admin_id=b.id and a.`status`=1  where  b.cl_admin=1 GROUP BY b.id order by count(a.id) asc limit 1');
                if (!empty($listdata)){
                    $data['admin_id'] = $listdata[0]['id'];
                }

                $sort = db('self_task_sort')->where('id','=',$data['sortid'])->find();
                $data['sort_img'] = isset($sort['img']) ? $sort['img'] : '';
                $data['appname'] = db('self_task_sort')->where('id','=',$sort['pid'])->value('appname');


                if ($data['num'] <= 0) $this->error('任务数量不能小于等于0');
                if ($data['money'] <= 0) $this->error('任务赏金不能小于等于0');
                if ($data['end'] <= time()) $this->error('结束时间必须大于现在时间');
                if ($data['end'] < $data['start']) $this->error('结束时间必须大于开始时间');

                $data['createtime'] = time();


                // 算钱
                $taskmoney = $data['num'] * $data['falsemoney'];

                $server = $taskmoney * $setting['commonserver'] / 100;
                $server = max($server, $setting['commonserverleast']);
                if ($server < 0) {
                    $server = 0;
                }

                $top = 0;
                if ($data['istop'] == 1) $top = sprintf('%.2f', $setting['topserver']);


                $total = $taskmoney + $server + $top;

                if (($adminuser['user_money'] + $adminuser['yong_money']) < $total) {
                    $this->error('您的余额不足，请先充值');
                }

                // 保证金
                $isdeposit = $setting['isdeposit'];

                $data['costtop'] = $top;
                $data['costserver'] = $server;

                if ($adminuser['deposit'] < $total && empty($isdeposit)) {
                    $thisneed = $total;
                    $this->error( '您的保证金不足，发布此任务账户需留存' . $thisneed . '保证金');
                }

                if ($adminuser['deposit'] < $isdeposit && $isdeposit > 0) {
                    $this->error( '您的保证金不足，发布任务账户需留存' . $isdeposit . '保证金');
                }

                Db::startTrans();
                if ($adminuser['user_money'] > $total) {
                    $res = db('user')->where('id', $adminuser['id'])->setDec('user_money', $total);
                    if (!$res) {
                        Db::rollback();
                        $this->error('扣费出错，发布失败');
                    }
                } else {
                    $this->error('您的余额不足，请先充值');
                }
                $newInfo = db('user')->find($adminid);

                //写入变更日志
                $moneylog['user_id'] = $newInfo['id'];
                $moneylog['create_time'] = time();
                $moneylog['score'] = 0;//更改积分
                $moneylog['coin'] = 0 - $total;//更改金额
                $moneylog['notes'] = '发任务扣钱';
                $moneylog['user_money'] = $newInfo['user_money'];  //变更后的余额
                $moneylog['channel'] = 14; // 发任务扣钱
                $res = db('user_money_log')->insert($moneylog);
                if (!$res) {
                    Db::rollback();
                    $this->error('写余额变更日志出错，发布失败');
                }

                $data['costtop'] = $top;  //置顶费
                $data['costserver'] = $server; //服务费
                //插入数据
                $id = db('self_task')->insertGetId($data);
                if (!$id) {
                    Db::rollback();
                    $this->error('写入任务出错，发布失败');
                }
                Db::commit();
                // 新任务通知
                $this->success('添加成功');
            }
        }
    }

    static function http_curl($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $output = curl_exec($ch);
        curl_close($ch);
        return json_decode($output, true);
    }


    public function editTask()
    {
        $id = $this->request->param('id');
        $sorts = db('self_task_sort')->where('status', 1)->order(['number' => 'desc', 'id' => 'desc'])->field('id,name')->select();
        if (empty($sorts)) {
            $this->error('请先添加任务分类');
        }
        $task = db('self_task')->find($id);
        $task['link'] = json_decode($task['link'], true);
        $task['images'] = json_decode($task['images'], true);
        $this->assign('id', $id);
        $this->assign('task', $task);
        $this->assign('sorts', $sorts);
        return $this->fetch();
    }

    public function editTaskPost()
    {
        if ($this->request->isPost()) {

            $param = $this->request->param();
            $taked = db('self_task_receive')->where(['taskid'=>$param['id']])->count();
            if ($taked > 0) $this->error('任务已经有人接取,不能编辑!');
            $task = db('self_task')->find($param['id']);
            // 修改的任务数量
            $editnum = $param['num'] - $task['num'];

            $setting = cmf_get_option('selftask_setting');
            if(empty($param['images'])){
                $param['images'] = '';
            }
            $data = array(
                'title' => $param['title'],//任务标题
                'num' => intval($param['num']),//任务总数
                'money' => sprintf('%.2f', $param['money']),//任务赏金
//                'replytime' => intval($param['replytime']),//等待时间
                'limitnum' => intval($param['limitnum']),//抢任务次数
//                'sex' => intval($param['sex']),
                'ishide' => intval($param['ishide']),//隐藏回复
                'istop' => intval($param['istop']),//是否置顶
                'content' => $param['content'],//任务内容
                'hidecontent' => $param['hidecontent'],//隐藏内容
                'sortid' => $param['sortid'],//分类id
                'start' => strtotime($param['start']),//开始
                'end' => strtotime($param['end']),//结束
                'images' =>  json_encode($param['images']),
                'chaolianjie' =>  $param['chaolianjie'], //任务超链接
                'usetime' => $param['usetime'] //接取任务有效时间
            );
            $data['oldnum'] = $task['oldnum'] + $editnum;

            $data['istaskform'] = 0;//0不使用模板 1使用回复模板
            $data['isread'] = 0;//0不可看答案 1可看  readprice查看答案价格 ishide隐藏回复

//            if (!empty($param['urlname'])) {
//                $urlarr = array();
//                foreach ($param['urlname'] as $k => $v) {
//                    array_push($urlarr, ['text' => $v, 'url' => $param['urlurl'][$k]]);
//                }
//                $data['link'] = json_encode($urlarr);
//            }

            if ($setting['isverifytask']) {
                $data['status'] = 1;//待审
            }

            if ($data['num'] <= 0) $this->error('任务数量不能小于等于0');
            if ($data['money'] <= 0) $this->error('任务赏金不能小于等于0');
            if ($data['end'] <= time()) $this->error('结束时间必须大于现在时间');
            if ($data['end'] < $data['start']) $this->error('结束时间必须大于开始时间');

//            if ($data['replytime'] >= $setting['autoconfirm'] * 60) $this->error('停留时间不能大于平台设置的自动结束时间');

            if ($task['iscount'] == 1) $this->error('已结算任务不能再编辑');

            $res = db('self_task')->where('id', $param['id'])->update($data);
            if ($res === false) {
                $this->error('保存失败');
            }
            $this->success('保存成功');
        }
    }

    //硬删除
    public function deleteHardTask()
    {
        $param = $this->request->param();
        $selfTaskModel = new SelfTaskModel();
        if (isset($param['id'])) {
            try {
                $id = $this->request->param('id');
                $task = db('self_task')->where(array('id' => $id))->find();

                if ($task['type'] == 0) $selfTaskModel::deleteTaskImg($task, 'task');
                $receiveTask = db('self_task_receive')->where(['taskid' => $id])->find();
                if (!empty($receiveTask)) {
                    db('self_task_receive')->where(array('taskid' => $id))->delete();
                }
//                db('self_task_usetasklog')->where(array('taskid' => $id))->delete();
                db('self_task_useraddcontent')->where(array('taskid' => $id))->delete();
                db('self_task')->where(array('id' => $id))->delete();
            } catch (\Exception $e) {
                $this->error($e->getMessage());
            }
            $this->success('成功删除任务的相关数据');

        }

        if (isset($param['ids'])) {
            try {
                $ids = $this->request->param('ids/a');
                $tasks = db('self_task')->where('id', 'in', $ids)->select()->toArray();
                foreach ($tasks as $task) {

                    $task = db('self_task')->where(array('id' => $task['id']))->find();

                    if ($task['type'] == 0) $selfTaskModel::deleteTaskImg($task, 'task');

                    db('self_task_receive')->where('taskid', 'in', $ids)->delete();
//                    db('self_task_usetasklog')->where(array('taskid' => $id))->delete();
                    db('self_task')->where('id', 'in', $ids)->delete();
                }
                db('self_task')->where(array('id' => $task['id']))->find();
            } catch (\Exception $e) {
                $this->error($e->getMessage());
            }
            $this->success('成功删除任务的相关数据');
        }
    }

    public function taskInfo()
    {
        $adminUser = db('user')->where('id',session('ADMIN_ID'))->value('user_type');
        $this->assign('adminuser',$adminUser);
        $id = $this->request->param('id');
        if (empty($id)) {
            $this->error('缺少请求参数');
        }
        $info = db("self_task")->where(array("id" => $id))->find();
//        dump($info);die;
        $pid = db('self_task_sort')->where('id',$info['sortid'])->value('pid');
        if (!empty($info['images'])) {
            $info["images"] = json_decode($info["images"], true);
        } else {
            $info['images'] = array();
        }
        if (!empty($info['link'])) {
            $info["link"] = json_decode($info["link"], true);
        } else {
            $info['link'] = array();
        }

        $info["end"] = date("Y-m-d H:i", $info["end"]);
        $info["start"] = date("Y-m-d H:i", $info["start"]);

        $site_info = cmf_get_option('site_info');
        if ($info['userid']) {
            $puber = db('user')->find($info['userid']);  //发布者
            if (empty($puber['avatar'])) $puber['avatar'] = $site_info['dhead'];
        } else {
            $puber = array();
        }
//        $credit = model_user::getUserCredit($info["userid"]);

        $continue = array();
        if ($info["continue"] == 1) {
            $continue = db("self_task_continue")->where(array("id" => $info["continueid"]))->select()->toArray();
        }

        $reply = db("self_task_receive")->where(array("status" => 1, "taskid" => $info["id"]))->count();

        $agree = db("self_task_receive")->where(array("status" => 2, "taskid" => $info["id"]))->count();

        $refuse = db("self_task_receive")->where(array("status" => 3, "taskid" => $info["id"]))->count();

        $where = array("r.taskid" => $info["id"], "r.status" => array('gt', 0.5));

        $status = $this->request->param('status');

        if (!empty($status)) {
            $where["r.status"] = intval($status);
        }


        $replyinfo = db("self_task_receive")->alias('r')
            ->join('self_task_receive_useragree g','r.taskid = g.taskid and r.userid=g.userid','left')
            ->where($where)
            ->field('r.*,g.status as dealstatus,g.dealnote')
            ->order('r.replytime', 'asc')->paginate(25)
            ->each(function ($item, $key) {
                $item["user"] = db('user')->find($item["userid"]);
                if (!empty($item['images'])) {
                    $item['images'] = json_decode($item['images'], true);
                } else {
                    $item['images'] = array();
                }
                $item['remind'] = db('self_task_remindlog')->where(array('takedid' => $item['id'], 'mtype' => 0))->select()->toArray();
                $item['addlist'] = db('self_task_remindlog')->where(array('takedid' => $item['id'], 'mtype' => 1))->select()->toArray();
                if (!empty($item['addlist'])) {
                    foreach ($item['addlist'] as $k => $v) {
                        if (!empty($v['images'])) {
                            $item['addlist'][$k]['images'] = json_decode($v['images'], true);
                        } else {
                            $item['addlist'][$k]['images'] = array();
                        }
                    }
                }
                return $item;
            });

        if($pid){ // // 5.爆音套餐 4.KS 3.小书本 2.火S 1.KS悬赏
            if($pid == 1){
                $bind = $puber['account1'] ? $puber['account1'] : "未绑定";
            }
            if ($pid == 2){
                $bind = $puber['account2'] ? $puber['account2'] : "未绑定";
            }
            if ($pid == 3){
                $bind = $puber['account3'] ? $puber['account3'] : "未绑定";
            }
            if ($pid == 4){
                $bind = $puber['account4'] ? $puber['account4'] : "未绑定";
            }
            if ($pid == 5){
                $bind = $puber['account5'] ? $puber['account5'] : "未绑定";
            }
        }
        $replyinfo->appends($this->request->param());
        $this->assign('id', $id);
        $this->assign([
            'bind'=>$bind,
            'id' => $id,
            'info' => $info,
            'continue' => $continue,
            'reply' => $reply,
            'agree' => $agree,
            'refuse' => $refuse,
            'replyinfo' => $replyinfo,
            'page' => $replyinfo->render(),
            'status' => $status
        ]);
        return $this->fetch();
    }


    //批量采纳
    public function agreeall()
    {
        $param = $this->request->param();
        if (isset($param['ids'])) {
            $ids = $this->request->param('ids/a');
            $model_task = new SelfTaskModel();
            $setting = cmf_get_option('selftask_setting');
            foreach ($ids as $v) {
                $taked = db('self_task_receive')->alias("a")
                    ->join("self_task b", " b.id=a.taskid")
                    ->fieldRaw("a.*,b.continueid,b.continue ,b.id AS sid,b.status as sstatus,b.start,b.end,b.sortid")
                    ->where(array('a.id' => $v, 'a.status' => 1))->find();

                if (empty($taked)) continue;

                if ($taked['sstatus'] != 0) $this->error('任务未上架');
                if ($taked['start'] > time()) $this->error('任务未开始');
//                if ($taked['end'] < time()) $this->error('任务已结束');
                $model_task::agreeTask($setting, $taked,'','');
            }
            $this->error('采纳成功');
        }
    }

    //批量拒绝
    public function refuseall()
    {
        $param = $this->request->param();
        if (isset($param['ids'])) {
            $ids = $this->request->param('ids/a');
            $liyou = $this->request->param('reason');
            $model_task = new SelfTaskModel();
            $success = 0;
            switch ($liyou) {
                case 1:
                    $reason = '乱截图';
                    break;
                case 2:
                    $reason = '没关注';
                    break;
                case 3:
                    $reason = '没点赞';
                    break;
                case 4:
                    $reason = '评论未显示';
                    break;
                default:
                    $reason = '';
            }
            foreach ($ids as $v) {
                $taked = db('self_task_receive')->where(array('id' => $v, 'status' => 1))->find();
                if (empty($taked)) continue;
                $task = db('self_task')->where(array('id' => $taked['taskid']))->find();
                if ($task['status'] != 0) $this->error('任务未上架');
                if ($task['start'] > time()) $this->error('任务未开始');
//                if ($task['end'] < time()) $this->error('任务已结束');
                $res = $model_task::refuseTask($taked, $reason,'','');
                if ($res) $success++;
            }
        }
        $this->error('操作成功');

//        if (isset($param['id'])) {
//            $id = $this->request->param('id');
//            $res = db('self_task')->where('id', $id)->setField(['status'=>3,'dealtime'=>time()]);
//            if ($res === false) {
//                $this->error('拒绝失败');
//            }
//            $this->error('拒绝成功');
//        }
    }

    //提醒商家
    public function sendRemind()
    {
        $id = $this->request->param('id');
        $content = $this->request->param('content');
        if (empty($content)) $this->error('还没填写提醒内容');
        $task = db('self_task')->where(array('id' => $id))->find();
        $uid = $task['userid'];
        if (!$uid) {
            $this->error('此为系统任务，无需提醒');
        }
        $title = $task['title'];

        $model_news = new NewsModel();
        $news = "{$content},任务ID{$task['id']},{$title}";
        $model_news::toUserNews($uid, $news);//后面需要改，可以查看是哪个任务，哪个回复
        $this->success('已提交提醒');
    }
    //采纳任务
    public function dealReply()
    {
        try {
            $replyid = $this->request->param('replyid');
//            dump($replyid);die;
            $reply = db('self_task_receive')->alias("a")
                ->join("self_task b", " b.id=a.taskid")
                ->fieldRaw("a.*,b.continueid,b.continue ,b.id AS sid,a.status as sstatus,b.start,b.end,a.taskid,b.sortid")
                ->where(array('a.id' => $replyid, 'a.status' => 1))->find();

            $firstid = db('self_task_receive')->where('userid',$reply['userid'])->whereTime('createtime','today')->order('createtime','asc')->limit(1)->value('id');


            if (empty($reply)) $this->error('没有找到回复');
            $task = db('self_task')->where(array('id' => $reply['taskid']))->find();
            if (empty($task)) $this->error('没有找到任务');

            $type = $this->request->param('type');

            $model_task = new SelfTaskModel();
            $setting = cmf_get_option('selftask_setting');

            if ($type == 'accept') {
                if ($reply['sstatus'] != 1) $this->error('此回复不能被采纳');
                $res = $model_task::agreeTask($setting, $reply, $firstid,'');
            } elseif ($type == 'refuse') {
//                $reason = $this->request->param('reason');
                $liyou = $this->request->param('liyou');
                switch ($liyou) {
                    case 1:
                        $reason = '乱截图';
                        break;
                    case 2:
                        $reason = '没关注';
                        break;
                    case 3:
                        $reason = '没点赞';
                        break;
                    case 4:
                        $reason = '评论未显示';
                        break;
                    default:
                        $reason = '';
                }

                if (empty($reason)) $this->error('请填写拒绝理由');
                if ($reply['sstatus'] != 1) $this->error('此回复不能被拒绝');
                $puber = db('user')->find($task['userid']);
                $res = $model_task::refuseTask($reply, $reason, $task, $puber['user_nickname']);

            } elseif ($type == 'noscan') {

                $res = db('self_task_receive')->where(array('id' => $reply['id']))->update(array('isscan' => 1));

            } elseif ($type == 'delete') {

                $res = db('self_task_receive')->where(array('id' => $reply['id']))->delete();

            } elseif ($type == 'allowscan') {

                $res = db('self_task_receive')->where(array('id' => $reply['id']))->update(array('isscan' => 0));

            } elseif ($type == 'remind') {

                $reason = $this->request->param('reason');
                if (empty($reason)) $this->error('请填写内容');
                $data = array(
                    'takedid' => $reply['id'],
                    'createtime' => time(),
                    'content' => $reason,
                    'type' => 1,
                );
                db('self_task_remindlog')->insert($data);

                $this->success('已发送提醒');
            }
            if ($res) {
                $this->success('操作成功');
            }
            $this->error('操作失败1');
        } catch (Exception $e) {

            $this->error('系统异常,请重试'.$e->getMessage());
        }

    }

    public function sendmoneyall()
    {
        $ids = $this->request->param('ids/a');
        $money = $this->request->param('money');
        if (empty($ids)) $this->error('请选择要操作的数据');
        foreach ($ids as $v) {
            $taked = db('self_task_receive')->where(array('id' => $v))->find();
            if (empty($taked)) $this->error('选择的数据不存在');
            $user = db('user')->where(array('id' => $taked['userid']))->find();
            if (empty($user)) $this->error('会员不存在');

            $value = sprintf('%.2f', $money) * 1;

            if ($value == 0) $this->error('改变数值不能等于0');

            if ($value < 0 && $user['user_money'] < abs($value)) $this->error('会员' . $user['mobile'] . '余额不足');
            if ($value < 0) {
                $res = db('user')->where('id', $user['id'])->setDec('user_money', abs($value));
            } else {
                $res = db('user')->where('id', $user['id'])->setInc('user_money', abs($value));
            }
            if ($res) {
                //写入余额变更日志
                $moneylog['user_id'] = $user['id'];
                $moneylog['tid'] = $taked['taskid'];
                $moneylog['rid'] = $taked['id'];
                $moneylog['create_time'] = time();
                $moneylog['score'] = 0;//更改余额
                $moneylog['coin'] = $value ? $value : 0 - $value;
                $moneylog['notes'] = '管理员查看自发系统任务' . $taked['taskid'] . '接任务回复ID' . $taked['id'] . '时修改了余额';
                $moneylog['channel'] = 14;//自发任务系统
                db('user_money_log')->insert($moneylog);
                db('self_task_receive')->where('id', $taked['id'])->update(array('adminadd' => $value));
            }
        }

        $this->success('操作完成');
    }

    //上架(被下架的就不能重新上架了)
    public function upTask()
    {
        $param = $this->request->param();
        $model_task = new SelfTaskModel();
        $setting = cmf_get_option('selftask_setting');
        //批量上架参与分销
        if (isset($param['ids'])) {

            $ids = $this->request->param('ids/a');
            Db::startTrans();

            $res = db("self_task")->where('id', 'in', $ids)->update(array("status" => 0));
            if (!$res) {
                Db::rollback();
                $this->error('更新任务状态失败');
            }
            //执行发任务分销
            foreach ($ids as $id) {
                $task = db('self_task')->where('id', $id)->field('id,userid,costtop,costserver')->find();
                $userinfo = db('user')->where('id', $task['userid'])->find();
                //个人发布
                db('user')->where('id', $userinfo['id'])->setInc('pubnumber');
                $upMoney = $task["costtop"] + $task["costserver"];
                $model_task::pubGiveParent($setting, $userinfo, $id, $upMoney);
                // 任务通过 给用户发消息
                if ($userinfo) {
                    $model_news = new NewsModel();
                    $news = "您发布的任务{$task['id']}已通过审核";
                    $model_news::toUserNews($userinfo['id'], $news);
                }
            }

            ##################################################################################
            Db::commit();

            $this->success('上架成功');
        }

        if (isset($param['id'])) {
            $id = $this->request->param('id');
            Db::startTrans();

            $res = db("self_task")->where('id', $param['id'])->update(array("status" => 0));
            if (!$res) {
                Db::rollback();
                $this->error('更新任务状态失败');
            }
            //执行发任务分销
            ##################################################################################
            $task = db('self_task')->where('id', $id)->field('id,userid,costtop,costserver')->find();
            $userinfo = db('user')->where('id', $task['userid'])->find();
            db('user')->where('id', $userinfo['id'])->setInc('pubnumber');
            $upMoney = $task["costtop"] + $task["costserver"];
            $model_task::pubGiveParent($setting, $userinfo, $id, $upMoney);
            // 任务通过 通知用户
            if ($userinfo) {
                $model_news = new NewsModel();
                $news = "您发布的任务{$task['id']}已通过审核";
                $model_news::toUserNews($userinfo['id'], $news);
            }

            Db::commit();

            $this->success('上架成功');
        }


    }

    //下架
    public function downTask()
    {
        $param = $this->request->param();
        if (isset($param['ids'])) {
            $ids = $this->request->param('ids/a');
            $res = db('self_task')->where('id', 'in', $ids)->setField('status', 2);
            if ($res === false) {
                $this->error('下架失败');
            }
            $this->success('下架成功');
        }

        if (isset($param['id'])) {
            $id = $this->request->param('id');
            $res = db('self_task')->where('id', $id)->setField('status', 2);
            if ($res === false) {
                $this->error('下架失败');
            }
            $this->success('下架成功');
        }
    }

    //驳回
    public function noupTask()
    {
        $param = $this->request->param();
        set_time_limit(0);//把程序执行完
        $setting = cmf_get_option('selftask_setting');
        if (isset($param['id'])) {
            $id = $this->request->param('id');
            $task = db('self_task')->find($id);

            if (empty($task) || $task["status"] != 1 || $task["iscount"] == 1) {
                $this->error("任务不能审核");
            }

            if ($task['api_key'] !== null || $task['account'] !==null || $task['api_key'] != '' || $task['account'] != ''){
                // 退钱给从林账号
                $money = $task['num'] * $task['money'];
                db('user')->where('id',$task['userid'])->setInc('cl_money',$money);
            }

            $model_task = new SelfTaskModel();
            $isback = empty($setting["isbacktm"]) ? false : true;//是否退回扣费
            //连续发布
            if ($task["continueid"] > 0) {
                $all = db('self_task')->where(array("continueid" => $task["continueid"]))->select()->toArray();
                foreach ($all as $v) {
                    //普通任务
                    if ($task["type"] == 0) {
                        $res = $model_task::countTask($v, $isback);
                    }
                    if ($res) {
                        db('self_task')->where(["id" => $v["id"]])->setField('status', 0);
                    }
                }
            } else {
                if ($task["type"] == 0) {
                    $res = $model_task::countTask($task, $isback);
                }
                if ($res) {
                    db('self_task')->where('id', $task['id'])->setField('status', 0);
                }
            }
            $this->success("已处理");
        }
    }

    public function restartTask()
    {
        $setting = cmf_get_option('selftask_setting');
        if (!$setting['restart']) {
            $this->error('发任务设置不能恢复已结算任务');
        }
        $taskid = $this->request->param('id');
        $task = db('self_task')->where(array('id' => $taskid))->find();
        if (empty($task)) $this->error('未找到任务');

        if ($task['iscount'] != 1) $this->error('任务未结算，不可恢复');

        Db::startTrans();

        if (!empty($task['userid']) && $task['backmoney'] > 0) {

            $user = db('user')->find($task['userid']);

            if ($user['user_money'] < $task['backmoney']) {
                $this->error('恢复需要扣除发布者' . $task['backmoney'] . '余额，发布者的余额不够');
            }

            // 扣钱
            $res = db('user')->where('id', $task['userid'])->setDec('user_money', abs($task['backmoney']));
            if ($res) {
                $newData = db('user')->where('id', $task['userid'])->value('user_money');
                // 资金记录
                $moneylog['user_id'] = $user['id'];
                $moneylog['tid'] = $task['id'];
                $moneylog['rid'] = 0;
                $moneylog['create_time'] = time();
                $moneylog['coin'] = 0 - abs($task['backmoney']);//更改金额
                $moneylog['notes'] = '自发系统发的任务ID' . $task['id'] . '被系统恢复,扣除发布费用';
                $moneylog['channel'] = 14;//自发任务系统
                $moneylog['user_money'] = $newData;

                $res = db('user_money_log')->insert($moneylog);
                if (!$res) {
                    Db::rollback();
                    $this->error('写入用户余额变更日志失败');
                }
            } else {
                Db::rollback();
                $this->error('扣钱失败');
            }
        }

        $setting['autoconfirm'] = empty($setting['autoconfirm']) ? 24 : $setting['autoconfirm'];
        $end = time() + $setting['autoconfirm'] * 3600;
        $res = db('self_task')->where(array('id' => $task['id']))->update(array('iscount' => 0, 'end' => $end, 'backmoney' => 0));
        if ($res) {
            Db::commit();
            $this->success('已恢复');
        } else {
            Db::rollback();
            $this->error('扣钱失败');
        }
    }

    //任务分类列表
    public function tasksort()
    {
        $list = Db::name('self_task_sort')
            ->order(['id' => 'asc'])
            ->paginate(10);

        $this->assign([
            'list' => $list,
            'page' => $list->render(),
        ]);

        return $this->fetch();
    }

    public function tasksortAdd()
    {
        $sorts = db('self_task_sort')
            ->where('status', 1)
            ->where('pid','=',0)
            ->order(['number' => 'desc', 'id' => 'desc'])
            ->field('id,name')->select()->ToArray();
        if (empty($sorts)) {
            $this->assign('sorts',null);
            $this->assign('info',null);
            return $this->fetch();
        }
        $sid = input('sid') ? input('sid') : $sorts[0]['id'];
        $info = db('self_task_sort')->where('id', $sid)->find();
        $info['other'] = json_decode($info['other'], true);
        $this->assign('sorts',$sorts);
        $this->assign('info',$info);
        return $this->fetch();
    }

    public function tasksortAddPost()
    {
        $model = new SelfTaskSortModel();
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $data['pid'] = $data['sortid'];
            $psort = db('self_task_sort')->where('id',$data['pid'])->find();
            if (!empty($psort)){
                $data['pname'] = $psort['name'];
            }else{
                $data['pname'] = '';
            }

            $model->adminAddTaskSort($data);
            $this->success('添加成功', url('selftask/tasksort'));
        }
    }

    public function tasksortEdit()
    {
        $id = $this->request->param('id');
        $info = model('self_task_sort')->find($id);
//        dump($info);die;
        $this->assign('id', $id);
        $this->assign('info', $info);
        return $this->fetch();
    }

    public function tasksortEditPost()
    {
        $model = new SelfTaskSortModel();
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $model->adminEditTaskSort($data);
            $this->success('保存成功', url('selftask/tasksortEdit', array('id' => $data['id'])));
        }
    }

    public function tasksortDelete()
    {
        $param = request()->param();

        $id = request()->param('id');

        if (isset($id)) {

            $sort = db('self_task_sort')->where(['id'=>$id])->find();
            if ($sort['pid'] == 0){
                $this->error('顶级分类不能删除');
                $sortList = db('self_task_sort')->where(['pid'=>$id])->select()->toArray();
                if (empty($sortList)){
                    $result = db('self_task_sort')->delete($id);
                    if ($result) {
                        $this->success('删除成功', url('selftask/tasksort'));
                    }
                } else{
                    $this->error('该分类有下级分类不能删除');
                }
            } else {
                $result = db('self_task_sort')->delete($id);
                if ($result) $this->success('删除成功', url('selftask/tasksort'));
            }

        }

        if (isset($param['ids'])) {
            $ids = $this->request->param('ids/a');
            foreach ($ids as $id){
                $sort = db('self_task_sort')->where(['id'=>$id])->find();
                if ($sort['pid'] == 0){
                    $this->error('选择的分类中有顶级分类不能删除');
                } else {
                    $sortList = db('self_task_sort')->where(['pid'=>$id])->select()->toArray();
                    if (empty($sortList)){
                        $result = db('self_task_sort')->where(['id' => $id])->delete();
                        if ($result) {
                            $this->success('删除成功', url('selftask/tasksort'));
                        }
                    } else {
                        $this->error('分类有下级分类不能删除');
                    }
                }
            }
        }
    }

    public function tasksortPublish()
    {
        $param = $this->request->param();
        $portalPostModel = db('self_task_sort');

        if (isset($param['ids']) && isset($param["yes"])) {
            $ids = $this->request->param('ids/a');
            $portalPostModel->where(['id' => ['in', $ids]])->update(['status' => 1]);

            $this->success("发布成功！", '');
        }

        if (isset($param['ids']) && isset($param["no"])) {
            $ids = $this->request->param('ids/a');

            foreach ($ids as $id) {
                $sort = $portalPostModel->where('pid',0)->where('id',$id)->find();
                if ($sort) $this->error('顶级分类不能取消发布');
            }

            $portalPostModel->where(['id' => ['in', $ids]])->update(['status' => 0]);

            $this->success("取消发布成功！", '');
        }
    }

    public function listOrder()
    {
        parent::listOrders(Db::name('self_task_sort'), 'number');
        $this->success("排序更新成功！", '');
    }

    //任务回复列表
    public function taskReply()
    {
        $adminUser = db('user')->where('id',session('ADMIN_ID'))->value('user_type');
        $this->assign('adminuser',$adminUser);
        if ($adminUser == 3){
            $where  = [];
        } else {
            $where = ['B.userid' => session('ADMIN_ID')];
        }

        $status = $this->request->param('status');

        $puber = $this->request->param('puber');
        $taskid = $this->request->param('taskid');

        if (!empty($status)) {
            $where["A.status"] = intval($status);
        }
        if ($puber == "admin") {
            $where["A.pubuid"] = 0;
        } else if ($puber == 'user') {
            $where["A.pubuid"] = array('gt', 0.1);
        }
        if ($taskid > 0) {
            $where["A.taskid"] = $taskid;
        }
        $list = db("self_task_receive")->alias('A')
            ->field('A.*,B.title,B.userid as buserid,C.mobile,D.status as dealstatus,D.dealnote,B.num,B.oldnum,B.money,B.falsemoney')
            ->join('__SELF_TASK__ B', 'A.taskid=B.id', 'left')
            ->join('__USER__ C', 'A.userid=C.id', 'left')
            ->join('self_task_receive_useragree D','A.taskid = D.taskid and A.userid=D.userid','left')
            ->where($where)->order('A.id', 'desc')
            ->paginate(20)->each(function ($item) {
                $deallist = Db::query("select status,count(id) num from mc_self_task_receive where taskid= " .$item['taskid']. " group by status");
                if (!empty($deallist)){
                    foreach ($deallist as $k=>$v){
                        $status[] = $v['status'];
                        $num[] = $v['num'];
                    }
                    $a = array_combine($status,$num);
                    if (isset($a[0])){
                        $item['stay'] = $a[0];
                    } else {
                        $item['stay'] = 0;
                    }
                    if (isset($a[1])){
                        $item['wait'] = $a[1];
                    } else {
                        $item['wait'] = 0;
                    }
                    if (isset($a[2])){
                        $item['comed'] = $a[2];
                    } else {
                        $item['comed'] = 0;
                    }
                    if (isset($a[3])){
                        $item['notpass'] = $a[3];
                    } else {
                        $item['notpass'] = 0;
                    }
                    $item['receive'] = array_sum($num);
                } else {
                    $item['receive'] = 0;
                    $item['stay'] = 0;
                    $item['wait'] = 0;
                    $item['comed'] = 0;
                    $item['notpass'] = 0;
                }
                return $item;
            });
        $list->appends($this->request->param());
        $this->assign('list',$list);
        $this->assign('page', $list->render());
        $this->assign('status',$status);
        $this->assign('puber',$puber);
        $this->assign('taskid',$taskid);
        return $this->fetch();
    }

    //回复编辑
    public function editReply()
    {
        $id = $this->request->param('id');
        $info = db("self_task_receive")->where(array("id" => $id))->find();
        if (empty($info)) {
            $this->error("回复不存在");
        }
        if (!empty($info['images'])) {
            $info["images"] = json_decode($info["images"], true);
        } else {
            $info["images"] = array();
        }
        $this->assign('id', $id);
        $this->assign('info', $info);
        return $this->fetch();
    }

    public function editReplyPost()
    {
        $images = $this->request->param('images/a');
        $data = $this->request->param();
        if (empty($images)) {
            $data['images'] = '';
        } else {
            $data['images'] = json_encode($images);
        }
        $res = db('self_task_receive')->update($data);
        if ($res !== false) {
            $this->success('更新成功', url('taskReply'));
        }
        $this->error('更新失败' . db('self_task_receive')->getLastSql());
    }

    public function counttask()
    {
        $id = intval(input("taskid"));
        $task = db("self_task")->where(array("id" => $id))->find();
        if (empty($task)) {
            $this->error("没有找到任务");
        }
        if ($task["iscount"] == 1) {
            $this->error("任务已结算过了");
        }
        $counting = \TbUtil::getCache("counttask", $task["id"]);
        if (is_array($counting) && $counting["status"] == 1) {
            $this->error("此任务正在被处理中，请重试");
        }
        \TbUtil::setCache("counttask", $task["id"], array("status" => 1));
        $model_task = new SelfTaskModel();
        $res = $model_task::countTask($task);
        \TbUtil::deleteCache("counttask", $task["id"]);
        if ($res) {
            $this->success("成功结算任务");
        }
        $this->error("结算失败");
    }
}