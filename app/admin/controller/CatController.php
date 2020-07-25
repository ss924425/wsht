<?php

namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;

class CatController extends AdminBaseController
{
    /**
     * 分类列表
     */
    public function index()
    {
        $name = input('cat_name');
        $where = [];
        if(!empty($name)){
            $where['cat_name'] = ['like','%'.$name.'%'];
        }
        $list = db('cat') -> alias('c')
            ->join('mc_user u','c.create_id = u.id')
            -> where(['c.isdelete' => 0])
            -> where($where)
            -> field('c.*,u.user_login')
            -> select();
        $this -> assign('list',$list);
        return $this -> fetch();
    }

    public function add()
    {
        return $this -> fetch();
    }

    public function addpost()
    {
        $post = input('cat_name');
        if(!empty($post)){
            $add = [];
            $add['cat_name'] = $post;
            $add['create_id'] = cmf_get_current_admin_id();
            $add['create_time'] = time();

            $res = db('cat') -> insert($add);
            if($res){
                $this->success('添加成功', url('cat/index'));
            }else{
                $this->error('添加失败');
            }
        }else{
            $this->error('请求错误', url('cat/add'));
        }
    }

    public function delete()
    {
        $id = input('id');
        if($id > 0){
            $task = db('task') -> where(['catid' => $id]) -> count();
            if($task != 0){
                $this->error('该分类已被使用，不可删除');
            }

            $res = db('cat') -> where(['id' => $id]) -> update(['isdetele' => 1]);
            if($res){
                $this->success('删除成功', url('cat/index'));
            }else{
                $this->error('删除失败');
            }
        }
    }

    public function edit()
    {
        $id = input('id');
        if($id > 0){
            $res = db('cat') -> where(['id' => $id,'isdelete' => 0]) -> find();
            if($res){
                $this -> assign('res',$res);
                return $this -> fetch();
            }else{
                $this -> error('这个分类不存在');
            }
        }else{
            $this -> error('请求错误');
        }
    }

    public function editpost()
    {
        $id = input('id');
        $name = input('cat_name');
        $task = db('task') -> where(['id'=> $id,'isdelete' => 0]) -> count();
        if($task != 0){
            $this -> error('该分类已被使用，不可编辑');
        }else{
            $data['cat_name'] = $name;
            $data['modify_id'] = cmf_get_current_admin_id();
            $data['modify_time'] = time();

            $res = db('cat') -> where(['id' => $id]) -> update($data);
            if($res){
                $this -> success('编辑成功');
            }else{
                $this -> error('编辑失败');
            }
        }
    }
}