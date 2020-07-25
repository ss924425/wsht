<?php

namespace app\admin\controller;

use api\home\model\NewsModel;
use app\admin\model\ServiceSortModel;
use app\common\model\SelfTaskModel;
use app\admin\model\SelfTaskSortModel;
use app\admin\model\UserModel;
use app\admin\model\UserMoneyModel;
use cmf\controller\AdminBaseController;
use think\Db;
use think\Exception;
use think\Log;


class ServiceController extends AdminBaseController
{
    /**
     * 服务列表
     */
    public function index()
    {
        $adminUser = db('user')->where('id',session('ADMIN_ID'))->value('user_type');

    }

    /**
     * 添加服务
     */
    public function addService()
    {
        $adminid = session('ADMIN_ID');
        $adminuser = db('user')->find($adminid);
        $this->assign('adminuser',$adminuser['user_type']);
        $sorts = db('service_sort')
            ->where('status', 1)
            ->where('pid','<>',0)
            ->order(['number' => 'desc', 'id' => 'desc'])
            ->field('id,name')->select()->ToArray();
        if (empty($sorts)) {
            $this->error('请先添加任务分类');
        }
        $sid = input('sid') ? input('sid') : $sorts[0]['id'];
        $info = db('service_sort')->field('*')->where('id',$sid)->select();
        $this->assign('sorts', $sorts);
        $this->assign('info', $info[0]);
        return $this->fetch();
    }

    /**
     * 服务分类
     */
    public function servicesort()
    {
        $list = Db::name('service_sort')
            ->order(['id' => 'asc'])
            ->paginate(10);

        $this->assign([
            'list' => $list,
            'page' => $list->render(),
        ]);

        return $this->fetch();
    }

    /**
     * 服务分类添加
     */
    public function servicesortAdd()
    {
        $sorts = db('service_sort')
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
        $info = db('service_sort')->where('id', $sid)->find();
        $this->assign('sid',$sid);
        $this->assign('sorts',$sorts);
        $this->assign('info',$info);
        return $this->fetch();
    }

    public function servicesortAddPost()
    {
        $model = new ServiceSortModel();
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $data['pid'] = $data['sortid'];
            $psort = db('service_sort')->where('id',$data['pid'])->find();
            if (!empty($psort)){
                $data['pname'] = $psort['name'];
            }else{
                $data['pname'] = '';
            }
            $model->adminAddServiceSort($data);
            $this->success('添加成功', url('service/servicesort'));
        }
    }

    public function servicesortEdit()
    {
        $id = $this->request->param('id');
        $info = model('service_sort')->find($id);
        $pname = model('service_sort')->where('id',$info['pid'])->value('name');
        $this->assign('id', $id);
        $this->assign('info', $info);
        $this->assign('pname', $pname);
        return $this->fetch();
    }

    public function servicesortEditPost()
    {
        $model = new ServiceSortModel();
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $model->adminEditServiceSort($data);
            $this->success('保存成功', url('service/servicesortEdit', array('id' => $data['id'])));
        }
    }

    public function servicesortDelete()
    {
        $param = request()->param();

        $id = request()->param('id');

        if (isset($id)) {

            $sort = db('service_sort')->where(['id'=>$id])->find();
            if ($sort['pid'] == 0){
                $this->error('顶级分类不能删除');
                $sortList = db('service_sort')->where(['pid'=>$id])->select()->toArray();
                if (empty($sortList)){
                    $result = db('service_sort')->delete($id);
                    if ($result) {
                        $this->success('删除成功', url('service/servicesort'));
                    }
                } else{
                    $this->error('该分类有下级分类不能删除');
                }
            } else {
                $result = db('service_sort')->delete($id);
                if ($result) $this->success('删除成功', url('service/servicesort'));
            }

        }

        if (isset($param['ids'])) {
            $ids = $this->request->param('ids/a');
            foreach ($ids as $id){
                $sort = db('service_sort')->where(['id'=>$id])->find();
                if ($sort['pid'] == 0){
                    $this->error('选择的分类中有顶级分类不能删除');
                } else {
                    $sortList = db('service_sort')->where(['pid'=>$id])->select()->toArray();
                    if (empty($sortList)){
                        $result = db('service_sort')->where(['id' => $id])->delete();
                        if ($result) {
                            $this->success('删除成功', url('service/servicesort'));
                        }
                    } else {
                        $this->error('分类有下级分类不能删除');
                    }
                }
            }
        }
    }

    public function servicesortPublish()
    {
        $param = $this->request->param();
        $portalPostModel = db('service_sort');

        if (isset($param['ids']) && isset($param["yes"])) {
            $ids = $this->request->param('ids/a');
            $portalPostModel->where(['id' => ['in', $ids]])->update(['status' => 1]);

            $this->success("上架成功！", '');
        }

        if (isset($param['ids']) && isset($param["no"])) {
            $ids = $this->request->param('ids/a');

            foreach ($ids as $id) {
                $sort = $portalPostModel->where('pid',0)->where('id',$id)->find();
                if ($sort) $this->error('顶级分类不能下架');
            }

            $portalPostModel->where(['id' => ['in', $ids]])->update(['status' => 0]);

            $this->success("下架成功！", '');
        }
    }

    public function listOrder()
    {
        parent::listOrders(Db::name('service_sort'), 'number');
        $this->success("排序更新成功！", '');
    }

    public function servicetype()
    {
        $list = Db::name('service_type')->alias('t')
            ->join('service_sort s','t.sortid = s.id','left')
            ->field('t.*,s.name as sname')
            ->order(['t.id' => 'asc'])
            ->paginate(10);

        $this->assign([
            'list' => $list,
            'page' => $list->render(),
        ]);

        return $this->fetch();
    }

    public function servicetypeAdd()
    {
        $sorts = db('service_sort')
            ->where('status', 1)
            ->where('pid','<>',0)
            ->order(['number' => 'desc', 'id' => 'desc'])
            ->field('id,name')->select()->ToArray();
        if (empty($sorts)) {
            $this->assign('sorts',null);
            $this->assign('info',null);
            return $this->fetch();
        }
        $sid = input('sid') ? input('sid') : $sorts[0]['id'];
        $info = db('service_sort')->where('id', $sid)->find();
        $this->assign('sid',$sid);
        $this->assign('sorts',$sorts);
        $this->assign('info',$info);
        return $this->fetch();
    }

    public function servicetypeAddPost()
    {
        $data = $this->request->param();
        if (Db::name('service_type')->where('name',$data['name'])->find()){
            $this->error('类型已存在');
        } else {
            $res = Db::name('service_type')->insert($data);
            if ($res)
                $this->success('添加成功');
            else
                $this->error('添加失败');
        }
    }

}