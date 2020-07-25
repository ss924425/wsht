<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 老猫 <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;
use cmf\phpqrcode\QRcode;

class AgentController extends AdminBaseController
{
    public function _initialize()
    {
        parent::_initialize();
    }

    //代理商列表
    public function index()
    {
        $request = $this->request;
        $comp_name = $request->param('comp_name');
        $contacts = $request->param('contacts');
        $cont_phone = $request->param('cont_phone');

        if($comp_name){
            $where['comp_name'] = ['like','%'.$comp_name.'%'];
        }

        if($contacts){
            $where['contacts'] = ['like','%'.$contacts.'%'];
        }

        if($cont_phone){
            $where['cont_phone'] = ['like','%'.$cont_phone.'%'];
        }

        $where['is_delete'] = 1;

        $list = db('agent')
            -> where($where)
            -> order("id desc")
            ->paginate(10,false,['query'  => array("comp_name"=>$comp_name,'contacts' => $contacts,'cont_phone' => $cont_phone)]);

        $page = $list -> render();

        $this -> assign('page',$page);
        $this -> assign('list',$list);
        return $this->fetch();
    }

    //添加代理商
    public function add()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $a_count = db('agent') -> count();
            $data['comp_id'] = $a_count + 1;
            $data['create_time'] = time();

            $res = db('agent') -> insertGetId($data);
            if($res){

                //加入系统操作日志
                $syslog['ip'] = get_client_ip();
                $syslog['user_id'] = cmf_get_current_admin_id();
                $syslog['address'] = _get_ip_dizhi($syslog['ip']);
                $syslog['url'] = $this->_url;
                $syslog['mark'] = "添加代理商:$res";
                $syslog['create_time'] = time();
                $syslog['type'] = 5;
                Db::name('admin_syslog')->insert($syslog);

                $this->success("添加成功！");
            }else{
                $this->error("添加失败！");
            }
        }else{
            return $this -> fetch();
        }
    }

    //生成二维码
    public function agent_code()
    {
        $request = $this->request;
        $id = $request->param('id');

        $ercode = $this -> qrcode($id);
        $data['ewm'] = $ercode; //二维码地址

        $res = db('agent') -> where(['id' => $id]) -> update($data);
        if($res){
            $this->success("成功！");
        }else{
            $this->error("失败！");
        }
    }

    //编辑代理商
    public function edit()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();

            $data['revise_id'] = cmf_get_current_admin_id();
            $data['revise_time'] = time();
            $ewm = db('agent') -> where(['id' => $data['id']]) -> value('ewm');
            if(empty($ewm)){
                $ercode = $this -> qrcode($data['id']);
                $data['ewm'] = $ercode; //二维码地址
            }

            $res = db('agent') -> update($data);
            if($res){

                //加入系统操作日志
                $syslog['ip'] = get_client_ip();
                $syslog['user_id'] = cmf_get_current_admin_id();
                $syslog['address'] = _get_ip_dizhi($syslog['ip']);
                $syslog['url'] = $this->_url;
                $syslog['mark'] = "编辑代理商:{$data['id']}";
                $syslog['create_time'] = time();
                $syslog['type'] = 5;
                Db::name('admin_syslog')->insert($syslog);

                $this->success("编辑成功！");
            }else{
                $this->error("编辑失败！");
            }
        }else{
            $id = $this->request->param('id');
            $detail = db('agent') -> where(['id' => $id]) -> find();

            $this -> assign('agent',$detail);
            return $this -> fetch();
        }
    }

    //删除代理商
    public function delete()
    {
        $id = $this->request->param('id');

        $detail = db('agent') -> where(['id' => $id]) -> find();
        if(empty($detail)){
            $this -> error('请求参数错误');
        }

        $res = db('agent') -> where(['id' => $id]) -> update(['is_delete' => 0]);
        if($res){

            //加入系统操作日志
            $syslog['ip'] = get_client_ip();
            $syslog['user_id'] = cmf_get_current_admin_id();
            $syslog['address'] = _get_ip_dizhi($syslog['ip']);
            $syslog['url'] = $this->_url;
            $syslog['mark'] = "删除代理商:{$id}";
            $syslog['create_time'] = time();
            $syslog['type'] = 5;
            Db::name('admin_syslog')->insert($syslog);

            $this -> success('删除成功');
        }else{
            $this -> error('删除失败');
        }
    }

    //生成二维码
    public function qrcode($id)
    {
        //TODO'完善路径信息'
        $value = 'http://trygameapp.xlwang.cn/index/Agent/order.html?comp_id='.$id;

        $errorCorrectionLevel = 'L';//容错级别
        $matrixPointSize = 6;//生成图片大小
        $savehader = $_SERVER['DOCUMENT_ROOT'];
        $savefoote = '/upload/qrcode/'.date('Ymd').$id.'.png';

        $savepath = $savehader.$savefoote;
        //生成二维码图片
        QRcode::png($value, $savepath, $errorCorrectionLevel, $matrixPointSize, 2);

        return $savefoote;
    }

    //申请列表
    public function mine()
    {
        $request = $this->request;
        $batchNumber = $request->param('batchNumber');
        $name = $request->param('name');
        $mobile = $request->param('mobile');
        $agentid = $request->param('agentid');

        if($batchNumber){
            $where['o.batchNumber'] = ['like','%'.$batchNumber.'%'];
        }

        if($name){
            $where['o.name'] = ['like','%'.$name.'%'];
        }

        if($mobile){
            $where['o.mobile'] = ['like','%'.$mobile.'%'];
        }

        if($agentid){
            $where['o.agentid'] = ['like','%'.$agentid.'%'];
        }

        $where['a.is_delete'] = 1;

        $list = db('agent_order') -> alias('o')
            -> join('mc_agent a','a.id = o.agentid')
            -> where($where)
            -> field('o.*,a.comp_name,a.cont_phone')
            -> order("o.id desc") -> paginate(17,false,['query'  => array('batchNumber' => $batchNumber,'name' => $name,'mobile' => $mobile,'agentid' => $agentid)]);

        $page = $list -> render();

        $this -> assign('list',$list);
        $this -> assign('page',$page);
        return $this -> fetch();
    }

    //审核结果
    public function mine_edit()
    {
        $request = $this->request;

        //数据id
        $id = $request->param('id');
        //处理结果
        $type = $request->param('type');
        //驳回原因
        $submit = $request->param('keyword');

        $agent = db('agent_order') -> where(['id' => $id]) -> find();

        if($type == 1){
            //审核通过
            if(empty($agent)){
                $this -> error('参数错误!');
            }

            //处理需要向user表添加的数据
            for($i = 0;$i < $agent['requestNum']; $i++){
                $add[$i]['user_type'] = 1;
                $add[$i]['mobile'] = $agent['mobile'];
                $add[$i]['user_pass'] = $agent['user_pass'];//cmf_password($agent['user_pass']);
                $add[$i]['user_status'] = 1;
                $add[$i]['idno'] = $agent['idno'];
                $add[$i]['apply_account'] = $agent['aliCard'];
                $add[$i]['apply_name'] = $agent['aliName'];
                $add[$i]['vip_end_time'] = strtotime("+1 year");
                $add[$i]['agentId'] = $agent['agentid'];
                $add[$i]['batch'] = $agent['batchNumber'];
                $add[$i]['create_time'] = time();
            }

            $data['status'] = $type;

            // 启动事务
            Db::startTrans();

            $res = Db::name('user') -> insertAll($add);
            $rec = Db::name('agent_order') -> where(['id' => $id]) -> update($data);
            if($res && $rec){
                // 提交事务
                Db::commit();

                //加入系统操作日志
                $syslog['ip'] = get_client_ip();
                $syslog['user_id'] = cmf_get_current_admin_id();
                $syslog['address'] = _get_ip_dizhi($syslog['ip']);
                $syslog['url'] = $this->_url;
                $syslog['mark'] = "审核代理商订单:{$id}-成功,生成账号:$res";
                $syslog['create_time'] = time();
                $syslog['type'] = 5;
                Db::name('admin_syslog')->insert($syslog);

                //将用户id转化为用户昵称
                $this -> username();
                $this -> success('成功通过');
            }else{
                // 回滚事务
                Db::rollback();
                $this -> error('请求失败');
            }
        }else if($type == 2){
            //审核驳回
            $data['status'] = $type;
            $data['reject'] = $submit;

            $rec = Db::name('agent_order') -> where(['id' => $id]) -> update($data);
            $res = Db::name('agent') -> where(['id' => $agent['agentId']]) -> setInc('max_num',$agent['requestNum']);

            if($rec && $res){
                // 提交事务
                Db::commit();

                $syslog['ip'] = get_client_ip();
                $syslog['user_id'] = cmf_get_current_admin_id();
                $syslog['address'] = _get_ip_dizhi($syslog['ip']);
                $syslog['url'] = $this->_url;
                $syslog['mark'] = "审核代理商订单:{$id}-驳回,原因:$submit";
                $syslog['create_time'] = time();
                $syslog['type'] = 5;
                Db::name('admin_syslog')->insert($syslog);

                $this -> success('驳回成功');
            }else{
                // 回滚事务
                Db::rollback();
                $this -> error('请求失败');
            }
        }else{
            $this -> error('请求错误');
        }
    }


    public function username()
    {
        $res = db('user') -> where(['user_login' => '']) -> select() -> toarray();
        foreach($res as $k => $v){
            $add['user_login'] = $v['id'];
            db('user') -> where(['id' => $v['id']]) -> update($add);
            $add['user_login'] = '';
        }
    }
}