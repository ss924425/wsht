<?php

namespace api\home\controller;

use think\Db;
use think\Exception;
use think\Validate;
use cmf\controller\RestBaseController;

class IndexController extends RestBaseController
{
    public function config()
    {
        try {
            //增加访问次数
            db('self_task_scan')->where('id', 1)->setInc('times', 1);
            //首页轮播图
            $slide_img = db('home_page_img')->where(['type' => 0, 'position' => 1])->field('img,img_url')->select()->toArray();
            $img = array();
            if (!$slide_img) {
                $img = array();
            } else {
                foreach ($slide_img as $k => $v) {
                    $img[] = cmf_get_image_url($v['img']);
                    $img[] = $v['img_url'];
                }
            }

            $data['slide'] = $img;
            $img = array();
            $slide_img = db('home_page_img')->where(['type' => 0, 'position' => 2])->field('img,img_url')->select()->toArray();
            if (!$slide_img) {
                $img = array();
            } else {
                foreach ($slide_img as $k => $v) {
                    $img[$k]['img_url'] = cmf_get_image_url($v['img']);
                    $img[$k]['url'] = $v['img_url'];
                }
            }

            $one_img = db('home_page_img')->where(['type' => 0, 'position' => 3])->field('img,img_url')->select()->toArray();
            if ($one_img) {
                $task_img['img_url'] = cmf_get_image_url($one_img[0]['img']);
                $task_img['url'] = $one_img[0]['img_url'];
            } else {
                $task_img['img_url'] = '';
                $task_img['url'] = '';
            }
            $two_img = db('home_page_img')->where(['type' => 0, 'position' => 4])->field('img,img_url')->select()->toArray();
            if ($two_img) {
                $team_img['img_url'] = cmf_get_image_url($two_img[0]['img']);
                $team_img['url'] = $two_img[0]['img_url'];
            } else {
                $team_img['img_url'] = '';
                $team_img['url'] = '';
            }
            $three_img = db('home_page_img')->where(['type' => 0, 'position' => 5])->field('img,img_url')->select()->toArray();
            if ($three_img) {
                $income_img['img_url'] = cmf_get_image_url($three_img[0]['img']);
                $income_img['url'] = $three_img[0]['img_url'];
            } else {
                $income_img['img_url'] = '';
                $income_img['url'] = '';
            }

            $four_img = db('home_page_img')->where(['type' => 0, 'position' => 6])->field('img,img_url')->select()->toArray();
            if (!empty($four_img)) {
                $cate_img['img_url'] = cmf_get_image_url($four_img[0]['img']);
                $cate_img['url'] = $four_img[0]['img_url'];
            } else {
                $cate_img['img_url'] = '';
                $cate_img['url'] = '';
            }

            $five_img = db('home_page_img')->where(['type' => 0, 'position' => 7])->field('img,img_url')->select()->toArray();
            if ($five_img) {
                $dis_img['img_url'] = cmf_get_image_url($five_img[0]['img']);
                $dis_img['url'] = $five_img[0]['img_url'];
            } else {
                $dis_img['img_url'] = '';
                $dis_img['url'] = '';
            }
            $data['pubslide'] = $img;
            $data['top_img'] = [$task_img, $team_img, $income_img, $dis_img];
            $data['cate_img'] = $cate_img;
            //站点信息
            $site_info = cmf_get_option('site_info');
            $site_info['dhead'] = cmf_get_image_url($site_info['dhead']);
            $site_info['kefucode'] = cmf_get_image_url($site_info['kefucode']);
            $site_info['qqcode'] = cmf_get_image_url($site_info['qqcode']);
            $data['site_info'] = $site_info;
            $data['kefu'] = htmlspecialchars_decode($site_info['site_kefu']);
            $data['qrcode_info'] = htmlspecialchars_decode($site_info['qrcode_info']);
            //首页公告标题列表
            $notice = db('notice')->where(['type' => 1, 'delete' => 0, 'position' => 1])->order('id', 'desc')->column('title');
            $data['notice'] = $notice ? $notice : array('请在后台配置首页公告');
            //发任务大厅公告标题列表
            $notice2 = db('notice')->where(['type' => 1, 'delete' => 0, 'position' => 2])->order('id', 'desc')->column('title');
            $data['pubnotice'] = $notice2 ? $notice2 : array('请在后台配置发任务大厅公告');
            //动态图标
            //有米配置
            $data['youmi'] = cmf_get_option('youmi_setting');
            $data['xieyi'] = cmf_get_option('xieyi_setting');
            $data['help'] = cmf_get_option('help_setting');
            $data['help']['help_setting'] = isset($data['help']['help_setting']) ? htmlspecialchars_decode($data['help']['help_setting']) : '';
            $data['xieyi']['xieyi_setting'] = isset($data['xieyi']['xieyi_setting']) ? htmlspecialchars_decode($data['xieyi']['xieyi_setting']) : '';
            $data['fatask']['task_explain'] = isset($data['fatask']['task_explain']) ? htmlspecialchars_decode($data['fatask']['task_explain']) : '';
            $data['dotask']['do_task_explain'] = isset($data['dotask']['do_task_explain']) ? htmlspecialchars_decode($data['dotask']['do_task_explain']) : '';
            //发布任务配置
            $data['selftask'] = cmf_get_option('selftask_setting');

            //升级会员金额
            $data['user_setting'] = cmf_get_option('user_setting');
            $data['upgrade_setting'] = cmf_get_option('upgrade_setting');
            $data['upgrade_setting']['vip_desc'] = isset($data['upgrade_setting']['vip_desc']) ? htmlspecialchars_decode($data['upgrade_setting']['vip_desc']) : '';
            $data['upgrade_setting']['credit_score_desc'] = isset($data['upgrade_setting']['credit_score_desc']) ? htmlspecialchars_decode($data['upgrade_setting']['credit_score_desc']) : '';

            //积分配置
            $data['score_setting'] = cmf_get_option('score_setting');

            $this->success('', $data);
        } catch (Exception $exception) {
            $this->error('系统异常，请重试！' . $exception->getMessage());
        }
    }

    // api 首页
    public function index()
    {
        //人气
        $u = db('user')->where(['user_type' => 1])->count();
        $t = db('task_branch')->count();
        $r = db('task')->sum('com_num');
        $user_setting = db('option')->where(['option_name' => 'user_setting'])->value('option_value');
        $user_setting = json_decode($user_setting, true);
        $staticinfo['u'] = $u + $user_setting['popularity'];
        $staticinfo['t'] = $t + $user_setting['release'];
        $staticinfo['r'] = $r + $user_setting['complete'];

        if ($staticinfo) {
            $this->success('查询首页信息完成！', $staticinfo);
        }
        $this->error('查询首页信息失败！', '');
    }

    /**
     * 全部任务
     * @param p 当前页
     * @param recharge 任务类型 0：分期任务 1：充值任务  2：应用任务
     */
    public function allTask()
    {
        $request = $this->request->param();
        $p = isset($request['p']) && $request['p'] ? $request['p'] : 1;
        $pagesize = 10;
        $start = ($p - 1) * 10;

        $uid = $this->userId;
        $where = 'A.id>0 ';
        $text = $this->request->param('text');//任务标题关键词

        if (isset($request['recharge'])) {
            $where .= " and (A.recharge={$request['recharge']}) ";
        }

        if (!empty($text)) {
            $where .= " and ((A.title like '%" . $text . "%') or (A.cat_name like '%" . $text . "%'))";
        }


        $relative_task = '';
        //任务类型 0：分期任务 1：充值任务  2：应用任务
        if (!empty($uid)) {
            $u_task = db('task_receive')->distinct(true)->where(['uid' => $uid])->column('tid');
            //接到任务的相同游戏的任务
            if (!empty($u_task)) {
                $gamewhere['id'] = ['in', $u_task];
                if (isset($request['recharge'])) {
                    $gamewhere['recharge'] = $request['recharge'];
                }
                $game_ids = db('task')->distinct(true)->where($gamewhere)->column('game_id');
                $task_ids = db('task')->distinct(true)->where('game_id', 'in', $game_ids)->column('id');
                if ($task_ids) {
                    $relative_task = " and A.id not in (" . implode(',', $task_ids) . ")";
                }
            }
        } else {
            $u_task = [];
        }

        $field = "A.id, A.title, A.cat_name, A.thumb, A.com_num, A.rel_num, A.task_money, A.end_time, A.recharge, A.invalid, A.display_num";

        $where .= " and (A.isdelete=0) ";
        $where .= " and (A.invalid=0) ";
        $time = date('Y-m-d H:i:s');
        if ($u_task) {
            $user_task = implode(',', $u_task);
            $sql = "select $field from (select mt.*,mc.cat_name from mc_task mt left join mc_cat mc on mt.catid=mc.id order by mt.id desc) A where (A.begin_time<'$time')  and (A.id not in ($user_task)) and $where $relative_task group by A.game_id order by A.id desc limit $start,$pagesize";
        } else {
            $sql = "select $field from (select mt.*,mc.cat_name from mc_task mt left join mc_cat mc on mt.catid=mc.id order by mt.id desc) A where (A.begin_time<'$time')  and $where $relative_task group by A.game_id order by A.id desc limit $start,$pagesize";
        }

        $task = Db::query($sql);

        if (empty($task)) {
            $this->success('暂无数据');
        }

        foreach ($task as $k => $v) {
            $task[$k]['isSystem'] = 1;//系统任务

            if (strtotime($v['end_time']) > time()) {
                $task[$k]['isInvalid'] = 0;
            } else {
                $task[$k]['isInvalid'] = 1;
            }

            if (!$uid) {
                $task[$k]['receive_state'] = "抢任务";
            } else {
                $receive = db('task_receive')->where(['tid' => $v['id'], 'uid' => $uid])->field('receive_type')->find();
                if ($receive) {
                    if ($receive['receive'] == 0) {
                        $task[$k]['receive_state'] = "进行中";
                    } else if ($receive['receive'] == 2) {
                        $task[$k]['receive_state'] = "待审核";
                    } else {
                        $task[$k]['receive_state'] = "已完结";
                    }
                } else {
                    $task[$k]['receive_state'] = "抢任务";
                }
            }

            //统计任务总量
            $rel_num = db('task_branch')->where(['tid' => $v['id']])->sum('quantity');
            $task[$k]['rel_num'] = $rel_num + $v['display_num'];
            //统计任务完成数量
            $com_num = db('task_receive')->where(['tid' => $v['id']])->count();
            $task[$k]['com_num'] = $com_num + $v['display_num'];
        }

        $this->success('', $task);
    }

    //我的任务
    public function MyTask()
    {
        $request = $this->request->param();
        $p = $request['p'];
        if (empty($p)) {
            $p = 1;
        }
        $uid = $this->getUserId();
        if (empty($uid)) $this->error(['code' => 10001, 'msg' => $this->standard_code['10001']]);

        if (isset($request['recharge'])) {
            $where['a.recharge'] = $request['recharge'];
        }

        $where['r.uid'] = $uid;

        $field = ['t.id', 'title', 'thumb', 'com_num', 'rel_num', 'task_money', 'end_time', 'recharge', 'r.tid', 'r.receive_type', 'r.receive_status', 't.invalid', 't.display_num', 'c.cat_name'];

        $task = db('task_receive')->alias('r')
            ->group('tid')
            ->join('task t', 't.id = r.tid', 'left')
            ->join('cat c', 'c.id = t.catid', 'left')
            ->where(['uid' => $uid, 't.recharge' => $request['recharge']])
            ->field($field)
            ->order('id', 'desc')
            ->limit(10)->page($p)
            ->select()->toArray();

        if (empty($task)) {
            $this->success('暂无数据');
        }

        foreach ($task as $k => $v) {
            $end = strtotime($task[$k]['end_time']);
            if ($end - time() > 0) {
                $task[$k]['isInvalid'] = 0;
            } else {
                $task[$k]['isInvalid'] = 1;
            }

            if ($v['recharge'] == 0) {
                $task[$k]['recharge'] = "分期任务";
            } else if ($v['recharge'] == 1) {
                $task[$k]['recharge'] = "充值任务";
            } else if ($v['recharge'] == 2) {
                $task[$k]['recharge'] = "应用任务";
            }

            if ($v['receive_type'] == 0 && $v['receive_status'] == 0) {
                $task[$k]['user_task_state'] = '进行中';
            } elseif ($v['receive_type'] == 2 && $v['receive_status'] == 0) {
                $task[$k]['user_task_state'] = '待审核';
            } elseif ($v['receive_type'] == 3 && $v['receive_status'] == 1) {
                $task[$k]['user_task_state'] = '已通过';
            } elseif ($v['receive_type'] == 1 && $v['receive_status'] == 2) {
                $task[$k]['user_task_state'] = '被驳回';
            } else {
                $task[$k]['user_task_state'] = '未知状态';
            }

            //统计任务总量
            $rel_num = db('task_branch')->where(['tid' => $v['id']])->sum('quantity');
            $task[$k]['rel_num'] = $rel_num + $v['display_num'];
            //统计任务完成数量
            $com_num = db('task_receive')->where(['tid' => $v['id']])->count();
            $task[$k]['com_num'] = $com_num + $v['display_num'];
        }

        $this->success('查询我的任务完成！', $task);
    }

    //任务详情页面
    public function detail()
    {
        $request = $this->request->param();
        $id = $request['id'];
        $uid = $this->getUserId();
        if (empty($uid)) $this->error(['code' => 10001, 'msg' => $this->standard_code['10001']]);

        $task = db('task')->where(['id' => $id])->field('id,thumb,title,recharge,settle_type,invalid')->find();
        if (empty($task)) {
            $this->error('不存在此任务！');
        }
        $task['branch'] = db('task_branch')->where(['tid' => $id])->field('id as bid,b_title')->select()->toArray();
        if (empty($task['branch'])) {
            $this->error('暂未设置分支任务');
        }
        $this->success('success', $task);
    }


    public function detail_info()
    {
        $id = $this->request->param('id');
        $uid = $this->getUserId();
        if (empty($uid)) $this->error(['code' => 10001, 'msg' => $this->standard_code['10001']]);
        $task = db('task_branch')->alias('b')
            ->join('mc_task t', 'b.tid = t.id')
            ->where(['b.id' => $id])
            ->field('t.remark,game_id,thumb,title,com_num,rel_num,garea,platform,task_link,recharge,b_money,b_title,b.id,b_validate,b_remark,b_remark_img,b_begin_time,b_end_time,explain1,e_photo1,explain2,e_photo2,explain3,e_photo3,explain4,e_photo4,b_invalid,last_sub_time,t.package')
            ->find();
        if (empty($task)) {
            $this->error('不存在此任务');
        }

        $task['remark'] = html_entity_decode($task['remark']);
        $task['b_remark'] = html_entity_decode($task['b_remark']);

        $receive = db('task_receive')->alias('r')
            ->join('mc_user u', 'u.id = r.uid')
            ->field('r.*,u.user_login,u.avatar')
            ->where(['r.bid' => $id, 'r.uid' => $uid])->find();

        //用户与任务的关系
        if (empty($receive)) {
            $task['user_task'] = -1;
            $task['ut_id'] = 0;
            $task['submit'] = "";
            $end = strtotime($task['b_end_time']);
        } else {
            $task['user_task'] = $receive['receive_type'];
            $task['ut_id'] = $receive['id'];
            $task['submit'] = $receive['submit'];
            $end = $receive['termination'];
        }

        $now = time();
        $diff = $end - $now;

        if ($diff < 0) {
            //小时
            $task['h'] = 0;
            //分钟
            $task['m'] = 0;
            //秒
            $task['s'] = 0;
            $task['isInvalid'] = 0;
        } else {
            //小时
            $task['h'] = floor($diff / 3600);
            //分钟
            $task['m'] = floor($diff % 3600 / 60);
            //秒
            $task['s'] = floor($diff % 3600 % 60);
            $task['isInvalid'] = 1;
        }
        $begin = strtotime($task['b_begin_time']);
        if ($begin >= $now) {
            $task['isInvalid'] = 2;
        }
        $this->success('查询任务详情页完成！', $task);
    }

    //接任务
    public function rob_task()
    {
        if ($this->request->isPost()) {
            $request = $this->request->param();

            $id = $request['id'];
            $token = $request['token'];
            $data = db('user_token')->where(['token' => $token])->field('user_id')->find();
            $uid = $data['user_id'];

            if ($uid > 0) {
                $user = db('user')->where(['id' => $uid])->find();
//                if($user['vip_type'] == 1){
//                    $this->error('会员等级达到VIP会员才能抢任务');
//                }
                if ($user['vip_type'] > 1) {
                    if ($user['vip_end_time'] < time()) {
                        $this->error('您的会员已到期');
                    }
                }
                $receive = db('task_receive')->where(['uid' => $uid, 'bid' => $id])->find();
                if (!empty($receive)) {
                    $this->error('你已经抢到这个任务了');
                }

                $branch = db('task_branch')->where(['id' => $id])->find();
                if ($branch['b_isdelete'] == 1) {
                    $this->error('该任务已删除');
                }
                if ($branch['b_invalid'] == 1) {
                    $this->error('该任务已抢完');
                }
                if (strtotime($branch['b_end_time']) < time()) {
                    $this->error('该任务已过期', [$id]);
                }

                $task = db('task')->where(['id' => $branch['tid']])->find();
                if ($task['invalid'] == 1) {
                    $this->error('该任务已下架', [$id]);
                }

                if ($branch['last_sub_time'] == 0) {
                    if ($task['recharge'] == 0) {
                        //分期任务提交截止时间
                        $termination = strtotime(date('Y-m-d 23:59:59', time()));
                    } else {
                        //充值和应用任务提交截止时间
                        $termination = time() + (24 * 60 * 60);
                    }
                } else {
                    $termination = time() + ($branch['last_sub_time'] * 60 * 60);
                }

//                if($branch['last_sub_time']){
//                    $termination = strtotime($branch['last_sub_time']);
//                }else{
//                    $termination = strtotime(date('Y-m-d',strtotime('+1 day')));
//                }

                if (isset($request['incubator'])) {
                    $incubator = $request['incubator'];
                } else {
                    $incubator = 0;
                }

                $agent = db('user')->where(['id' => $uid])->value('agentId');
                $flag = false;
                $err = '抢任务失败';
                try {
                    $res = Db::query('call rob_task(:uid,:bid,:receive_type,:tid,:agentId,:termination,:incubator)', ['uid' => $uid, 'bid' => $id, 'receive_type' => 0, 'tid' => $branch['tid'], 'agentId' => $agent, 'termination' => $termination, 'incubator' => $incubator]);

                    foreach ($res[0][0] as $k => $v) {
                        if ($v == 1) {
                            $flag = true;
                            $err = '抢任务成功';
                            break;
                        }
                    }
                } catch (\Exception $e) {
                    $flag = false;
                    $err = '抢任务失败(Exception)';
                }
                if ($flag) {
                    $this->success($err, ['user_task' => 0]);
                } else {
                    $this->error($err);
                }
            } else {
                $err = ['code' => -1, 'msg' => 'token无效!'];
                $this->error($err);
            }
        }
    }

    //提交任务
    public function com_task()
    {
        if ($this->request->isPost()) {
            $request = $this->request->param();
            //接收当前分期任务的id
            $id = $request['id'];
            //接收任务记录表中的需要修改的数据的id
            $rid = $request['rid'];
            //接收当前用户的token
            $token = $request['token'];
            $user_id = db('user_token')->where(['token' => $token])->field('user_id')->find();
            $uid = $user_id['user_id'];
            if ($uid > 0) {
                $receive = db('task_receive')->where(['id' => $rid])->find();

                if ($receive['termination'] < time()) {
                    $this->error('已超出提交截止时间，无法提交');
                }
                //主任务id
                $tid = db('task_branch')->where(['id' => $id])->value('tid');

                $data['submit'] = $this->request->param('submit');
                $data['submit_order'] = $this->request->param('submit_order');

                if ($data['submit']) {
                    $ver1 = db('task_receive')->where(['bid' => $id, 'submit' => $data['submit']])->find();
                    if ($ver1) {
                        $this->error('该依据已使用');
                    }
                }
                if ($data['submit_order']) {
                    $ver2 = db('task_receive')->where(['bid' => $id, 'submit_order' => $data['submit_order']])->find();
                    if ($ver2) {
                        $this->error('该订单号已使用');
                    }
                }

                try {
                    $data['submit_phone'] = $this->request->param('submit_phone');
                    $img1 = $this->request->param('submit_img1');
                    if ($img1) {
                        $src1 = $this->base64_image_content($img1, '_img1');
                        if ($src1) {
                            $data['submit_img1'] = $src1;
                        }
                    }

                    $img2 = $this->request->param('submit_img2');
                    if ($img2) {
                        $src2 = $this->base64_image_content($img2, '_img2');
                        if ($src2) {
                            $data['submit_img2'] = $src2;
                        }
                    }
                    $img3 = $this->request->param('submit_img3');
                    if ($img3) {
                        $src3 = $this->base64_image_content($img3, '_img3');
                        if ($src3) {
                            $data['submit_img3'] = $src3;
                        }
                    }
                    $img4 = $this->request->param('submit_img4');
                    if ($img4) {
                        $src4 = $this->base64_image_content($img4, '_img4');
                        if ($src4) {
                            $data['submit_img4'] = $src4;
                        }
                    }

                    $data['receive_type'] = 2;
                    $data['submit_time'] = time();
                    $cre_time = db('task_receive')->where(['id' => $rid])->value('receive_time');
                    $data['time_diff'] = time() - $cre_time;

                    $res = db('task_receive')->where(['id' => $rid])->update($data);

                } catch (\Exception $e) {

                    $addFail['taskId'] = $tid;
                    $addFail['fail'] = $e->getMessage();
                    $addFail['type'] = 3;
                    $addFail['time'] = time();
                    Db::name('fail_log')->insert($addFail);

                    $this->error('提交失败');
                }
                $this->success('提交成功', ['user_task' => 2, 'tid' => $tid]);
            } else {
                $err = ['code' => -1, 'msg' => 'token无效!'];
                $this->error($err);
            }
        }
    }

    //用户下载日志
    public function download_log()
    {
        if ($this->request->isPost()) {
            $request = $this->request->param();
            $id = $request['id'];
            $token = $request['token'];
            $user_id = db('user_token')->where(['token' => $token])->field('user_id')->find();
            $uid = $user_id['user_id'];
            if ($uid > 0) {
                $add['user_id'] = $uid;
                $add['task_id'] = $id;
                $add['link'] = db('task')->where(['id' => $id])->value('task_link');
                $add['create_time'] = time();
                $res = Db::name('loading_log')->insert($add);

                $rec = Db::name('task')->where(['id' => $id])->setInc('download_num');
                if ($res && $rec) {
                    $this->success('成功');
                } else {
                    $addFail['taskId'] = $id;
                    $addFail['fail'] = '下载任务失败，用户id：' . $uid . '任务id：' . $id;
                    $addFail['type'] = 2;
                    $addFail['time'] = time();
                    Db::name('fail_log')->insert($addFail);

                    $this->error('失败');
                }
            } else {
                $err = ['code' => -1, 'msg' => 'token无效!'];
                $this->error($err);
            }
        }
    }

    function base64_image_content($base64_image_content, $num)
    {
        //匹配出图片的格式
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_image_content, $result)) {
            $path = $_SERVER['DOCUMENT_ROOT'] . '/upload/admin';
            $type = $result[2];
            $new_file = $path . "/" . date('Ymd', time()) . "/";
            if (!file_exists($new_file)) {
                //检查是否有该文件夹，如果没有就创建，并给予最高权限
                mkdir($new_file, 0700);
            }
            $new_file = $new_file . time() . $num . ".{$type}";
            if (file_put_contents($new_file, base64_decode(str_replace($result[1], '', $base64_image_content)))) {
                return "admin/" . date('Ymd', time()) . "/" . time() . $num . ".{$type}";
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    //用户任务消息提醒
    public function remind()
    {

    }

    //批量添加任务
    public function timing_task()
    {
        //条件 ： 任务类型  是否删除  是否下架
        $task = db('task')->where(['recharge' => 0, 'isdelete' => 0, 'invalid' => 0])->field('id,recharge,game_id')->select()->toArray();

        $list1 = [];
        $list2 = [];
        $list3 = [];
        foreach ($task as $k => $v) {
            if ($v['recharge'] == 0) {
                $list1[$v['game_id']] = $v['id'];
            } elseif ($v['recharge'] == 1) {
                $list2[$v['game_id']] = $v['id'];
            } elseif ($v['recharge'] == 2) {
                $list3[$v['game_id']] = $v['id'];
            }
        }
        $list = array_merge($list1, $list2, $list3);

        //定义空的变量，用来存放失败的任务id
        $fail_task = '';
        foreach ($list as $k => $v) {
            $main_task = db('task')->where(['id' => $v])->find();
            //拷贝主线任务，并进行数据处理
            $main_task['create_time'] = time();
            $main_task['begin_time'] = date('Y-m-d H:i:s', strtotime($main_task['begin_time'] . "+1 day"));
            $main_task['end_time'] = date('Y-m-d H:i:s', strtotime($main_task['end_time'] . "+1 day"));
            $main_task['com_num'] = 0;
            //提取当前主任务id
            $task_id = $main_task['id'];
            unset($main_task['id']);

            //查询当前任务所属游戏今天是否已经添加过
            $f_task = db('task')->where(['game_id' => $main_task['game_id']])->whereTime('create_time', 'today')->find();

            //如果当前游戏今天没有添加过任务，则执行添加操作
            if (empty($f_task)) {
                //添加处理过后的数据
                $res = Db::name('task')->insertGetId($main_task);
                if ($res) {
                    //拷贝分期任务，并进行数据处理
                    $bran_task = db('task_branch')->where(['tid' => $v])->select()->toArray();
                    foreach ($bran_task as $k1 => $v1) {
                        $bran_task[$k1]['tid'] = $res;
                        $bran_task[$k1]['b_create_time'] = time();
                        $bran_task[$k1]['b_begin_time'] = date('Y-m-d H:i:s', strtotime($v1['b_begin_time'] . "+1 day"));
                        $bran_task[$k1]['b_end_time'] = date('Y-m-d H:i:s', strtotime($v1['b_end_time'] . "+1 day"));
                        $bran_task[$k1]['b_invalid'] = 0;
                        unset($bran_task[$k1]['id']);
                    }
                    //添加处理过后的数据
                    Db::name('task_branch')->insertAll($bran_task);
                } else {
                    //将执行添加失败的主任务ID记录
                    $fail_task .= $task_id . ',';

                    //将错误信息添加进错误记录
                    $addFail['taskId'] = $fail_task;
                    $addFail['fail'] = '自动添加任务执行失败';
                    $addFail['time'] = time();
                    $addFail['type'] = 1;
                    Db::name('fail_log')->insert($addFail);
                }
            }
        }
    }

    /**
     * 获取提现列表
     * @param 传入参数1
     * @param 传入参数2
     * @return 返回格式json
     */
    public function cashlist()
    {
        $list = Db::name('cash')->alias('c')
            ->join('user u', 'c.uid = u.id', 'left')
//            ->where(['c.cash_status' => 1])
            ->field('c.cash_money,u.user_nickname')
            ->order('c.create_time desc')
            ->limit(5)->select()->toArray();
        if (empty($list))
            $this->error('暂无提现数据');
        else
            $this->success('查询成功', $list);
    }

}
