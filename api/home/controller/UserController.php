<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace api\home\controller;

use api\user\controller\VerificationController;
use think\Db;
use think\Validate;
use cmf\controller\RestBaseController;

class UserController extends RestBaseController
{

    //订单详情
    public function order_detail()
    {
        $orderid = input('orderid');

        $m = db('shop_order');
        $map['order_id'] = $orderid;
        $cache = $m->where($map)->find();
        if (!$cache) {
            $this->error('此订单不存在!');
        }
        $cache['items'] = unserialize($cache['items']);

        switch ($cache['state']) {
            case -2:
                $cache['orderstate'] = '已关闭';
                break;
            case -1:
                $cache['orderstate'] = '已取消';
                break;
            case 0:
                $cache['orderstate'] = '待支付';
                break;
            case 1:
                $cache['orderstate'] = '待发货';
                break;
            case 2:
                $cache['orderstate'] = '待收货';
                break;
            case 3:
                $cache['orderstate'] = '已完成';
                break;
        }

        switch ($cache['is_true']) {
            case 0:
                $cache['paystate'] = "未支付";
                break;
            case 1:
                $cache['paystate'] = "已支付";
                break;
        }

        $addres = db('user_address') ->where([
            'user_id'   => $this->userId,
            'isdefault' => 1
        ]) ->find();
        if (!is_null($addres)){
            $cache['shouhuoaddress'] = $addres['sheng'].$addres['shi'].$addres['xian'].$addres['address'];
            $cache['shouhuoren']     = $addres['username'];
            $cache['shouhuomobile']  = $addres['telphone'];
        } else {
            $cache['shouhuoaddress'] = false;
        }

        $cache['create_time'] = date('Y-m-d H:i:s', $cache['create_time']);
        $this->success('', $cache);
    }

    public function wuliu_detail()
    {
        $orderid = input('orderid');

        $m = db('shop_order');
        $map['order_id'] = $orderid;
        $cache = $m->where($map)->field('order_sn,fahuokdcode,fahuokdnum,fahuotime')->find();
        if (!$cache) {
            $this->error('此订单不存在!');
        }

        $api_key = '6a521dbfe39130377fc913541595c4b6';
        $api = 'http://v.juhe.cn/exp/index?key=' . $api_key . '&com=' . $cache['fahuokdcode'] . '&no=' . $cache['fahuokdnum'];
        $res = file_get_contents($api);
        $res = json_decode($res, true);
        if ($res['resultcode'] != 200 && $res['error_code'] == 0) {
            $this->error('物流信息查询失败');
        }

        $data['order_sn'] = $cache['order_sn'];
        $data['fahuo_sn'] = $cache['fahuokdnum'];
        $data['fahuotime'] = date('Y-m-d H:i:s', $cache['fahuotime']);
        $data['fahuokd'] = $res['result']['company'];
        $data['list'] = array_reverse($res['result']['list']);
        $this->success('查询成功',$data);
    }

    //订单取消
    public function order_cancel()
    {
        $orderid = input('orderid');
        $m = db('shop_order');
        $map['order_id'] = $orderid;
        $cache = $m->where($map)->find();
        if (!$cache) {
            $this->error('此订单不存在!');
        }
        if ($cache['state'] != 0) {
            $this->error('只有未付款订单可以取消！');
        }
        $user_id = $this->getUserId();
        $map['user_id'] = $user_id;
        $re = $m->where($map)->setField('state', -1);
        if ($re) {
            //订单取消只有后端日志
            $mslog = db('Shop_order_syslog');
            $dlog['oid'] = $cache['order_id'];
            $dlog['msg'] = '订单取消';
            $dlog['type'] = 0;
            $dlog['ctime'] = time();
            $res = $mslog->insert($dlog);
            if ($res) {
                $this->success('订单取消成功！');
            } else {
                $this->error('订单取消失败,请重新尝试！');
            }
        } else {
            $this->error('订单取消失败,请重新尝试！');
        }
    }

    //确认收货 -1已取消 0未支付 1已支付 2已发货 3已完成
    public function order_ok()
    {
        $orderid = input('orderid');

        $m = db('Shop_order');
        $map['order_id'] = $orderid;
        $user_id = $this->getUserId();
        $map['user_id'] = $user_id;
        $cache = $m->where($map)->find();
        if (!$cache) {
            $this->error('此订单不存在!');
        }
        if ($cache['state'] != 2 && $cache['state'] != 1) {
            $this->error('只有待收货订单可以确认收货！');
        }
        $cache['etime'] = time();//交易完成时间
        $cache['state'] = 3;
        $rod = $m->update($cache);
        if (FALSE !== $rod) {

            //后端日志
            $mlog = db('Shop_order_syslog');
            $dlog['oid'] = $cache['order_id'];
            $dlog['msg'] = '交易完成-会员点击';
            $dlog['type'] = 5;
            $dlog['paytype'] = $cache['paytype'];
            $dlog['ctime'] = time();
            $rlog = $mlog->insert($dlog);
            $this->success('交易已完成，感谢您的支持！');
        } else {
            //后端日志
            $mlog = db('Shop_order_syslog');
            $dlog['oid'] = $cache['order_id'];
            $dlog['msg'] = '确认收货失败';
            $dlog['type'] = -1;
            $dlog['paytype'] = $cache['paytype'];
            $dlog['ctime'] = time();
            $rlog = $mlog->insert($dlog);
            $this->error('确认收货失败，请重新尝试！');
        }
    }

    //订单列表
    public function orderlist()
    {
        $type = input('type');
        $p = input('p');
        $limit = input('limit') ? input('limit') : 6;
        $p = max($p,1);
        $end = $limit;
        $start = ($p - 1) * $end;

        $m = db('Shop_order');
        $map['user_id'] = $this->userId;
        switch ($type) {
            case '999':
                //全部
                $map['state'] = array('neq', '-1');
                break;
            case '0':
                //待付款
                $map['state'] = 0;
                break;
            case '1':
                //待发货
                $map['state'] = 1;
                break;
            case '2':
                //待收货
                $map['state'] = 2;
                break;
            case '888':
                //待评价(已经确认收货才能评价)
                $map['state'] = array('eq', '3');
                $map['iscomment'] = array('eq', '0');
                break;
        }
        $field = "order_id,order_sn,payprice,totalnum,items,state,is_true,iscomment";
        $cache = $m->field($field)->where($map)->order('order_id desc')->limit($start, $end)->select()
            ->each(function ($item, $key) {
                $item['items'] = unserialize($item['items']);
                return $item;
            })->toArray();

        if (!$cache) {
            $this->error('没有更多数据');
        }
        //待付款订单数
        $where['state'] = 0;
        $where['is_true'] = 0;
        $where['user_id'] = $this->userId;
        $daifucount = $m->where($where)->count();
        $count = $m->where($map)->count();
        $data['code'] = 1;
        $data['daifu'] = $daifucount;
        $data['total'] = ceil($count / $end);
        $data['data'] = $cache;
        $this->ajaxReturn($data);
    }

    //获取晒单
    public function get_shaidan_list()
    {
        $p = isset($_GET['p']) ? intval($_GET['p']) : 1;
        $state = isset($_POST['state']) ? input('state') : 0;//0为已晒单，1为未晒单商品
        $type = isset($_POST['type']) ? input('type') : 2;//1为所有晒单，2为个人晒单
        $end = 10;
        $start = ($p - 1) * $end;

        $member = $this->user;
        if ($state == 0) {
            if ($type == 1) {
                $count = $this->db->GetCount("select count(*) from `@#_shaidan`");
                $postlist = $this->db->GetList("select a.sd_userid,a.sd_title,a.sd_content,a.sd_thumbs,a.sd_photolist,a.sd_time,b.id,b.q_user,b.thumb,b.q_user_code,b.q_end_time from `@#_shaidan` a left join `@#_shoplist` b on a.sd_shopid=b.id order by a.sd_time desc limit $start,$end");
            } else {
                $count = $this->db->GetCount("select count(*) from `@#_shaidan` where sd_userid='$member[uid]'");
                $postlist = $this->db->GetList("select a.sd_userid,a.sd_title,a.sd_content,a.sd_thumbs,a.sd_photolist,a.sd_time,b.id,b.q_user,b.thumb,b.q_user_code,b.q_end_time from `@#_shaidan` a left join `@#_shoplist` b on a.sd_shopid=b.id where a.sd_userid='$member[uid]' order by a.sd_time desc limit $start,$end");
            }
        } elseif ($state == 1) {
            $shaidanlist = $this->db->GetList("select sd_shopid from `@#_shaidan` a left join `@#_shoplist` b on a.sd_shopid=b.id where a.sd_userid='$member[uid]'");
            //获得的商品
            $orderlist = $this->db->GetList("select * from `@#_member_go_record` a left join `@#_shoplist` b on a.shopid=b.id where b.q_uid='$member[uid]' order by a.time desc");
            $sd_id = $r_id = array();
            foreach ($shaidanlist as $sd) {
                $sd_id[] = $sd['sd_shopid'];
            }
            foreach ($orderlist as $rd) {
                if (!in_array($rd['shopid'], $sd_id)) {
                    $r_id[] = $rd['shopid'];
                }
            }
            if (!empty($r_id)) {
                $rd_id = implode(",", $r_id);
                $rd_id = trim($rd_id, ',');
            } else {
                $rd_id = "0";
            }
            //未晒单
            $count = $this->db->GetCount("select count(*) from  `@#_shoplist`  where id in($rd_id)");
            $postlist = $this->db->GetList("select id,title,thumb,q_user_code,q_end_time from  `@#_shoplist`  where id in($rd_id) order by id limit  $start, $end");
        }
        if (empty($postlist)) {
            $this->error('暂无记录');
        }
        if ($state == 0) {
            foreach ($postlist as $key => $val) {
                $tmpuser = unserialize($val['q_user']);
                $postlist[$key]['q_user'] = $tmpuser;
                if ($tmpuser['username']) {
                    $postlist[$key]['q_user']['username'] = $tmpuser['username'];
                } else {
                    $postlist[$key]['q_user']['username'] = mask_mobile($tmpuser['mobile']);
                }
                $postlist[$key]['sd_photolist'] = explode(';', rtrim($val['sd_photolist'], ';'));
                if ($type == 2) {
                    $num = $this->db->GetOne("select sum(gonumber) as num from `@#_member_go_record` where shopid={$val['id']} and uid={$member['uid']}");
                } else {
                    $num = $this->db->GetOne("select sum(gonumber) as num from `@#_member_go_record` where shopid={$val['id']} and uid={$val['sd_userid']}");
                }
                $postlist[$key]['gonumber'] = $num['num'] ? $num['num'] : 0;
                $postlist[$key]['sd_time'] = date('Y-m-d H:i:s', $val['sd_time']);
                $postlist[$key]['q_end_time'] = date('Y-m-d H:i:s', $val['q_end_time']);
            }
        } else {
            foreach ($postlist as $key => $val) {
                $postlist[$key]['q_end_time'] = date('Y-m-d H:i:s', $val['q_end_time']);
            }
        }
        $data['code'] = 1;
        $data['total'] = ceil($count / $end);
        $data['data'] = $postlist;
        $this->ajaxReturn($data);
    }


    public function get_address_list()
    {
        $p = isset($_GET['p']) ? $_GET['p'] : 1;
        $limit = input('limit') ? input('limit') : 5;
        $end = $limit;
        $start = ($p - 1) * $end;
        $m = db('user_address');
        $map['user_id'] = $this->userId;
        $order['isdefault'] = 'desc';
        $order['id'] = 'desc';
        $total = $m->where($map)->count();
        $address = $m->where($map)->order($order)->limit($start, $end)->select()->toArray();
        if (!$address) {
            $this->error('无地址信息');
        }
        foreach ($address as $k => $v) {
            $address[$k]['shouhuodizhi'] = $v['sheng'] . $v['shi'] . $v['xian'] . $v['address'];
        }
        $data['code'] = 1;
        $data['data'] = $address;
        $data['total'] = ceil($total / $end);
        $this->ajaxReturn($data);
    }

    public function get_address_one()
    {
        $map['user_id'] = $this->userId;
        $order['isdefault'] = 'desc';
        $order['id'] = 'desc';
        $address = db('user_address')->where($map)->order($order)->find();
        if (!$address) {
            $this->error('没有收货信息');
        }
        $address['shouhuodizhi'] = $address['sheng'] . $address['shi'] . $address['xian'] . $address['jiedao'];
        $this->success('', $address);
    }

    public function add_address()
    {
        $data = $this->request->param();
        unset($data['id']);
        if (empty($data['username']) || empty($data['telphone'])) {
            $this->error("收货人信息不完整");
        }

        if (empty($data['sheng']) || empty($data['shi']) || empty($data['address'])) {
            $this->error("收货地址不完整");
        }

        if ($data['isdefault'] == 1) {
            $map['user_id'] = $this->userId;
            $map['isdefault'] = 1;
            $res = db('user_address')->where($map)->find();
            if ($res) {
                $this->error('已有默认收货地址');
            }
        }
        $data['create_time'] = time();
        $data['user_id'] = $this->userId;
        $ql = db('user_address')->insert($data);
        if ($ql) {
            $this->success("地址添加成功");
        }
        $this->error("地址添加失败");
    }

    public function update_address()
    {
        $data = $this->request->param();
        if (!$data['id']) {
            $this->error('请传入正确参数');
        }
        if (empty($data['username']) || empty($data['telphone'])) {
            $this->error("收货人信息不完整");
        }

        if (empty($data['sheng']) || empty($data['shi']) || empty($data['address'])) {
            $this->error("收货地址不完整");
        }

        if ($data['isdefault'] == 1) {
            $map['user_id'] = $this->userId;
            $map['isdefault'] = 1;
            $map['id'] = ['neq', $data['id']];
            $res = db('user_address')->where($map)->find();
            if ($res) {
                $this->error('已有默认收货地址');
            }
        }

        $ql = db('user_address')->update($data);
        if ($ql !== false) {
            $this->success("地址修改成功");
        }
        $this->error("地址修改失败");
    }

    public function del_address()
    {
        $id = input('id');
        if (!$id) {
            $this->error('请传入正确参数');
        }
        $q1 = db('user_address')->delete($id);
        if ($q1) {
            $this->success("删除成功");
        } else {
            $this->error("删除失败");
        }
    }

    //设为默认
    public function set_address()
    {
        $id = input('id');
        if (!$id) {
            $this->error('请传入正确参数');
        }

        Db::startTrans();

        $map1['id'] = $id;
        $map1['user_id'] = $this->userId;
        $q1 = db('user_address')->where($map1)->setField('isdefault', 1);
        $map2['user_id'] = $this->userId;
        $map2['id'] = ['neq', $id];
        $q2 = db('user_address')->where($map2)->setField('isdefault', 0);

        if ($q1 === false || $q2 === false) {
            Db::rollback();
            $this->error("设置失败");
        }

        Db::commit();
        $this->success("设置成功");

    }

    public function setPayPass()
    {
        if (!request() ->isPost()){
            $this->error('非法请求');
        }
        $userId = db('user_token') ->where('token',request() ->param('token')) ->value('user_id');
        $userInfo = db('user') ->where('id',$userId) ->find();
        if (is_null($userInfo)){
            $this->error('请重新登陆');
        }

        $verify = cmf_check_verification_code($userInfo['mobile'],request() ->param('verify'));
        if ($verify != ''){
            $this->error($verify);
        }
        $password = request() ->param('password');
        $rePassword = request() ->param('rePassword');
        if (!$password){
            $this->error('请请输入支付密码');
        }
        if (!is_numeric($password) || strlen($password) != 6){
            $this->error('支付密码必须是6位数字');
        }
        if ($password != $rePassword){
            $this->error('两次密码不一致');
        }
        $res = db('user') ->where('id',$userId) ->setField('pay_pass',cmf_password($password));
        if (!$res){
            $this->error('设置失败');
        }
        $this->success('设置成功');
    }

    public function verifyCodeSend ()
    {
        if (!request() ->isPost()){
            $this->error('非法请求');
        }
        $userInfo = db('user')
            ->where('id',db('user_token') ->where('token',request() ->param('token')) ->value('user_id'))
            ->find();
        if (is_null($userInfo)){
            $this->error('请重新登陆');
        }
        $code = cmf_get_verification_code($userInfo['mobile']);
        if (empty($code)) {
            $this->error("验证码发送过多,请明天再试!");
        }

        $template = cmf_get_option('sms_template_verification_code')['template'];
        $message = str_replace('{$code}',$code,$template);
        $result = cmf_send_sms($userInfo['mobile'],$message);
        cmf_verification_code_log($userInfo['mobile'], $code);

        if ($result['error'] == 1) {
            $this->error($result['message']);
        } else {
            $this->success('验证码已经发送成功!');
        }
    }

    function userScoreList()
    {
        $uid = $this->userId;

        $addscorelist = db('user_score_log')->where('user_id',$uid)->where('type','=',1)->select()->toArray();
        $decscorelist = db('user_score_log')->where('user_id',$uid)->where('type','=',2)->select()->toArray();
        if (!empty($addscorelist)) {
            foreach ($addscorelist as $k=>$v){
                $addscorelist[$k]['create_time'] = date('Y-m-d',$v['create_time']);
            }
        }
        if (!empty($decscorelist)) {
            foreach ($decscorelist as $k=>$v){
                $decscorelist[$k]['create_time'] = date('Y-m-d',$v['create_time']);
            }
        }

        $data['addscorelist'] = empty($addscorelist) ? [] : $addscorelist;
        $data['decscorelist'] = empty($decscorelist) ? [] : $decscorelist;
        $this->success('查询成功',$data);
    }


}
