<?php

namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;
use think\Exception;

class NoticeController extends AdminBaseController
{
    /**
     * 系统公告列表
     */
    public function index()
    {
        $title = $this->request->param('title');
        if ($title) {
            $where['A.title'] = ['like', '%' . $title . '%'];
        }
        $where['A.delete'] = 0;
        $list = db('notice')->alias('A')
            ->join('user B', 'A.uid = B.id')
            ->field('A.*,B.user_login')
            ->where($where)
            ->order('A.id desc')
            ->paginate(10);
        $page = $list->render();
        $this->assign('list', $list);
        $this->assign('page', $page);
        return $this->fetch();
    }

    /**
     *  添加系统公告
     */
    public function add()
    {
        return $this->fetch();
    }

    public function addPost()
    {
        $data = $this->request->param();
        if (is_array($data)) {
            if (empty($data['title'])) {
                $this->error('请输入标题');
            }
            if (empty($data['content'])) {
                $this->error('请输入文本内容');
            }
            $data['create_time'] = date('Y-m-d H:i:s', time());
            $data['ip'] = get_client_ip();
            $data['uid'] = cmf_get_current_admin_id();

            $res = db('notice')->insert($data);

            if ($res) {
                $this->success('提交成功');
            } else {
                $this->error('提交失败');
            }
        }
    }

    /**
     * 修改系统公告
     */
    public function edit()
    {
        $id = $this->request->param('id');

        if ($id) {
            $data = db('notice')->where(['id' => $id])->find();
            $this->assign('data', $data);
            return $this->fetch();
        } else {
            $this->error('请求错误');
        }
    }

    public function editPost()
    {
        $data = $this->request->param();
        if (is_array($data)) {
            if (empty($data['title'])) {
                $this->error('请输入标题');
            }
            if (empty($data['content'])) {
                $this->error('请输入文本内容');
            }

            $data['ip'] = get_client_ip();
            $data['uid'] = cmf_get_current_admin_id();

            $res = db('notice')->where(['id' => $data['id']])->update($data);

            if ($res) {
                $this->success('提交成功');
            } else {
                $this->error('提交失败');
            }
        }
    }

    public function delete()
    {
        $id = $this->request->param('id');
        if ($id) {
            $data = db('notice')->where(['id' => $id])->find();
            if ($data) {
                $res = db('notice')->where(['id' => $id])->update(['delete' => 1]);

                if ($res) {
                    $this->success('删除成功');
                } else {
                    $this->error('删除失败');
                }
            } else {
                $this->error('数据不存在');
            }
        } else {
            $this->error('请求错误');
        }
    }

    public function msgsend()
    {
        try {
            Db::startTrans();
            $id = $this->request->param('id');
            if ($id) {
                $data = db('notice')->where(['id' => $id])->field('title,content')->find();
                if ($data) {
                    $res = db('notice')->where(['id' => $id])->update(['issend' => 1]);
                    if ($res) {
                        $vip = ['1', '4', '5'];
                        $where['user_type'] = ['in', $vip];
                        $newdata = Db::name('user')->where($where)->field('id as uid')->select()->toArray();
                        foreach ($newdata as $k => $v) {
                            $newdata[$k]['title'] = $data['title'];
                            $newdata[$k]['news'] = $data['content'];
                            $newdata[$k]['time'] = time();
                            $newdata[$k]['type'] = 1;
                        }
                        $res = Db::name('news')->insertAll($newdata);
                        if ($res) {
                            Db::commit();
                            $this->success('发送成功');
                        } else {
                            throw  new  Exception('消息发送失败');
                        }
                    } else {
                        throw  new  Exception('消息更新失败');
                    }
                } else {
                    throw  new  Exception('消息不存在');
                }
            } else {
                throw  new  Exception('请求错误');
            }
        } catch (Exception $exception) {
            Db::rollback();
            $this->error($exception->getMessage());
        }
    }
}