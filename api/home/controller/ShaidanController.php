<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace api\home\controller;

use think\Db;
use think\Validate;
use cmf\controller\RestBaseController;

class ShaidanController extends RestBaseController
{

    public function upload_shaidanimg()
    {
        $file = request()->file('image');
        // 移动到框架应用根目录/public/uploads/ 目录下
        $file_path = ROOT_PATH . 'public/upload/shaidan';
        if (!file_exists($file_path)) {
            mkdirs($file_path);
        }
        $info = $file->validate(['size'=>2*1024*1024,'ext'=>'jpg,png,jpeg'])->move($file_path);
        if ($info) {
            $saveExtension = $info->getSaveName();
            $savename = $info->getFilename();
            $savepath = str_replace($savename,'',$saveExtension);
            $image = \think\Image::open($file_path . DS . $saveExtension);
            $newsavepath = rtrim($file_path,DS) . DS .  trim($savepath,DS) . DS . "thumb_" . $savename;

            $res = $image->thumb(200, 200, 1)->save($newsavepath);
            if(!$res){
                @unlink(rtrim($file_path,DS) . DS .  $saveExtension);
                $this->error('上传失败');
            }
        } else { // 上传失败获取错误信息
            $this->error($file->getError());
        }
        $this->success('上传成功',"shaidan" . DS . $saveExtension);
    }

    //添加晒单
    public function post_shaidan()
    {
        $user_id = $this->getUserId();
        $order_id = input('orderid');
        $content = input('content');

        $map['user_id'] = $user_id;
        $map['order_id'] = $order_id;
        $order = db('shop_order')->where($map)->find();
        if (!$order) {
            $this->error('不存在此订单');
        }
        if ($order['state'] < 2) {
            $this->error('此订单还还不能评论哟');
        }
        if ($order['state'] == 2) {
            $this->error('您还未确认收货哟');
        }
        if($order['iscomment']){
            $this->error('您已经评价过此订单了');
        }
        if (!$content) {
            $this->error("评价内容不能为空");
        }

        $photolist = input('post.imgList') ? input('post.imgList') : '';
        if (!$photolist) {
            $this->error("图片不能为空");
        }
        $thumbs = explode(';', $photolist)[0];

        $data['order_id'] = $order_id;
        $data['user_id'] = $user_id;
        $data['content'] = $content;
        $data['thumbs'] = $thumbs;
        $data['photolist'] = $photolist;
        $data['create_time'] = time();

        DB::startTrans();

        $res = db('shop_shaidan')->insert($data);
        if (!$res) {
            Db::rollback();
            $this->error('评价失败，稍后重试');
        }

        $res = db('shop_order')->where($map)->setField('iscomment', 1);
        if (!$res) {
            Db::rollback();
            $this->error('评价失败,请稍后重试');
        }

        Db::commit();

        $this->success('评价成功');
    }

    /**
     * 晒单列表页
     */
    public function shaidan()
    {
        $parm = input('param');
        $p = $_POST['p'] ? input('p') : 1;
        $end = 10;
        $start = ($p - 1) * $end;

        switch ($parm) {
            case 'new':
                $sel = '`sd_time`';
                break;
            case 'renqi':
                $sel = '`sd_zhan`';
                break;
            case 'pinglun':
                $sel = '`sd_ping`';
                break;
            default:
                $sel = '`sd_time`';
        }

        $count = $this->db->GetList("select sd_id from `@#_shaidan` order by $sel DESC");
        $shaidan = $this->db->GetList("select * from `@#_shaidan` order by $sel DESC limit $start,$end");

        $user = $time = $pic = array();
        foreach ($shaidan as $v) {
            $user[] = get_user_name($v['sd_userid']);
            $time[] = date("Y-m-d H:i:s", $v['sd_time']);
            $userinfo = $this->db->GetOne("select img from `@#_member` where `uid`='$v[sd_userid]'");
            $pic[] = get_uploads_path() . $userinfo['img'];
        }
        for ($i = 0; $i < count($shaidan); $i++) {
            $shaidan[$i]['user'] = $user[$i];
            $shaidan[$i]['time'] = $time[$i];
            $shaidan[$i]['pic'] = $pic[$i];
        }
        $pagex = ceil(count($count) / $end);
        if ($p <= $pagex) {
            $shaidan[0]['page'] = $p + 1;//下一页的页数，动态加载
        }
        if ($pagex > 0) {
            $shaidan[0]['sum'] = $pagex;
        } else if ($pagex == 0) {
            $shaidan[0]['sum'] = $pagex;
        }
        ajaxReturn($shaidan);
    }

    /**
     * 晒单详情
     */
    public function detail()
    {
        $sd_id = input('sd_id');
        $shaidan = $this->db->GetOne("select * from `@#_shaidan` where `sd_id`='$sd_id'");
        if (!$shaidan) {
            ajaxError('参数错误');
        }

        if (!empty($shaidan['sd_shopid'])) {
            $goods = $this->db->GetOne("select * from `@#_shoplist` where `id` = {$shaidan['sd_shopid']} order by `qishu` DESC");
        }

        $shaidan_new = $this->db->GetList("select * from `@#_shaidan` order by `sd_id` DESC limit 5");
        $shaidan_hueifu = $this->db->GetList("select * from `@#_shaidan_hueifu` where `sdhf_id`='$sd_id'");

        $substr = substr($shaidan['sd_photolist'], 0, -1);
        $sd_photolist = explode(";", $substr);
        foreach ($sd_photolist as $k => $v) {
            $sd_photolist[$k] = get_uploads_path() . $v;
        }
        $data['shaidan_item'] = $shaidan;
        $data['shaidan_goods'] = $goods;
        $data['shaidan_new'] = $shaidan_new;
        $data['shaidan_hueifu'] = $shaidan_hueifu;
        $data['shaidan_photo'] = $sd_photolist;
        ajaxReturn($data);
    }

    //羡慕嫉妒恨
    public function xianmu()
    {
        $sd_id = input('sd_id');
        $shaidan = $this->db->GetOne("select * from `@#_shaidan` where `sd_id`='$sd_id'");
        $sd_zhan = $shaidan['sd_zhan'] + 1;
        $this->db->Query("UPDATE `@#_shaidan` SET sd_zhan='" . $sd_zhan . "' where sd_id='" . $sd_id . "'");
        $data['sd_id'] = $sd_id;
        $data['sd_zan'] = $sd_zhan;
        ajaxReturn($data);
    }

    //晒单评论
    public function shaidan_hueifu()
    {
        $itemid = input('sd_id');
        $shoplist = $this->db->GetList("select * from `@#_shoplist` where `sid`='$itemid'");
        if (!$shoplist) {
            ajaxError('参数错误');
        }
        $shop = '';
        foreach ($shoplist as $list) {
            $shop .= $list['id'] . ',';
        }
        $id = trim($shop, ',');
        if ($id) {
            $shaidan = $this->db->GetList("select * from `@#_shaidan` where `sd_shopid` IN ($id) order by `sd_id` DESC");
            $sum = 0;
            foreach ($shaidan as $sd) {
                $shaidan_hueifu = $this->db->GetList("select * from `@#_shaidan_hueifu` where `sdhf_id`='$sd[sd_id]'");
                $sum = $sum + count($shaidan_hueifu);
            }
        } else {
            $shaidan = 0;
            $sum = 0;
        }
        $data['shaidan_hueifu'] = $shaidan_hueifu;
        $data['sum'] = $sum;
        ajaxReturn($data);
    }
}