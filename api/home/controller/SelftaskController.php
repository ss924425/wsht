<?php

namespace api\home\controller;

use api\home\model\NewsModel;
use app\admin\controller\DouyinUrlController;
use app\common\model\SelfTaskModel;
use api\home\model\UserModel;
use plugins\mobile_code_demo\MobileCodeDemoPlugin;
use think\Db;
use think\Exception;
use think\Validate;
use cmf\controller\RestBaseController;
use think\Log;

use Qiniu\Auth; //七牛鉴权使用
use Qiniu\Storage\UploadManager; //七牛上传使用

class SelftaskController extends RestBaseController
{


    public function index()
    {
        $setting = cmf_get_option('selftask_setting');

        //分类获取
        $sorts = db('self_task_sort')->field('id as sortid,name,img')->where(['pid' => 0])->where('status', 1)->order(['number' => 'desc', 'id' => 'desc'])->select()->toArray();

        if (empty($sorts)) {
            $this->error('请先添加任务分类');
        }
        foreach ($sorts as $k => $sort) {
            if (!empty($sort['img'])) {
                $sorts[$k]['img'] = cmf_get_image_preview_url($sort['img']);
            }
        }

        $data['sort'] = $sorts;
        //首页数据
        $times = db('self_task_scan')->find();
        $data['statistics']['renqi'] = $times['times'] + $setting['falsetimes'];
        $data['statistics']['fabu'] = $times['pubed'] + $setting['falsepubed'];
        $data['statistics']['wancheng'] = $times['comed'] + $setting['falsecomed'];

        $this->success('success', $data);
    }

    // 个人发任务列表
    public function alltask()
    {
        $p = $this->request->param('p');
        $p = max($p, 1);
        $pagesize = 10;
        $start = ($p - 1) * $pagesize;

        $user_id = $this->getUserId();
        $receive_taskids = db('self_task_receive')->where('userid', $user_id)->column('taskid');
        if ($receive_taskids) $where['s.id'] = ['not in', $receive_taskids];

        $text = $this->request->param('text');//任务标题关键词
        $type = $this->request->param('type');//分类ID

        $where['s.cl_is_back'] = 0;
        $where['s.iscount'] = 0;
        $time = time();
        $where['s.status'] = 0;
        $where['s.type'] = 0;
        $where['s.end'] = ['>', $time];
        $where['s.oldnum'] = ['>', 0];
        if (!empty($type)) {
            $where['sortid'] = intval($type);
        }
        if (!empty($text)) {
            $where['title'] = array('like', "%{$text}%");
        }

        $select = 's.id,s.orderid,s.order_aa,s.sort_img,s.title,s.money,s.scan,s.isempty,s.istop,s.start,s.puber,s.num,s.oldnum,s.limitnum,s.iscount,s.ispause,s.userid,s.address,c.name as c_name';
        $order = 's.iscount ASC,s.isstart ASC,s.isempty ASC,s.istop DESC,s.id DESC ';
        $data = Db::name('self_task')->alias('s')
            ->join('self_task_sort c', 's.sortid = c.id', 'left')
            ->where($where)
            ->field($select)
            ->limit($start, $pagesize)
            ->order($order)
            ->select()
            ->toArray();

        if (empty($data)) {
            $this->success('暂无更多数据');
        }


        $setting = cmf_get_option('selftask_setting');
        $site_setting = cmf_get_option('site_info');
        $model_task = new SelfTaskModel();
        $alldata = [];

        foreach ($data as $k => $v) {

            // 屏蔽关键词
            $data[$k]['title'] = $model_task::hideKey($setting['hidetxt'], $data[$k]['title']);

            // 分类头像
            $data[$k]['sort_img'] = isset($v['sort_img']) ? 'http://' . $_SERVER['HTTP_HOST'] . '/upload/' . $v['sort_img'] : '';

            $user = array();
            if (!empty($v['userid'])) {
                $user = db('user')->find($v['userid']);
                if (empty($user['avatar'])) {
                    $data[$k]['avatar'] = cmf_get_image_url($site_setting['dhead']);
                } else {
                    $data[$k]['avatar'] = cmf_get_image_url($user['avatar']);
                }
                if (empty($user['user_nickname'])) {
                    $data[$k]['user_nickname'] = mask_mobile($user['mobile']);
                } else {
                    $data[$k]['user_nickname'] = $user['user_nickname'];
                }
            } else {
                $data[$k]['avatar'] = cmf_get_image_url($site_setting['dhead']);
                $data[$k]['user_nickname'] = $site_setting['site_name'];
            }

            $thisstatus = $model_task::getStatusInTask($user_id, $v['id'], $v['num'], $v['userid'], $v['limitnum'], true);

            if ($v['isempty'] == 0) {
                $data[$k]['statusstr'] = '<li class="index_item_status fr status_ing">任务进行中</li>';
            } else {
                $data[$k]['statusstr'] = '<li class="index_item_status fr status_no">已接完</li>';
            }
            if ($v['ispause'] == 1) {
                $data[$k]['statusstr'] = '<li class="index_item_status fr status_no">任务已关闭</li>';
            }

            if (!empty($thisstatus['status'])) {
                $data[$k]['statusstr'] = '<li class="index_item_status fr status_no">您不能接此任务</li>';
                if ($thisstatus['status'] == 1) {
                    $data[$k]['statusstr'] = '<li class="index_item_status fr status_no">您已接了此任务</li>';
                }
            }

            if ($v['start'] > time()) {
                $data[$k]['statusstr'] = '<li class="index_item_status fr status_no">还未开始</li>';
            }
            if ($v['iscount'] == 1) {
                $data[$k]['statusstr'] = '<li class="index_item_status fr status_no">已结束</li>';
            }

            $data[$k]['topstr'] = '';
            if ($v['istop'] == 1) {
                $data[$k]['topstr'] = '<span class="top_task">置顶</span>';
            }

            $data[$k]['taskid'] = '<span class="font_mini">(' . $v['id'] . ')</span>';
            $isCanReceive = $model_task::isCanreceive($user_id, $v['id'], $v['limitnum']);
            //现在只显示未结算的,不是自己发的,份数还有的,时间未结束的(同时满足)
            if ($thisstatus['status'] != 1 && $v['isempty'] == 0 && $v['userid'] != $user_id && $isCanReceive) {

                array_push($alldata, $data[$k]);
            }
        }

        $this->success('success', $alldata);
    }


    public function upload_taskimg()
    {
        $file = request()->file('image');

        // 移动到框架应用根目录/public/uploads/ 目录下
        $file_path = ROOT_PATH . 'public/upload/task';
        if (!file_exists($file_path)) {
            mkdirs($file_path);
        }
        $info = $file->validate(['size' => 10 * 1024 * 1024, 'ext' => 'jpg,png,jpeg'])->move($file_path);
        if ($info) {
            $saveExtension = $info->getSaveName();
            $savename = $info->getFilename();
            $savepath = str_replace($savename, '', $saveExtension);
            $image = \think\Image::open($file_path . DS . $saveExtension);
            $newsavepath = rtrim($file_path, DS) . DS . trim($savepath, DS) . DS . "thumb_" . $savename;

            $res = $image->thumb(200, 200, 1)->save($newsavepath);
            if (!$res) {
                @unlink(rtrim($file_path, DS) . DS . $saveExtension);
                $this->error('上传失败');
            }
        } else { // 上传失败获取错误信息
            $this->error($file->getError());
        }
        $this->success('上传成功', "task" . DS . $saveExtension);
    }

    /**
     * 使用七牛云上传图片
     * @param 传入参数1
     * @param 传入参数2
     * @return 返回格式json
     */
    public function upload_taskimgnew()
    {
        try {
            if (empty($_FILES['image']['tmp_name'])) {
                $this->error('上传图片发生错误');
            } else {
                //获取上传的临时文件
                $file = $_FILES['image']['tmp_name'];
                $ext = explode('.', $_FILES['image']['name']);  //获取文件扩展名
                //构建上传路径名称
                $path = date("Y") . DS . date('m') . DS . date('d')
                    . DS . substr(md5($file, 0), 10) . time() . $ext['1'];

                $uploadSetting = cmf_get_upload_setting();
                $ym = $uploadSetting['file_types']['file']['bomain'];
                $accessKey = $uploadSetting['file_types']['file']['accessKey'];
                $secretKey = $uploadSetting['file_types']['file']['secretKey'];
                $auth = new Auth($accessKey, $secretKey);
                $bucket = $uploadSetting['file_types']['file']['bucket'];
                $token = $auth->uploadToken($bucket);

                //初始化
                $uploadManager = new UploadManager();

                list($ret, $err) = $uploadManager->putFile($token, $path, $file);

                if (!$err) {
                    $this->success('上传成功', $ym . '/' . $ret['key']);
                } else {
                    $this->error('上传失败');
                }
            }
        } catch (Exception $exception) {
            $this->error('Exception系统异常' . $exception->getMessage());
        }
    }


    //分类详情   ↓↓↓↓↓下面的方法发任务做任务步骤
    public function sortDetail()
    {
        $sortid = $this->request->param('sortid');
        $sort = db('self_task_sort')->where('id', $sortid)->find();
        if (empty($sort)) {
            $this->error('不存在此任务分类');
        }
        $sort['img'] = cmf_get_image_preview_url($sort['img']);
        $sort['title'] = htmlspecialchars_decode($sort['title']);
        $sort['content'] = htmlspecialchars_decode($sort['content']);

        $other = json_decode($sort['other'], true);
        if (isset($other['taskimg'])) {
            foreach ($other['taskimg'] as $kk => $taskimg) {
                $other['taskimg'][$kk] = cmf_get_image_preview_url($taskimg);
            }
        }
        $other['hcontent'] = isset($other['hcontent']) ? htmlspecialchars_decode($other['hcontent']) : '';
        $sort['other'] = $other;
        $this->success('success', $sort);
    }

    /**
     *  →→→ 获取任务步骤和绑定账号步骤 ←←←
     */
    public function gettypestep()
    {
        $sortid = input('sortid');
        if (empty($sortid))
            $this->error('查询失败！', '');
        $task_explain = cmf_get_option('task_explain');
        $do_task_explain = cmf_get_option('do_task_explain' . $sortid)['do_task_explain'];
        $bind_step = cmf_get_option("bind_step" . "$sortid");

//        if($task_explain){
//            foreach ($task_explain as $k=>$v){
//                $task_explain[$k] = cmf_replace_content_file_url(htmlspecialchars_decode($v));
//            }
//        }

        if ($bind_step) {
            foreach ($bind_step as $k => $v) {
                $bind_step[$k] = cmf_replace_content_file_url(htmlspecialchars_decode($v));
            }
        }

        $this->success('查询成功', [
            'dotask' => $do_task_explain,
            'bind_step' => $bind_step,
        ]);
    }

    function hideKey($hidetxt, $content)
    {
        if (!empty($hidetxt)) {
            echo 11;
            die;
            $list = preg_split("/,|，/", trim($hidetxt, ',，'));
            foreach ($list as $find) {
                $content = str_replace($find, '*', $content);
            }
        }

        return $content;
    }

    //发布任务获取任务分类
    public function pubsort()
    {
        try {
            $sorts = db('self_task_sort')->where(['pid' => 0])->where('status', 1)->order(['number' => 'desc', 'id' => 'desc'])->select()->toArray();

            if (empty($sorts)) {
                $this->error('请先添加任务分类');
            }
            $data = [];
            foreach ($sorts as $k => $sort) {
                $row = db('self_task_sort')->where(['pid' => $sort['id']])->where('status', 1)->order(['number' => 'desc', 'id' => 'desc'])->select()->toArray();
                if (!empty($row)) {
                    foreach ($row as $rk => $rv) {
                        if (!empty($rv)) {
                            $rv['img'] = cmf_get_image_preview_url($rv['img']);
                            $rv['title'] = htmlspecialchars_decode($rv['title']);
                            $rv['content'] = htmlspecialchars_decode($rv['content']);
                            $row[$rk] = $rv;
                        }
                    }
                    array_push($data, [
                        'name' => $sort['name'],
                        'data' => $row
                    ]);
                }
            }
            $this->success('success', $data);
        } catch (Exception $exception) {
            $this->error('系统异常，请重试' . $exception->getMessage());
        }
    }

    //发布普通任务
    public function pub()
    {
        $param = $this->request->param();
        $user_id = $this->getUserId();
        if (empty($user_id)) $this->error(['code' => '10001', 'msg' => $this->standard_code['10001']]);
        $userinfo = $this->getUserInfo();

        $score_setting = cmf_get_option('score_setting');
        if ($userinfo['credit_score'] < $score_setting['minscore']) $this->error('您的信誉积分低于' . $score_setting['minscore'] . '分不能发布任务');

        if (empty($userinfo['user_nickname'])) {
            $this->error(['code' => 10006, 'msg' => $this->standard_code['10006']]);
        }
        $model_task = new SelfTaskModel();
        $data['sortid'] = intval($param['sortid']); //分类id

        $sort = db('self_task_sort')->where('id', '=', $data['sortid'])->find();
        $data['sort_img'] = isset($sort['img']) ? $sort['img'] : '';
        $data['appname'] = db('self_task_sort')->where('id', '=', $sort['pid'])->value('appname');

        $data['title'] = $param['title'];
        $data['chaolianjie'] = $param['chaolianjie'];
        $data['content'] = $param['content'];
        $data['hidecontent'] = isset($param['hidecontent']) ? $param['hidecontent'] : '';
        if (!empty($param['images'])) {
            $images = json_encode(explode(',', trim($param['images'], ',')));
            $data['images'] = $images;
        } else {
            $data['images'] = '';
        }
        $data['num'] = intval($param['num']);
        $data['oldnum'] = intval($param['num']);
        $data['money'] = sprintf('%.2f', $param['money']);
        $data['falsemoney'] = sprintf('%.2f', $param['money']);

        $data['replytime'] = intval($param['replytime']);
        $data['limitnum'] = intval($param['limitnum']);
//        $data['sex'] = intval($param['sex']);
        $data['ishide'] = intval($param['ishide']);

        $data['continue'] = intval($param['continue']);
        $data['istop'] = intval($param['istop']);

        if (!empty($param['linkname'])) {
            $link = array();
            $linknamearr = explode(',', trim($param['linkname'], ','));
            $linkurlarr = explode(',', trim($param['linkurl'], ','));
            $linkurlarr = $linkurlarr ? $linkurlarr : array();
            foreach ($linknamearr as $k => $v) {
                $linkitem['text'] = $v;
                $linkitem['url'] = isset($linkurlarr[$k]) ? $linkurlarr[$k] : '';
                $link[] = $linkitem;
            }
            $data['link'] = json_encode($link);
        }

        $check = $model_task::isCanPub($data);
        if ($check !== true) $this->error($check);

        $setting = cmf_get_option('selftask_setting');

        if ($data['money'] < $setting['leastcommoney']) {
            $this->error('任务赏金至少' . $setting['leastcommoney']);
        }

        // 算钱
        $taskmoney = $data['num'] * $data['money'];

        $server = $taskmoney * $setting['commonserver'] / 100;
        $server = max($server, $setting['commonserverleast']);
        if ($server < 0) {
            $server = 0;
        }

        $top = 0;
        if ($data['istop'] == 1) $top = sprintf('%.2f', $setting['topserver']);

        $continueday = 0;
        $totalcontinuemoney = 0; //连续发布后几天的钱
        $ewai = 0; // 额外奖励的钱 = 任务数量X额外奖励
        if ($data['continue'] == 1) {
            $continuemoney = sprintf('%.2f', $param['continuemoney']);
            $continueday = intval($param['continueday']);

            if ($continueday <= 0) $this->error('连续发布天数必须填正整数');

            $totalcontinuemoney = $continueday * ($taskmoney + $server + $top);
            $ewai = $continuemoney * $data['num'];
        }

        $total = $taskmoney + $server + $top + $totalcontinuemoney + $ewai;

        if (($userinfo['user_money'] + $userinfo['yong_money']) < $total) {
            $this->error(['code' => 10004, 'msg' => '您的余额不足，请先充值']);
        }

        // 保证金
        $isdeposit = $setting['isdeposit'];

        if ($userinfo['deposit'] < $total && empty($isdeposit)) {
            $thisneed = $total;
            $this->error(['code' => 10005, 'msg' => '您的保证金不足，发布此任务账户需留存' . $thisneed . '保证金']);
        }

        if ($userinfo['deposit'] < $isdeposit && $isdeposit > 0) {
            $this->error(['code' => 10005, 'msg' => '您的保证金不足，发布任务账户需留存' . $isdeposit . '保证金']);
        }

        Db::startTrans();
        if ($userinfo['user_money'] > $total) {
            $res = db('user')->where('id', $userinfo['id'])->setDec('user_money', $total);
        } else {
            $this->error(['code' => 10004, 'msg' => '您的余额不足，请先充值']);
        }
        if (!$res) {
            Db::rollback();
            $this->error('扣费出错，发布失败');
        }

        $newInfo = $this->getUserInfo();

        //写入变更日志
        $moneylog['user_id'] = $userinfo['id'];
        $moneylog['tid'] = 0;
        $moneylog['rid'] = 0;
        $moneylog['agentId'] = $userinfo['agentId'];
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
        $time1 = time();
        $data['costtop'] = $top;
        $data['userid'] = $userinfo['id'];
        $data['start'] = time();
        $data['createtime'] = time();
        $time = $setting['autoconfirm'] > 0 ? $setting['autoconfirm'] : 1;//发布的任务有效期
        $data['end'] = $time1 + $time * 3600;
        $data['status'] = 0;
        $data['isstart'] = 0;
        if ($setting['isverifytask'] == 1) $data['status'] = 1;//是否审核任务

        $data['costserver'] = $server;


        if ($data['continue'] == 1) {
            $continue['money'] = $continuemoney;
            $continue['totalnum'] = $data['num'];
            $continue['totalmoney'] = $data['num'] * $continue['money'];
            $continueid = db('self_task_continue')->insertGetId($continue);
            if (!$continueid) {
                Db::rollback();
                $this->error('写入连续任务出错，发布失败');
            }
            $data['continueid'] = $continueid;
        }
        if ($setting['isverifytask']) {
            $data['status'] = 1;//待审
        }

        // 审核人id
        $listdata = Db::query('select b.id from mc_user b  left  join mc_self_task a on a.admin_id=b.id and a.`status`=1  where  b.cl_admin=1 GROUP BY b.id order by count(a.id) asc limit 1');
        if (!empty($listdata)) {
            $data['admin_id'] = $listdata[0]['id'];
        }

        $id = db('self_task')->insertGetId($data);

        if (!$id) {
            Db::rollback();
            $this->error('写入任务出错，发布失败', db('self_task')->getLastSql());
        }

        $totalpub = $data['num'];
        if ($data['continue'] == 1) {

            $today = strtotime(date('Y-m-d', time()));
            for ($i = 0; $i < $continueday; $i++) {
                $newdata = $data;
                $newdata['isstart'] = 1;
                $newdata['start'] = $today + 24 * 3600 * ($i + 1);
                $newdata['end'] = $newdata['end'] + 24 * 3600 * ($i + 1);
                $newid = db('self_task')->insertGetId($newdata);
                if ($newid && $newdata['status'] == 0) { // 给上级提成
                    $upmoney = $newdata['costtop'] + $newdata['costserver'];
                    $model_task::pubGiveParent($setting, $userinfo, $newid, $upmoney);
                }
            }
            $totalpub = ($continueday + 1) * $data['num'];
        }
        // 发任务成功 通知会员
        if ($user_id) {
            $model_news = new NewsModel();
            $news = "您发布任务{$id}成功,请等待审核";
            $model_news::toUserNews($user_id, $news);
        }

        Db::commit();
        // 平台发布量
        db('self_task_scan')->where('id', 1)->setInc('pubed', $totalpub);
        db('self_task_scan')->where('id', 1)->setInc('commpubed', $totalpub);
        //个人发布
        if (!$setting['isverifytask']) {
            $upmoney = $data['costtop'] + $data['costserver'];
            $model_task::pubGiveParent($setting, $userinfo, $id, $upmoney);
            db('user')->where('id', $user_id)->setInc('pubnumber');
        }
        // 管理员通知
        // 新任务通知
        $this->success('发布成功', array('taskid' => $id));
    }

    //个人中心-个人发任务-我发的任务
    public function mypub()
    {
        $user_id = $this->getUserId();
        if (empty($user_id)) $this->error(['code' => 10001, 'msg' => $this->standard_code['10001']]);
        $userinfo = $this->getUserInfo();

        $p = $this->request->param('p');
        $p = max($p, 1);
        $pagesize = 10;
        $start = ($p - 1) * $pagesize;

        $type = $this->request->param('type');
        $search = $this->request->param('search');

        $where['userid'] = $userinfo['id'];
        if ($type == 1) {
            // 未开始
            $where['iscount'] = 0;
            $where['status|isstart'] = 1;
        } elseif ($type == 2) {
            // 进行中
            $where['status'] = 0;
            $where['iscount'] = 0;
            $where['start'] = array('lt', time());
        } elseif ($type == 3) {
            // 已结算
            $where['iscount'] = 1;
        }

        if (!empty($search)) {
            $where['title'] = array('like', "%{$search}%");
        }

        $select = 'id,title,money,scan,isempty,status,iscount,start,type,address';
        $data = db('self_task')->field($select)->where($where)->limit($start, $pagesize)->order('id', 'desc')->select()->toArray();

        if (empty($data)) {
            $this->success('暂无更多内容');
        }

        if (empty($userinfo['avatar'])) {
            $site_info = cmf_get_option('site_info');
            $userinfo['avatar'] = cmf_get_image_url($site_info['dhead']);
        } else {
            $userinfo['avatar'] = cmf_get_image_url($userinfo['avatar']);
        }
        if (empty($userinfo['user_nickname'])) {
            $userinfo['user_nickname'] = mask_mobile($userinfo['mobile']);
        }
        foreach ($data as $k => $v) {
            $data[$k]['avatar'] = $userinfo['avatar'];
            $data[$k]['user_nickname'] = $userinfo['user_nickname'];
            $data[$k]['title'] = htmlspecialchars_decode($v['title']);
            if ($v['iscount'] == 1) {
                $statusstr = '已结算';
                if ($v['status'] == 2) {
                    $statusstr = '未通过审核';
                }
            } else {
                if ($v['status'] == 0) {
                    if ($v['isempty'] == 1) {
                        $statusstr = '已被接完';
                    } else {
                        $statusstr = '任务进行中';
                    }
                } elseif ($v['status'] == 1) {
                    $statusstr = '审核中';

                } elseif ($v['status'] == 2) {
                    $statusstr = '未通过审核';
                }
                if ($v['start'] > time()) {
                    $statusstr = '还未开始';
                }
            }
            $data[$k]['statusstr'] = $statusstr;
        }
        $this->success('success', $data);
    }

    //个人中心-个人发任务-我接的任务
    public function myreply()
    {
        $user_id = $this->getUserId();
        if (empty($user_id)) $this->error(['code' => 10001, 'msg' => $this->standard_code['10001']]);
        $userinfo = $this->getUserInfo();
//        $user_id = 29;
//        $userinfo = db('user')->find($user_id);
        $p = $this->request->param('p');
        $p = max($p, 1);
        $pagesize = 10;
        $start = ($p - 1) * $pagesize;

        $type = $this->request->param('type');
        $search = $this->request->param('search');
        $where['a.userid'] = $userinfo['id'];

        if ($type == 1) {
            // 审核中
            $where['a.status'] = 0;
            $where['a.endtime'] = array('gt', time());
        } elseif ($type == 2) {
            // 进行中
            $where['a.status'] = 1;
        } elseif ($type == 3) {
            // 已采纳
            $where['a.status'] = 2;
        } elseif ($type == 4) {
            // 已拒绝
            $where['a.status'] = 3;
        }

        if (!empty($search)) {
            $where['b.title'] = array('like', "%{$search}%");
        }

        $site_setting = cmf_get_option('site_info');

        $select = 'b.id,b.orderid,b.order_aa,b.scan,b.title,b.userid,a.content,a.createtime,a.money,a.ewai,a.status,a.usetime,c.user_nickname,c.avatar';

        $data = db('self_task')->alias('b')
            ->join('__USER__ c', 'b.userid=c.id', 'left')
            ->join('__SELF_TASK_RECEIVE__ a', 'b.id=a.taskid', 'left')
            ->field($select)
            ->where($where)
            ->limit($start, $pagesize)->order('a.id', 'desc')->select()->toArray();
        if (empty($data)) {
            $this->success('暂无更多内容' . db('self_task')->getLastSql());
        }

        foreach ($data as $k => $v) {
            $data[$k]['timestr'] = \TbUtil::formatTime($v['createtime']);
            $data[$k]['title'] = htmlspecialchars_decode($v['title']);
            $data[$k]['content'] = htmlspecialchars_decode($v['content']);
            if (!empty($v['avatar'])) {
                $data[$k]['avatar'] = cmf_get_image_url($v['avatar']);
            } else {
                $data[$k]['avatar'] = cmf_get_image_url($site_setting['dhead']);
            }
            if (empty($v['user_nickname'])) {
                $data[$k]['user_nickname'] = $site_setting['site_name'];
            }
            $moneystr = '';
            $statusstr = '';
            if ($v['status'] == 0) {
                $statusstr = '待回复';
            } elseif ($v['status'] == 1) {
                $statusstr = '待采纳';
            } elseif ($v['status'] == 2) {
                $statusstr = '已采纳';
                $ewai = '';
                if ($v['ewai'] > 0) {
                    $ewai = ' + ' . $v['ewai'];
                }
                $moneystr = "{$v['money']}{$ewai}";
            } elseif ($v['status'] == 3) {
                $statusstr = '被拒绝';
            }
            $data[$k]['moneystr'] = $moneystr;
            $data[$k]['statusstr'] = $statusstr;
        }
        $this->success('success', $data);
    }

    //任务详情页
    public function taskDetail()
    {
        $extra = array();//额外参数，用来辅助页面状态显示

        $user_id = $this->getUserId();//当前登录的用户id，可能未登录
        if (empty($user_id)) $this->error(['code' => 10001, 'msg' => $this->standard_code['10001']]);
        $userinfo = $this->getUserInfo();

        $taskid = $this->request->param('taskid');

        $task = db('self_task')->where(array('id' => $taskid))->find();

        if (!$task) {
            $this->error('不存在此任务');
        }
        $model_task = new SelfTaskModel();
        $model_user = new UserModel();

        $site_info = cmf_get_option('site_info');
        $selftask_setting = cmf_get_option('selftask_setting');

        $canverify = 0;
        $pass = 0;
        if ($task['status'] == 1 || $task['status'] == 2) {
            if ($task['userid'] == $user_id) $pass = 1;
            $isadmin = $model_user::isAdmin($userinfo['id']);
            if ($isadmin) {
                $pass = 1;
                if ($task['status'] == 1) $canverify = 1;
            }
            if ($pass == 0) $this->error('此任务还在审核中');
        }

        if ($task['status'] == 2 && $pass == 0) message('此任务已下架');
        $extra['canverify'] = $canverify;

        //任务数据处理
        $task['title'] = htmlspecialchars_decode($task['title']);
        $task['content'] = $model_task::hideKey($selftask_setting['hidetxt'], htmlspecialchars_decode($task['content']));
        if (empty($task['link'])) {
            $task['link'] = array();
        } else {
            $task['link'] = json_decode($task['link'], true);
        }
        $task['images'] = json_decode($task['images'], true);
        $task['taked'] = db('self_task_receive')->where(array('taskid' => $task['id'], 'endtime' => array('gt', time())))->count();//已接
        if (!empty($task['images'])) {
            foreach ($task['images'] as $k => $v) {
                $task['images'][$k] = cmf_get_image_preview_url($v);
            }
        }

        // 发布者
        if (empty($task['userid'])) {
            $puber['user_nickname'] = $site_info['site_name'];
            $puber['id'] = 'admin';
            $puber['deposit'] = '0';
            $puber['avatar'] = cmf_get_image_url($site_info['dhead']);
            $puber['limitnum'] = 0;
        } else {
            $puber = db('user')->where('id', $task['userid'])->find();
            if (empty($puber['user_nickname'])) $puber['user_nickname'] = mask_mobile($puber['mobile']);
            if (empty($puber['avatar'])) {
                $puber['avatar'] = cmf_get_image_url($site_info['dhead']);
            } else {
                $puber['avatar'] = cmf_get_image_url($puber['avatar']);
            }
        }

        $extra['puber'] = $puber;//发布者信息
        $autotime = '';
        if ($task['end'] > time()) $autotime = $task['end'];
        if ($task['start'] > time()) $autotime = $task['start'];
        $extra['autotime'] = $autotime;

        // 是否已抢过
        $mystatus = $model_task::getStatusInTask($user_id, $task['id'], $task['num'], $task['userid'], $task['limitnum'], false);
        $myreply = array();
        if ($mystatus['status'] == 1) {
            $myreply = $mystatus['reply'];
        }
        $extra['mystatus'] = $mystatus;
        $extra['myreply'] = $myreply;
        // 是否抢过，用于显示图片
        $istaked = db('self_task_receive')->where(array('taskid' => $task['id'], 'userid' => $userinfo['id']))->find();
        // 任务剩余时间
        $usetime = $task['usetime'] * 60 * 60;
        $istaked['usetime'] = $istaked['createtime'] + $usetime;

        $extra['istaked'] = $istaked;


        $continuenum = array();
        if ($task['continue'] == 1) {
            $where = array('continueid' => $task['continueid']);
            $continuenum = db('self_task')->where($where)->count();
//            $continue = db('self_task_continue')->where(array('id' => $task['continueid']))->find();
        }
        $extra['continuenum'] = $continuenum ? $continuenum : 0;//多少条连续任务

        // 留言
        $messagenum = db('self_task_message')->where(array('taskid' => $task['id'], 'parent' => 0))->count();
        $extra['messagenum'] = $messagenum ? $messagenum : 0;


        //投诉功能是升级后的功能

        // 浏览量
//        db('self_task')->where('id', $task['id'])->setInc('scan');
        $pid = db('self_task_sort')->where('id', $task['sortid'])->value('pid');
        $user_info = db('user')->where('id', $user_id)->find();
        if ($pid) { // // 5.爆音套餐 4.KS 3.小书本 2.火S 1.KS悬赏
            if ($pid == 1) {
                $data['bind'] = $user_info['account1'] ? $user_info['account1'] : "";
            }
            if ($pid == 2) {
                $data['bind'] = $user_info['account2'] ? $user_info['account2'] : "";
            }
            if ($pid == 3) {
                $data['bind'] = $user_info['account3'] ? $user_info['account3'] : "";
            }
            if ($pid == 4) {
                $data['bind'] = $user_info['account4'] ? $user_info['account4'] : "";
            }
            if ($pid == 5) {
                $data['bind'] = $user_info['account5'] ? $user_info['account5'] : "";
            }
        }
        $data['task'] = $task;
        $data['chaolianjie'] = $task['chaolianjie'] ? $task['chaolianjie'] : '';
        $data['extra'] = $extra;

        $this->success('success', $data);
    }

    //任务留言列表
    public function messageList()
    {
        $taskid = input('taskid');
        $list = db('self_task_message')->alias('A')
            ->join('__USER__ B', 'A.userid=B.id', 'left')
            ->field('A.id mid,A.time,A.content,B.avatar,B.user_nickname,B.pubnumber')
            ->where(array('A.taskid' => $taskid, 'A.parent' => 0))->select()->toArray();
        if (empty($list)) {
            $this->success('没有更多数据', array());
        }
        foreach ($list as $k => $v) {
            $list[$k]['time'] = \TbUtil::formatTime($v['time']);
            $list[$k]['avatar'] = cmf_get_image_url($v['avatar']);
        }
        $this->success('success', $list);
    }

    //回复列表
    public function takedList()
    {
        $user_id = $this->getUserId();//当前登录的用户id，可能未登录
        if (empty($user_id)) $this->error(['code' => 10001, 'msg' => $this->standard_code['10001']]);
        $userinfo = $this->getUserInfo();
//        $user_id = 1170;
//        $userinfo = db("user")->find($user_id);
        $p = $this->request->param('p');
        $p = max($p, 1);
        $pagesize = 10;
        $start = ($p - 1) * $pagesize;

        $taskid = $this->request->param('taskid');
        if (!$taskid) {
            $this->error('请传入正确的参数');
        }
        $type = $this->request->param('type');

        $where['r.taskid'] = $taskid;

        if (!in_array($type, array(1, 2, 3))) {
            $where['r.status'] = array('gt', 0.1);
        }

        //status 0刚抢到还没回复 1已回复 2已采纳 3已被拒绝
        if ($type == 1) {
            //自己的已采纳或已被拒绝的回复
            $where['r.userid'] = $userinfo['id'];
            $where['r.status'] = array('gt', 1);
        }
        if ($type == 2) $where['r.status'] = 1;//已回复
        if ($type == 3) $where['r.status'] = 2;//已采纳
        if ($type == 4) $where['r.status'] = 3;//已被拒绝

        $select = 'r.*,g.status dealstatus,dealnote';

        $order = 'r.id DESC';
        $data = db('self_task_receive')->alias('r')
            ->join('self_task_receive_useragree g', 'r.taskid = g.taskid and r.userid = g.userid and r.id = g.replyid', 'left')
            ->field($select)
            ->where($where)
            ->order($order)
            ->select()->toArray();
//        dump($data);die;
        if (empty($data)) {
            $this->success('暂无更多回复');
        }
        $task = db('self_task')->where(array('id' => $where['r.taskid']))->find();
        $selftask_setting = cmf_get_option('selftask_setting');
        $site_info = cmf_get_option('site_info');
        $str = '';
//        dump($task);die;
        foreach ($data as $k => $v) {
            $time = \TbUtil::formatTime($v['replytime']);
            $user = db('user')->where('id', $v['userid'])->find();

            if (empty($user['user_nickname'])) $user['user_nickname'] = mask_mobile($user['mobile']);

            $nickname = $user['user_nickname'] . '<font class="userid">(' . $user['id'] . ')</font>';

            if (empty($user['avatar'])) {
                $user['avatar'] = cmf_get_image_url($site_info['dhead']);
            } else {
                $user['avatar'] = cmf_get_image_url($user['avatar']);
            }

            $img = '';
            if (!empty($v['images'])) $v['images'] = json_decode($v['images'], true);
            if (!empty($v['images']) && is_array($v['images'])) {
                foreach ($v['images'] as $vv) {
                    $img .= '<li class="need_show_images_item fl" onclick="openimg1.open()"><img src="' . cmf_get_image_url($vv) . '"></li>';
                }
            }

            $reason = '';
            if ($v['status'] == 3 && !empty($v['reason'])) {
                $reason = '<p class="font_mini">被拒理由：' . $v['reason'] . '</p>';
            }

            //发提醒内容
            $remindstr = '';
            if ($userinfo['id'] == $task['userid'] || $userinfo['id'] == $v['userid']) {
                $remind = db('self_task_remindlog')->where(array('takedid' => $v['id'], 'mtype' => 0))->select()->toArray();
                $showremindbtn = true;
                if (!empty($remind)) {
                    $remindstr = '<div class="remind_box">';
                    foreach ($remind as $r) {

                        if ($r['createtime'] > (time() - 10 * 60) && $r['type'] == 0) {
                            $showremindbtn = false;
                        }

                        $remindstr .= <<<div
						<div class="item_cell_box remind_item">
							<li class="remin_nick">提醒：</li>
							<li class="item_cell_flex">{$r['content']}</li>
						</div>
div;
                    }
                    $remindstr .= '</div>';
                }
            }

            // 补充内容
            $addcontentstr = '';
            if ($userinfo['id'] == $task['userid'] || $userinfo['id'] == $v['userid']) {
                $addlist = db('self_task_remindlog')->where(array('takedid' => $v['id'], 'type' => 1))->select()->toArray();
                $showaddcontentbtn = true;
                if (!empty($addlist)) {
                    $addcontentstr = '<div class="addcontent_box">';
                    foreach ($addlist as $r) {

                        if ($r['createtime'] > (time() - 0.1 * 60)) {
                            $showaddcontentbtn = false;
                        }
                        $addimg = '';
                        if (!empty($r['images'])) $r['images'] = json_decode($r['images'], true);
                        if (!empty($r['images']) && is_array($r['images'])) {
                            foreach ($r['images'] as $rr) {
                                $addimg .= '<li class="need_show_images_item fl" onclick="openimg1.open()"><img src="' . cmf_get_image_url($rr) . '"></li>';
                            }
                        }
                        $addcontentstr .= <<<div
						<div class="item_cell_box addcontent_item">
							<div class="remin_nick">补充：</div>
							<div class="item_cell_flex">
								<li>{$r['content']}</li>
								{$addimg}
							</div>
						</div>
div;
                    }
                    $addcontentstr .= '</div>';
                }
            }

//-----------------------------------------------------------------------------------------------------------------------------------
            if ($userinfo['id'] == $v['pubuid']) {
                $reason = '';
                if ($v['status'] == 3 && !empty($v['reason'])) {
                    $reason = '<p class="font_mini">被拒理由：' . $v['reason'] . '</p>';
                }
                $statusstr = '';
                if ($v['dealstatus'] == null) $statusstr = '<span class="task_replay_status">待采纳</span>';
                if ($v['dealstatus'] == 1) $statusstr = '<span class="task_replay_status font_green">已采纳</span>';
                if ($v['status'] == 3) $statusstr = '<span class="task_replay_status font_ff5f27">被拒绝</span>';

                $botstr = '<div class="task_replay_bottom item_cell_box" ><div>' . $statusstr . '</div></div>';

                if ($v['dealstatus'] == null && $userinfo['id'] == $v['userid'] && true) {
                    $botstr = '<div class="task_replay_bottom item_cell_box" ><div class="item_cell_flex"></div><div class="puber_deal_btn addcontent" reid="' . $v['id'] . '">补充内容</div></div>';
                }

                if ($v['status'] == 1 && $v['dealstatus'] == null && $userinfo['id'] == $task['userid']) { // 是发布者

                    $remindbtn = '<div class="puber_deal_btn remind" reid="' . $v['id'] . '">提醒</div>';

                    if (!$showremindbtn) $remindbtn = '';
                    $botstr = <<<div
						<div class="task_replay_bottom item_cell_box " >
							<div class="item_cell_flex"></div>
							{$remindbtn}
							<div class="puber_deal_btn agree" reid="{$v['id']}">采纳</div>
							<div class="puber_deal_btn refuse" reid="{$v['id']}">拒绝</div>
							<div class="puber_deal_check weui_cells_checkbox">
								<label class="weui_cell weui_check_label needsclick " >
									<div class="weui_cell_hd needsclick">
										<input type="checkbox" class="weui_check" name="reply[]" value="{$v['id']}" >
										<i class="weui_icon_checked"></i>
									</div>
									<div class="weui_cell_bd tl weui_cell_primary needsclick">
										<span class="form_tips needsclick">选择</span>
									</div>
								</label>						
							</div>
						</div>
div;
                }
            } else {
                $moneystr = '';
                if ($v['status'] == 2) {
                    $ewaistr = '';
                    if ($v['ewai'] > 0) $ewaistr = '+' . $v['ewai'];
                    $moneystr = '<span class="task_replay_in">' . $v['money'] . $ewaistr . '</span>';
                }
                $statusstr = '';
                if ($v['status'] == 1) $statusstr = '<span class="task_replay_status">待采纳</span>';
                if ($v['status'] == 2) $statusstr = '<span class="task_replay_status font_green">已采纳</span>';
                if ($v['status'] == 3) $statusstr = '<span class="task_replay_status font_ff5f27">被拒绝</span>';

                $botstr = '<div class="task_replay_bottom item_cell_box" ><div>' . $moneystr . $statusstr . '</div></div>';
                if ($v['status'] == 1 && $userinfo['id'] == $v['userid'] && true) {
                    $botstr = '<div class="task_replay_bottom item_cell_box" ><div class="item_cell_flex"></div><div class="puber_deal_btn addcontent" reid="' . $v['id'] . '">补充内容</div></div>';
                }

                if ($v['status'] == 1 && $userinfo['id'] == $task['userid']) { // 是发布者

                    $remindbtn = '<div class="puber_deal_btn remind" reid="' . $v['id'] . '">提醒</div>';

                    if (!$showremindbtn) $remindbtn = '';
                    $botstr = <<<div
						<div class="task_replay_bottom item_cell_box " >
							<div class="item_cell_flex"></div>
							{$remindbtn}
							<div class="puber_deal_btn agree" reid="{$v['id']}">采纳</div>
							<div class="puber_deal_btn refuse" reid="{$v['id']}">拒绝</div>
							<div class="puber_deal_check weui_cells_checkbox">
								<label class="weui_cell weui_check_label needsclick " >
									<div class="weui_cell_hd needsclick">
										<input type="checkbox" class="weui_check" name="reply[]" value="{$v['id']}" >
										<i class="weui_icon_checked"></i>
									</div>
									<div class="weui_cell_bd tl weui_cell_primary needsclick">
										<span class="form_tips needsclick">选择</span>
									</div>
								</label>						
							</div>
						</div>
div;
                }

            }

//------------------------------------------------------------------------------------------------------------------------------------------
            $contentstr = '';
            if ($task['ishide'] == 0 || $userinfo['id'] == $task['userid'] || $userinfo['id'] == $v['userid']) {

                $contentstr = '<div class="task_reply_title">' . $v['content'] . '</div><div class="need_show_images oh">' . $img . '</div>' . $addcontentstr . $reason . $remindstr;
            } else {
                $contentstr = '<li class="hide_tips">内容被隐藏</li>';
            }
            if ($v['isscan'] == 1) {
                $contentstr = '<li class="hide_tips">内容被禁</li>';
            }


            if (true && $task['isread'] == 1 && $userinfo['id'] == $task['userid']) {

                $hidestr = '<div class="puber_deal_btn hide" reid="' . $v['id'] . '">隐藏</div>';
                if ($v['isscan'] == 1) {
                    $hidestr = '<div class="puber_deal_btn show" reid="' . $v['id'] . '">显示</div>';
                }
                $botstr = '<div class="task_replay_bottom item_cell_box " >' . $hidestr . '</div>';
            }

            if ($userinfo['id'] == $task['userid'] || $userinfo['id'] == $v['userid']) {
                $str .= <<<div
<div class="task_reply_item"><div class="task_reply_in"><div class="item_cell_box"><div class="task_reply_headimg"><img src="{$user['avatar']}" ></div><div class="item_cell_flex task_content_body"><div class="oh"><span class="font_bold_name task_content_nick">{$nickname}</span> <span class="font_13px_999 fr">{$time}</span></div><div>{$contentstr}</div></div></div>{$botstr}</div></div>
div;
            }
        }
        $this->success('success', $str);
    }

    //接任务
    public function takeTask()
    {
        try {
            $user_id = $this->getUserId();
            if (empty($user_id)) $this->error(['code' => 10001, 'msg' => $this->standard_code['10001']]);
            $userinfo = $this->getUserInfo();

//            $user_id = 1510;
//            $userinfo = db("user")->find($user_id);
            $score_setting = cmf_get_option('score_seeting'); // 积分设置

            $setting = cmf_get_option('selftask_setting'); // maxip 每日非vip限制

            if ($userinfo['credit_score'] < $score_setting['minscore']) $this->error('您的信誉积分低于' . $score_setting['minscore'] . '分不能接取任务');

            $beginToday = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
            $endToday = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;

            $tid = intval(input('taskid'));
            $task = db('self_task')->where(array('id' => $tid))->find();
            if ($task['cl_is_back'] > 0) $this->error('任务已退单');

            if (empty($task)) $this->error('任务不存在');

            // 已经接取的次数
            $receive = db('self_task_receive')
                ->where('userid', $user_id)
                ->where('createtime', '>=', $beginToday)
                ->where('createtime', '<=', $endToday)
                ->count();

            if ($userinfo['credit_score'] < $score_setting['score']) {
                if ($receive >= $score_setting['receivenum']) $this->error("你的信誉积分低于" . $score_setting['score'] . "分,每日只能接取" . $score_setting['receivenum'] . '次任务');
            }

            if ($userinfo['vip_type'] == 1) {
                if ($receive >= $setting['maxip']) $this->error('普通用户每日只能接取' . $setting['maxip'] . "次任务");
            }

            //0可同时抢多个人 1完成已抢任务后才能抢下一个任务 2回复已抢任务后才能抢下一个任务
            $time = time();
            if ($setting['taketask'] == 1) {
                $istakedd = db('self_task_receive')->where(array('status' => array('in', array(0, 1)), 'endtime' => array('egt', $time), 'userid' => $userinfo['id']))->find();
                if (!empty($istakedd)) $this->error('你接的任务还没完成，请先完成已接的任务');
            }
            if ($setting['taketask'] == 2) {
                $istakedd = db('self_task_receive')->where(array('status' => 0, 'endtime' => array('egt', $time), 'userid' => $userinfo['id']))->find();
                if (!empty($istakedd)) $this->error('你接的任务还没提交，请先提交后已接的任务');
            }

            if ($task['oldnum'] <= 0) {
                $this->error('任务已被接完', $task['num']);
            }

            if ($task['status'] != 0) $this->error('任务未上架');
            if ($task['start'] > $time) $this->error('任务未开始');
            if ($task['end'] < $time) $this->error('任务已结束');
            if ($task['iscount'] == 1) $this->error('任务已结束');
            if ($task['ispause'] == 1) $this->error('任务已关闭');

            if ($task['sex'] == 1 && !in_array($userinfo['sex'], array('1', '3'))) $this->error('此任务仅限男性可接');
            if ($task['sex'] == 2 && !in_array($userinfo['sex'], array('2', '4'))) $this->error('此任务仅限女性可接');

            $model_task = new SelfTaskModel();
            $mystatus = $model_task::getStatusInTask($user_id, $task['id'], $task['num'], $task['userid'], $task['limitnum'], false);

            if ($mystatus['status'] == 1) $this->error('您已接了此任务还没完成，请完成了再接');
            if ($mystatus['status'] == 2) $this->error('您已经接了很多次了，不能再接了');
            if ($mystatus['status'] == 3) $this->error('您不能再接此用户发布的任务了');
            if ($mystatus['status'] == 4) $this->error('任务已被接完');
            if ($mystatus['totallast'] < 1) {
                //isempty 0没有被抢光 1被抢光了
                db('self_task')->where(array('id' => $task['id']))->update(array('isempty' => 1));
                $this->error('此任务已被接完');
            }

            $endtime = $setting['endtime'] > 0 ? $setting['endtime'] : 60;//抢到普通任务后释放时间
            $endtime = $endtime * 60;
            $replytime = $task['replytime'] * 60; // 等待回复时间

            $last = $task['end'] - $time;
            if ($last <= $replytime) { // 如果剩余时间小于设置的停留时间，停留时间设置为剩余的一半
                $replytime = $last / 2;
            }
            if ($last <= $endtime) { // 如果剩余时间小于设置的任务限时，任务限时
                $endtime = $last;
            }
            $usetime = $task['usetime'] * 60 * 60 + $time;


            Db::startTrans();
            $data = array(
                'userid' => $userinfo['id'],
                'taskid' => $task['id'],
                'continueid' => $task['continueid'],
                'createtime' => $time,
                'waittime' => $time + $replytime,
                'endtime' => $time + $endtime,
                'money' => $task['money'],
                'puber' => $task['puber'],
                'pubuid' => $task['userid'],
                'ip' => get_client_ip(),
                'usetime' => $usetime,
                'orderid' => $task['orderid'] ?? '', //业务订单id
                'order_aa' => $task['order_aa'] ?? '' //业务订单视频id
            );
            $res = db('self_task_receive')->insertGetId($data);
            if ($res) {
                // 从林订单接取+1
//                Db::query("update mc_clorder_log set receive_num = receive_num + 1 where cl_orderid =" . $task['orderid'] . "and cl_order_num > receive_num");
                if ($task['orderid'] && $task['order_aa']) {  //从林订单id和下单地址同时存在为从林任务
                    $cl_receive = db('clorder_log')->where(['cl_orderid' => $task['orderid'], 'cl_order_aa' => $task['order_aa']])->find();
                    if ($cl_receive['receive_num'] + 1 < $cl_receive['cl_order_num']) {
                        db('clorder_log')->where(['cl_orderid' => $task['orderid'], 'cl_order_aa' => $task['order_aa']])->setInc('receive_num');
                    }
                }

                $res1 = db('self_task')->where(['id' => $tid])->whereExp('oldnum', '>0')->setDec('oldnum');

                if (empty($res1) || $res1 == 0) {
                    //isempty 0没有被抢光 1被抢光了
                    throw new Exception('库存不足，请更换任务');
                }
                if ($task['sortid'] == 9){
                    // 从林回传执行
                    if ($task['orderid'] && $task['order_aa']) self::clNum($task);
                }

                Db::commit();
                $this->success('您已接到任务，请在规定的时间内完成并回复，否则任务失败',$task['chaolianjie']);
            } else {
                throw new Exception('任务接取失败');
            }
        } catch (Exception $exception) {
            DB::rollback();
            $this->error($exception->getMessage());
        }
    }

    //接任务的人的回复
    public function takeReply()
    {
        try {
            $taskid = input('taskid');
            $user_id = $this->getUserId();
            if (empty($user_id)) throw new Exception("用户id为空");
            $userinfo = $this->getUserInfo();

            $data['content'] = input('content');
            $data['app_nickname'] = input('app_nickname');
            $images = input('images');
            if (empty($images)) throw new Exception("图片不能为空");
            if (!empty($images)) {
                $images = explode(',', trim($images, ','));
                foreach ($images as $k => $v) {
                    $images[$k] = cmf_asset_relative_url($v);
                }
            }
            $data['images'] = !empty(input('images')) ? json_encode($images) : '';
            $data['is_qiniu'] = 0;
            $uploadSetting = cmf_get_upload_setting();
            if (!empty($uploadSetting) && !empty($uploadSetting['file_types']['file']['accessKey'])) {
                $data['is_qiniu'] = 1;
            }
            $data['replytime'] = time();
            $data['status'] = 1;

            $task = db('self_task')->where(array('id' => $taskid))->find();

//        if (empty($data['content'])) $this->error('请填写回复的内容');
            if (empty($task)) throw new Exception('任务不存在');
            if ($task['status'] != 0) throw new Exception('任务未上架');
            if ($task['start'] > time()) throw new Exception('任务未开始');
            if ($task['end'] < time()) throw new Exception('任务已结束');
            if ($task['iscount'] == 1) throw new Exception('任务已结束');

            $taked = db('self_task_receive')->where(array('taskid' => $task['id'], 'userid' => $userinfo['id'], 'status' => 0))->find();
            if (empty($taked)) throw new Exception('您还没接到此任务');
            if ($taked['endtime'] < time()) throw new Exception('请刷新页面重新接任务');
            if ($taked['waittime'] > time()) throw new Exception('还没到可回复任务的时间');

            $data['endtime'] = $task['end'] + 10 * 365 * 24 * 60 * 60;
            Db::startTrans();
            $res = db('self_task_receive')->where(array('id' => $taked['id']))->update($data);

            if ($res) {
                // 给雇主发消息
                if ($task['userid']) {
                    $model_news = new NewsModel();
                    $news = "用户{$userinfo['user_nickname']}回复了您的任务{$task['id']}";
                    $model_news::toUserNews($task['userid'], $news);
                }
                if ($task['sortid'] == 9){
                    // 从林回传执行
                    if ($task['orderid'] && $task['order_aa']) self::clNum($task);
                }
                Db::commit();
                $this->success('您已成功回复任务，请等待雇主处理您的回复');
            } else {
                throw new Exception('回复失败');
            }

        } catch (Exception $exception) {
            Db::rollback();
            $this->error('系统异常：' . $exception->getMessage());
        }
    }


    // 从林回传执行
    static function clNum($task)
    {
//        $num = 0;
//        $num = db('self_task_receive')->where('taskid', $task['id'])->where('status', 'in', [1, 2])->count();
//
//        $start_num = db("self_task")->where(['id'=>$task['id']])->value('start_num');
//        $num = $num + $start_num;
        if (strlen($task['order_aa']) == 19){
            $nowdata = DouyinUrlController::RmD($task['order_aa']);
        }

        if (strlen($task['order_aa']) > 19){
            $aa = DouyinUrlController::aa($task['order_aa']);
            $nowdata = DouyinUrlController::RmD($aa);
        }
        $nowdata = json_decode($nowdata,true);
        $num = $nowdata['zan'];
        if (($num - $task['start_num']) > $task['need_num']){
            self::overorder($task);
        }
        
        // $url = "http://by.conglin178.com/admin_jiuwuxiaohun.php?m=home&c=api&a=edit&goods_id=" . $task['account_id'] . "&order_id=" . $task['orderid'] . "&now_num=" . $num . "&apikey=" . $task['api_key'];
        $url = "http://by.conglin178.com/admin_jiuwuxiaohun.php?m=home&c=api&a=edit&goods_id=" . $task['account_id'] . "&order_state=jxz&order_id=" . $task['orderid'] . "&apikey=" . $task['api_key'] . "&now_num=" . $num;


        $clurllog['orderid'] = $task['orderid'];
        $clurllog['order_aa'] = $task['order_aa'];
        $clurllog['num'] = $num;
        $clurllog['url'] = $url;
        $clurllog['create_time'] = time();
        db('cl_url_log')->insert($clurllog);

        self::http_curl($url);
    }

    static function overorder($task)
    {
        $orderid = $task['orderid'];
        $order_aa = $task['order_aa'];

        if (strlen($order_aa) == 19){
            $nowdata = DouyinUrlController::RmD($order_aa);
        }

        if (strlen($order_aa) > 19){
            $aa = DouyinUrlController::aa($order_aa);
            $nowdata = DouyinUrlController::RmD($aa);
        }
        $nowdata = json_decode($nowdata,true);
        $now_num = $nowdata['zan'];

//        $url = "http://by.conglin178.com/admin_jiuwuxiaohun.php?m=Admin&c=Api&a=refund&apikey=" . $task['api_key'] . "&order_id=" . $orderid . "&order_state=ytd" . "&now_num=" . $now_num;

        $url = "http://by.conglin178.com/admin_jiuwuxiaohun.php?m=home&c=api&a=edit&goods_id=" . $task['account_id'] . "&order_state=ytd&order_id=" . $orderid . "&apikey=" . $task['api_key'] . "&now_num=" . $now_num;

        db('self_task')->where('orderid',$orderid)->setField('end_num',$now_num);  //修改任务结束量

        $res = self::http_curl($url);
        if ($res) {
            // 访问接口日志
            $clurllog['orderid'] = $orderid;
            $clurllog['order_aa'] = $order_aa;
            $clurllog['num'] = $now_num;
            $clurllog['url'] = $url;
            $clurllog['create_time'] = time();
            db('cl_url_log')->insert($clurllog);
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


    public function addContent()
    {
        $user_id = $this->getUserId();
        if (empty($user_id)) $this->error(['code' => 10001, 'msg' => $this->standard_code['10001']]);
        $userinfo = $this->getUserInfo();

        $takedid = $this->request->param('takedid');
        $content = $this->request->param('content');
        if (empty($content)) $this->error('请输入回复内容');
        $taked = db('self_task_receive')->where('id', $takedid)->find();
        if (empty($taked)) {
            $this->error('没有此回复');
        }
        $task = db('self_task')->where('id', $taked['taskid'])->find();
        if (empty($task)) $this->error('没有此任务');
        $addnum = db('self_task_useraddcontent')->where(array('takedid' => $taked['id'], 'type' => 0, 'createtime' => array('gt', time() - 60 * 10)))->count();
        if ($addnum > 0) $this->error('每10分钟只能补充一次，最近10分钟您已补充过，稍等会再补充');

        $images = $this->request->param('images');
        if (!empty($images)) {
            $arr = explode(',', trim($images, ','));
            $imgstr = json_encode($arr);
        } else {
            $imgstr = '';
        }
        $data = array(
            'takedid' => $taked['id'],
            'taskid' => $task['id'],
            'content' => $content,
            'img' => $imgstr,
            'createtime' => time(),
        );
        $res = db('self_task_useraddcontent')->insert($data);
        if ($res) {
            // 发消息
            // 给雇主发消息
            if ($task['userid']) {
                $model_news = new NewsModel();
                $news = "用户{$userinfo['user_nickname']}对任务{$task['id']}添加了回复";
                $model_news::toUserNews($task['userid'], $news);
            }
            $this->success('添加成功,请等待商家处理');
        }
        $this->error('回复失败，请稍后重试');
    }

    //留言
    public function pubmessage()
    {
        $user_id = $this->getUserId();
        if (empty($user_id)) $this->error(['code' => 10001, 'msg' => $this->standard_code['10001']]);
        $userinfo = $this->getUserInfo();

        $taskid = $this->request->param('taskid');
        $message = $this->request->param('message');
        if (empty($message)) {
            $this->error('请输入提问内容');
        }
        $data['taskid'] = intval($taskid);
        $data['content'] = $message;

        $type = 0;//普通任务
        $task = db('self_task')->where(array('id' => $data['taskid']))->find();

        if (empty($task)) $this->error('任务不存在，不能再留言');
        if ($task['status'] != 0) $this->error('任务未上架，不能再留言');
        if ($task['end'] < time()) $this->error('任务已结束，不能再留言');
        if ($task['iscount'] == 1) $this->error('任务已结束，不能再留言');

        $where = array('userid' => $userinfo['id'], 'taskid' => $task['id'], 'type' => $type);
        $renum = db('self_task_message')->where($where)->count();
        if ($renum >= 5) $this->error('您已经留言很多次了，不能再留言');

        // 是否已接到
        $istaked = db('self_task_receive')->where(array('taskid' => $task['id'], 'userid' => $userinfo['id']))->find();
        if (empty($istaked)) $this->error('您还没接到任务，不能留言');

        $data['userid'] = $userinfo['id'];
        $data['time'] = time();
        $data['type'] = $type;
        if (empty($task['userid'])) { // 管理员发布的
            $data['isadmin'] = 1;
        }

        $res = db('self_task_message')->insert($data);
        if ($res) {
            // 给非系统发布者发消息
            if ($task['userid']) {
                $model_news = new NewsModel();
                $news = "有人给您的任务ID{$task['id']}留言了";
                $model_news::toUserNews($task['userid'], $news);
            }
            $this->success('留言成功');
        }
        $this->error('留言失败');
    }

    //回复留言/雇主回复接任务的人的回复  mid   content
    public function replyMessage()
    {
        $user_id = $this->getUserId();
        if (empty($user_id)) $this->error(['code' => 10001, 'msg' => $this->standard_code['10001']]);
        $userinfo = $this->getUserInfo();

        $data['parent'] = intval(input('mid'));
        $data['content'] = input('content');

        if (empty($data['content'])) $this->error('请输入回复内容');

        $parent = db('self_task_message')->where(array('id' => $data['parent']))->find();
        if (empty($parent)) $this->error('请重试');

        $task = db('self_task')->where(array('id' => $parent['taskid']))->find();

        if (empty($task)) $this->error('任务不存在，不能再回复');
        if ($task['status'] != 0) $this->error('任务未上架，不能再回复');
        if ($task['end'] < time()) $this->error('任务已结束，不能再回复');
        if ($task['iscount'] == 1) $this->error('任务已结束，不能再回复');
        if ($task['userid'] != $userinfo['id']) $this->error('您不能回复此留言');

        $data['userid'] = $userinfo['id'];
        $data['taskid'] = $task['id'];
        $data['time'] = time();
        $data['type'] = $parent['type'];

        $res = db('self_task_message')->insert($data);

        if ($res) {
            // 给接任务的人发消息
            $model_news = new NewsModel();
            $news = "雇主{$userinfo['user_nickname']}回复了您的留言";
            $model_news::toUserNews($parent['userid'], $news);
            $this->success('回复成功');
        }
        $this->error('回复失败');
    }

    /**
     * 采纳回复
     * 发布者采纳回复  不做加钱操作只修改处理状态
     */
    public function agreeTaked()
    {
        $user_id = $this->getUserId();
        if (empty($user_id)) $this->error(['code' => 10001, 'msg' => $this->standard_code['10001']]);
        $userinfo = $this->getUserInfo();
//        $user_id = 16;
//        $userinfo = db("user")->find('16');

        $ids = input('ids');

        if (empty($ids)) $this->error('请先选择要采纳的任务');
        if (false !== stripos($ids, ',')) {
            $ids = explode(',', trim($ids, ','));
        } else {
            $ids = array(trim($ids, ','));
        }
        $model_task = new SelfTaskModel();
        $success = 0;
        $setting = cmf_get_option('selftask_setting');

        foreach ($ids as $v) {
            $task = db('self_task_receive')->alias("a")
                ->join("self_task b", " b.id=a.taskid")
                ->fieldRaw("a.*,b.continueid,b.continue ,b.id AS sid,a.status as sstatus,b.status as bstatus,b.start,b.end,a.taskid,b.userid buserid")
                ->where(array('a.id' => $v, 'a.status' => 1))
                ->find();
//            dump($task);die;
            if (empty($task)) continue;
//            $taskagree = db('self_task_receive_useragree')->where('taskid',$task['taskid'])->where('userid',$task['userid'])->select();
//
//            if (!empty($taskagree)) $this->error('此回复已处理过了');
            if ($task['buserid'] != $userinfo['id']) $this->error('您不是任务的发布者');
            if ($task['bstatus'] != 0) $this->error('任务未上架');
            if ($task['start'] > time()) $this->error('任务未开始');
            if ($task['end'] < time()) $this->error('任务已结束');
            $dealstatus = 1;
            $res = $model_task::userAgree($dealstatus, $task);

            if ($res) $success++;
        }
        if ($success > 0) {
            $this->success('成功采纳' . $success . '项回复');
        }
        $this->error('处理失败');
    }

    //拒绝任务
    public function refuseTaked()
    {
        $user_id = $this->getUserId();
        if (empty($user_id)) $this->error(['code' => 10001, 'msg' => $this->standard_code['10001']]);
        $userinfo = $this->getUserInfo();

        $ids = input('ids');
        if (empty($ids)) $this->error('请先选择要拒绝的任务');
        if (false !== stripos($ids, ',')) {
            $ids = explode(',', trim($ids, ','));
        } else {
            $ids = array(trim($ids, ','));
        }

        $model_task = new SelfTaskModel();
        $reason = input('reason');

        if (empty($reason)) $this->error('请输入拒绝理由');

        $success = 0;
        foreach ($ids as $v) {
            $taked = db('self_task_receive')->where(array('id' => $v, 'status' => 1))->find();
            if (empty($taked)) continue;
            $task = db('self_task')->where(array('id' => $taked['taskid']))->find();
            if (empty($taked)) continue;
            if ($task['userid'] != $userinfo['id']) $this->error('您不是任务的发布者');
            if ($task['status'] != 0) $this->error('任务未上架');
            if ($task['start'] > time()) $this->error('任务未开始');
            if ($task['end'] < time()) $this->error('任务已结束');
            $res = $model_task::refuseTask($taked, $reason, $task, $userinfo['user_nickname']);
            if ($res) $success++;
        }

        if ($success > 0) {
            // 平台发布量
            $this->success('成功拒绝' . $success . '项回复');
        }
        $this->error('处理失败');
    }

    //显示或隐藏任务
    public function showOrHideTaked()
    {
        $user_id = $this->getUserId();
        if (empty($user_id)) $this->error(['code' => 10001, 'msg' => $this->standard_code['10001']]);
        $userinfo = $this->getUserInfo();

        $ids = input('ids');
        $show = input('show') ? 1 : 0;
        if (empty($ids)) $this->error('请先选择任务');
        if (false !== stripos($ids, ',')) {
            $ids = explode(',', trim($ids, ','));
        } else {
            $ids = array(trim($ids, ','));
        }
        $id = intval($ids[0]);
        if (empty($id)) $this->error('请先选择任务');

        $taked = db('self_task_receive')->where(array('id' => $id))->find();
        if (empty($taked)) $this->error('没有找到回复');

        $task = db('self_task')->where(array('id' => $taked['taskid']))->find();
        if (empty($taked)) $this->error('没有找到任务');

        if ($task['userid'] != $userinfo['id']) $this->error('您不是任务的发布者');

        if ($task['status'] != 0) $this->error('任务未上架');

        $isscan = !$show;
        $show ? $nstr = '显示' : $nstr = '隐藏';
        $res = db('self_task_receive')->where(array('id' => $id))->update(array('isscan' => $isscan));
        if ($res !== false) {
            $this->success('已' . $nstr);
        }
        $this->error($nstr . '失败，请稍后重试');
    }

    //提醒
    public function remindTaked()
    {
        $user_id = $this->getUserId();
        if (empty($user_id)) $this->error(['code' => 10001, 'msg' => $this->standard_code['10001']]);
        $userinfo = $this->getUserInfo();

        $ids = input('ids');
        if (empty($ids)) $this->error('请先选择任务');
        if (false !== stripos($ids, ',')) {
            $ids = explode(',', trim($ids, ','));
        } else {
            $ids = array(trim($ids, ','));
        }

        $id = intval($ids[0]);
        $content = input('content');
        if (empty($id)) $this->error('请先选择任务');
        if (empty($content)) $this->error('请输入内容');

        $taked = db('self_task_receive')->where(array('id' => $id, 'status' => 1))->find();
        if (empty($taked)) $this->error('没有找到回复');

        $task = db('self_task')->where(array('id' => $taked['taskid']))->find();
        if (empty($taked)) $this->error('没有找到任务');

        if ($task['userid'] != $userinfo['id']) $this->error('您不是任务的发布者');

        if ($task['status'] != 0) $this->error('任务未上架');
        if ($task['start'] > time()) $this->error('任务未开始');
        if ($task['end'] < time()) $this->error('任务已结束');

        $sended = db('self_task_remindlog')->where(array('takedid' => $taked['id'], 'type' => 0, 'mtype' => 0, 'createtime' => array('gt', time() - 60 * 10)))->count();
        if ($sended > 0) $this->error('你已发过提醒，过段时间才能再提醒（10分钟内只能提醒对方1次）');

        $model_news = new NewsModel();
        $news = "雇主{$userinfo['user_nickname']}提醒您注意任务：{$task['title']}。\"{$content}\"";
        $model_news::toUserNews($task['userid'], $news);

        $data = array(
            'takedid' => $taked['id'],
            'createtime' => time(),
            'content' => $content,
        );
        db('self_task_remindlog')->insert($data);

        $this->success('已发送提醒');
    }

    //开启或关闭 0关闭 1开启
    public function pauseTaked()
    {
        $user_id = $this->getUserId();
        if (empty($user_id)) $this->error(['code' => 10001, 'msg' => $this->standard_code['10001']]);
        $userinfo = $this->getUserInfo();

        $id = intval(input('taskid'));
        $type = input('type');
        $task = db('self_task')->where(array('id' => $id))->find();

        if (empty($task)) $this->error('任务不存在');
        if ($task['status'] != 0) $this->error('任务未上架');
        if ($task['start'] > time()) $this->error('任务未开始');
        if ($task['iscount'] == 1) $this->error('任务已结算过了');

        if ($task['userid'] != $userinfo['id']) $this->error('您不能操作此任务');

        $type = empty($type) ? 1 : 0;

        if ($type == 0 && $task['ispause'] == 1) $this->error('任务已在关闭中');
        if ($type == 1 && $task['ispause'] == 0) $this->error('任务已在开启中');
//        dump($type);exit;
        $res = db('self_task')->where(array('id' => $task['id']))->setField(array('ispause' => $type));
        if (!$res) {
            $this->error('操作失败，请稍后重试1');
        }
        $this->success('操作成功');
    }

    //结算任务
    public function countTask()
    {
        $user_id = $this->getUserId();
        if (empty($user_id)) $this->error(['code' => 10001, 'msg' => $this->standard_code['10001']]);
        $userinfo = $this->getUserInfo();

        $id = intval(input('taskid'));
        $model_task = new SelfTaskModel();
        $task = db('self_task')->where(array('id' => $id))->find();

        if (empty($task)) $this->error('任务不存在');
        if ($task['status'] != 0) $this->error('任务未上架');
        if ($task['start'] > time()) $this->error('任务未开始');
        if ($task['iscount'] == 1) $this->error('任务已结算过了');

        if ($task['userid'] != $userinfo['id']) $this->error('您不能结算此任务');
        if (!$this->checkTaskIs($id)) {
            $counting = \TbUtil::getCache('counttask', $task['id']);
            if (is_array($counting) && $counting['status'] == 1) {
                $this->success('此任务正在被处理中，请重试');
            }
            \TbUtil::setCache('counttask', $task['id'], array('status' => 1));

            $res = $model_task::countTask($task);

            \TbUtil::deleteCache('counttask', $task['id']);
            if ($res) $this->success('成功结算任务');
            $this->error('结算失败');
        } else {
            $this->error('有未处理回复，请处理后进行结算');
        }
    }

    /**
     * 校验是否有未处理回复
     * @param 传入参数1
     * @param 传入参数2
     * @return 返回格式json
     */
    public function checkTaskIs($taskid)
    {
        $res = Db::name('self_task_receive')->where(['taskid' => $taskid, 'status' => 1])->count();
        return $res > 0 ? true : false;
    }

    //追加任务
    public function subaddtask()
    {
        die;
        $user_id = $this->getUserId();
        if (empty($user_id)) $this->error(['code' => 10001, 'msg' => $this->standard_code['10001']]);
        $userinfo = $this->getUserInfo();

        $id = intval(input('taskid'));
        $task = db('self_task')->where(array('id' => $id))->find();

        if (empty($task)) $this->error('任务不存在');
        if ($task['status'] != 0) $this->error('任务未上架');
        if ($task['start'] > time()) $this->error('任务未开始');
        if ($task['end'] < time()) $this->error('任务已结束');
        if ($task['iscount'] == 1) $this->error('任务已结算过了');

        if ($task['userid'] != $userinfo['id']) $this->error('您不能操作此任务');

        $num = intval(input('num'));
        if ($num <= 0) $this->error('追加数量必须是大于0的整数');

        $money = $task['money'] * $num;

        $setting = cmf_get_option('selftask_setting');

        $server = $setting['commonserver'] * $money / 100;

        $ewai = 0;
        if ($task['continue'] == 1) {
            $continue = db('self_task_continue')->where(array('id' => $task['continueid']))->find();
            $ewai = $continue['money'] * $num;
        }
        $total = $money + $server + $ewai;
        if ($total <= 0) $this->error('请刷新页面重试');

        $credit = db('user')->find($userinfo['id']);
        if ($credit['user_money'] < $total) {
            $this->error(['code' => 10004, 'msg' => '您的余额不足']);
        }

        // 扣钱
        $res = db('user')->where('id', $userinfo['id'])->setDec('user_money', $total);
        if ($res) {
            $newInfo = $this->getUserInfo();
            // 资金记录
            $moneylog['user_id'] = $task['userid'];
            $moneylog['tid'] = $task['id'];
            $moneylog['rid'] = 0;
            $moneylog['agentId'] = $userinfo['agentId'];
            $moneylog['create_time'] = time();
            $moneylog['score'] = 0;//更改积分
            $moneylog['coin'] = 0 - $total;//更改金额
            $moneylog['type'] = 1;//支出
            $moneylog['status'] = 0;//金额
            $moneylog['notes'] = '追加任务ID' . $task['id'];
            $moneylog['user_money'] = $newInfo['user_money'];  // 变更后的余额
            $moneylog['channel'] = 14; // 发任务扣钱
            db('user_money_log')->insert($moneylog);
            db('self_task')->where(array('id' => $task['id']))->update(array('isempty' => 0, 'num' => $num + $task['num'], 'oldnum' => $num + $task['oldnum']));

            if ($task['continue'] == 1) {
                db('self_task_continue')->where('id', $task['continueid'])->update(array('totalmoney' => $ewai, 'totalnum' => $num));
            }

            $this->success('追加成功', array('taskid' => $id));
        }
        $this->error('发布失败');
    }

    //审核任务 type 1通过 2驳回
    public function verifytask()
    {
        $user_id = $this->getUserId();
        if (empty($user_id)) $this->error(['code' => 10001, 'msg' => $this->standard_code['10001']]);
        $userinfo = $this->getUserInfo();

        $type = input('type');
        $taskid = intval(input('taskid'));
        $data['closereason'] = input('closereason');

        $task = db('self_task')->where(array('id' => $taskid))->find();
        if (empty($task)) $this->error('任务不存在，不能审核');
        if ($task['status'] != 1) $this->error('任务不能审核');
        if ($task['end'] < time()) $this->error('任务已结束，不能再审核');
        if ($task['iscount'] == 1) $this->error('任务已结束，不能再审核');

        if ($userinfo['user_level'] > 1) $this->error('任务不能被您审核');

        $setting = cmf_get_option('selftask_setting');
        $model_task = new SelfTaskModel();
        // 通过
        if ($type == 1) {
            $data['status'] = 0;
            if ($task['continueid'] > 0) {

                $all = db('self_task')->where(array('continueid' => $task['continueid'], 'status' => 1))->select()->toArray();
                foreach ($all as $v) {
                    $res = db('self_task')->where(array('id' => $v['id']))->update($data);

                    if ($res && $task['type'] == 0) { // 给上级奖励
                        $user = db('user')->find($v['userid']);
                        $upmoney = $v['costtop'] + $v['costserver'];
                        $model_task::pubGiveParent($setting, $user, $v['id'], $upmoney);
                    }
                }

            } else {
                $res = pdo_update('self_task', $data, array('id' => $task['id']));
                if ($res) { // 给上级奖励
                    $user = db('user')->find($task['userid']);
                    $upmoney = $task['costtop'] + $task['costserver'];
                    $model_task::pubGiveParent($setting, $user, $task['id'], $upmoney);
                }

            }
        }

        // 不通过
        if ($type == 2) {

            set_time_limit(0);
            $isback = empty($setting['isbacktm']) ? false : true;

            if ($task['continueid'] > 0) {

                $all = db('self_task')->where(array('continueid' => $task['continueid'], 'status' => 1))->select()->toArray();

                foreach ($all as $v) {
                    $res = $model_task::countTask($v, $isback);
                    if ($res) {
                        pdo_update('self_task', array('status' => 0), array('id' => $v['id']));
                    }
                }
            } else {
                $res = $model_task::countTask($task, $isback);
                if ($res) {
                    db('self_task')->where(array('id' => $task['id']))->update(array('status' => 0));
                }
            }
        }


        if ($res) {
            // 发消息
            $model_news = new NewsModel();
            if ($type == 1) {
                $remind = '审核通过';
            } else if ($type == 2) {
                $remind = '审核驳回';
            }
            $news = "您的任务已{$remind}。{$data['closereason']}";
            $model_news::toUserNews($task['userid'], $news);
            $this->success('审核成功');
        }
        $this->error('审核失败');
    }

    //恢复任务
    public function restartTask()
    {
        $user_id = $this->getUserId();
        if (empty($user_id)) $this->error(['code' => 10001, 'msg' => $this->standard_code['10001']]);
        $userinfo = $this->getUserInfo();

        $setting = cmf_get_option('selftask_setting');
        if ($setting['restart'] != 1) $this->error('恢复功能已关闭');

        $taskid = intval(input('taskid'));
        $task = db('self_task')->where(array('id' => $taskid, 'userid' => $userinfo['id']))->find();
        if (empty($task)) $this->error('未找到任务');

        if ($task['iscount'] != 1) $this->error('任务未结算，不可恢复');

        if ($task['backmoney'] > 0) {

            $credit = $userinfo;
            if ($credit['user_money'] < $task['backmoney']) {
                $this->error(['code' => 10004, 'msg' => '恢复需要扣除' . $task['backmoney'] . '资金，你的资金不够，请先充值', 'data' => array('money' => $task['backmoney'])]);
            }

            // 扣钱
            $res = db('user')->where('id', $credit['id'])->setDec('user_money', $task['backmoney']);
            if ($res) {
                $newInfo = $this->getUserInfo();
                // 资金记录
                $moneylog['user_id'] = $task['userid'];
                $moneylog['tid'] = $task['id'];
                $moneylog['rid'] = 0;
                $moneylog['agentId'] = $userinfo['agentId'];
                $moneylog['create_time'] = time();
                $moneylog['score'] = 0;//更改积分
                $moneylog['coin'] = 0 - $task['backmoney'];//更改金额
                $moneylog['notes'] = '恢复任务ID' . $task['id'];
                $moneylog['user_money'] = $newInfo['user_money']; //变更后的余额
                $moneylog['channel'] = 14;//发任务扣钱
                db('user_money_log')->insert($moneylog);
            } else {
                $this->error('恢复失败');
            }
        }

        $setting['autoconfirm'] = empty($setting['autoconfirm']) ? 24 : $setting['autoconfirm'];//发布的任务有效时间
        $end = time() + $setting['autoconfirm'] * 3600;
        $res = db('self_task')->where(array('id' => $task['id']))->update(array('iscount' => 0, 'end' => $end, 'backmoney' => 0));
        if ($res) {
            $this->success('已恢复');
        } else {
            $this->error('恢复失败');
        }
    }

    public function sort_task()
    {
        $p = $this->request->param('p');
        $p = max($p, 1);
        $pagesize = 10;
        $start = ($p - 1) * $pagesize;
        $sort_id = $this->request->param('sortid');
        $user_id = $this->getUserId();

        $text = $this->request->param('text');//任务标题关键词
        $type = $this->request->param('type');//分类ID
        $sorts_id = [];
        if ($sort_id) {
            $sorts = db("self_task_sort")->where('pid', $sort_id)->select();
            foreach ($sorts as $v) {
                $sorts_id[] = $v['id'];
            }
        }

        array_push($sorts_id, $sort_id);
        $where['s.cl_is_back'] = 0;
        $where['s.iscount'] = 0;
        $time = time();
        $where['s.status'] = 0;
        $where['s.type'] = 0;
        $where['s.sortid'] = ['in', $sorts_id];
        $where['s.end'] = ['>', $time];
        $where['s.oldnum'] = ['>', 0];
        if (!empty($type)) {
            $where['sortid'] = intval($type);
        }
        if (!empty($text)) {
            $where['title'] = array('like', "%{$text}%");
        }

        $receive_taskids = db('self_task_receive')->where('userid', $user_id)->column('taskid');
        if ($receive_taskids) $where['s.id'] = ['not in', $receive_taskids];

        $select = 's.id,s.sort_img,s.title,s.money,s.scan,s.oldnum,s.isempty,s.istop,s.start,s.puber,s.num,s.limitnum,s.iscount,s.ispause,s.userid,s.address,c.name as c_name';
        $order = 's.iscount ASC,s.isstart ASC,s.isempty ASC,s.istop DESC,s.id DESC ';
        $data = Db::name('self_task')->alias('s')
            ->join('self_task_sort c', 's.sortid = c.id', 'left')
            ->where($where)
            ->field($select)
            ->limit($start, $pagesize)
            ->order($order)
            ->select()
            ->toArray();

        if (empty($data)) {
            $this->success('暂无更多数据');
        }

        $setting = cmf_get_option('selftask_setting');
        $site_setting = cmf_get_option('site_info');
        $model_task = new SelfTaskModel();
        $alldata = [];
        foreach ($data as $k => $v) {
            // 屏蔽关键词
            $data[$k]['title'] = $model_task::hideKey($setting['hidetxt'], $data[$k]['title']);

            // 分类头像
            $data[$k]['sort_img'] = isset($v['sort_img']) ? 'http://' . $_SERVER['HTTP_HOST'] . '/upload/' . $v['sort_img'] : '';

            $user = array();
            if (!empty($v['userid'])) {
                $user = db('user')->find($v['userid']);
                if (empty($user['avatar'])) {
                    $data[$k]['avatar'] = cmf_get_image_url($site_setting['dhead']);
                } else {
                    $data[$k]['avatar'] = cmf_get_image_url($user['avatar']);
                }
                if (empty($user['user_nickname'])) {
                    $data[$k]['user_nickname'] = mask_mobile($user['mobile']);
                } else {
                    $data[$k]['user_nickname'] = $user['user_nickname'];
                }
            } else {
                $data[$k]['avatar'] = cmf_get_image_url($site_setting['dhead']);
                $data[$k]['user_nickname'] = $site_setting['site_name'];
            }

            $thisstatus = $model_task::getStatusInTask($user_id, $v['id'], $v['num'], $v['userid'],
                ['limitnum'], true);

            if ($v['isempty'] == 0) {
                $data[$k]['statusstr'] = '<li class="index_item_status fr status_ing">任务进行中</li>';
            } else {
                $data[$k]['statusstr'] = '<li class="index_item_status fr status_no">已接完</li>';
            }
            if ($v['ispause'] == 1) {
                $data[$k]['statusstr'] = '<li class="index_item_status fr status_no">任务已关闭</li>';
            }

            if (!empty($thisstatus['status'])) {
                $data[$k]['statusstr'] = '<li class="index_item_status fr status_no">您不能接此任务</li>';
                if ($thisstatus['status'] == 1) {
                    $data[$k]['statusstr'] = '<li class="index_item_status fr status_no">您已接了此任务</li>';
                }
            }

            if ($v['start'] > time()) {
                $data[$k]['statusstr'] = '<li class="index_item_status fr status_no">还未开始</li>';
            }
            if ($v['iscount'] == 1) {
                $data[$k]['statusstr'] = '<li class="index_item_status fr status_no">已结束</li>';
            }

            $data[$k]['topstr'] = '';
            if ($v['istop'] == 1) {
                $data[$k]['topstr'] = '<span class="top_task">置顶</span>';
            }

            $data[$k]['taskid'] = '<span class="font_mini">(' . $v['id'] . ')</span>';
            //现在只显示未结算的,不是自己发的,份数还有的,时间未结束的(同时满足)
            if ($thisstatus['status'] != 1 && $v['isempty'] == 0 && $v['userid'] != $user_id) {
                array_push($alldata, $data[$k]);
            }
        }

        $this->success('success', $alldata);
    }


    /**
     * 放弃任务
     */

    function giveuptask()
    {

        try {
            if ($this->request->isPost()) {
                $uid = $this->getUserId();
                if (empty($uid)) $this->error(['code' => 10001, 'msg' => $this->standard_code['10001']]);
                $taskid = input('taskid');
                $where = [
                    'userid' => $uid,
                    'taskid' => $taskid
                ];
                if (empty($uid) || empty($taskid))
                    throw new Exception('参数错误，请重试！');

                Db::startTrans();
                $res = db('self_task_receive')->where($where)->delete();
                if (empty($res) || $res == 0) {
                    throw new Exception('放弃失败');
                }

                $res1 = db('self_task')->where(['id' => $taskid])->whereExp('num', ">`oldnum`")->setInc('oldnum');
                db('self_task')->where(['id' => $taskid])->setField('isempty', 0);
                if (empty($res1) || $res1 == 0) {
                    throw new Exception('放弃失败');
                }
                Db::commit();
                $this->success('放弃成功');
            }
        } catch (Exception $exception) {
            Db::rollback();
            $this->error('系统异常:' . $exception->getMessage());
        }
    }

    /**
     * 从林退单
     */
    function cl_edit_task()
    {
        try {
            if ($this->request->isPost()) {
                $data = $this->request->param();
                if ($data['qudao'] != 'conglin' || $data['apikey'] != "6b9528016630c063ad9c75b53708356c") {
                    throw new Exception('参数错误');
                }
                $task = db('self_task')->where(['orderid' => $data['orderid'], 'order_aa' => $data['order_aa']])->find();
                if (empty($task)) throw new Exception('任务不存在');
                if ($task['cl_is_back'] == 1) throw new Exception('任务已在退单中');
                if ($task['cl_is_back'] == 2) throw new Exception('任务已退单');
                $log = [
                    'taskid' => $task['id'],
                    'orderid' => $data['orderid'],
                    'order_aa' => $task['order_aa'],
                    'create_time' => time(),
                    'status' => 1, // 待处理
                    'qudao' => $data['qudao'],
                ];
                Db::startTrans();
                $res = db('cl_edit_task_log')->insertGetId($log);
                if ($res) {
                    db('self_task')->where(['id' => $task['id']])->setField('cl_is_back', 1);
                    Db::commit();
                    $this->success('请求成功');
                }
            }
        } catch (Exception $exception) {
            Db::rollback();
            $this->error('系统异常:' . $exception->getMessage());
        }
    }

}