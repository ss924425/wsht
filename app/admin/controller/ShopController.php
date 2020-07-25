<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/4/11 0011
 * Time: 15:11
 */

namespace app\admin\controller;

use app\admin\model\ShopBannerModel;
use api\home\model\NewsModel;
use app\admin\model\ShopCategoryModel;
use app\admin\model\ShopGoodsModel;
use app\admin\model\ShopOrderModel;
use cmf\controller\AdminBaseController;
use think\Db;

class ShopController extends AdminBaseController
{
    public function banner()
    {
        $bannerModel = new ShopBannerModel();
        $where['delete_time'] = 0;
        $banner = $bannerModel->where($where)->order('id','desc')->paginate(20);
        $this->assign('banner',$banner->items());
        $this->assign('page',$banner->render());
        return $this->fetch();
    }

    public function addBanner()
    {
        return $this->fetch();
    }

    public function addBannerPost()
    {
        $shopBannerModel = new ShopBannerModel();
        $data = $this->request->param();
        if ($this->request->isPost()) {
            $res = $shopBannerModel->isUpdate(false)->data($data['post'],true)->allowField(true)->save();
            if($res){
                $this->success('添加成功');
            }
            $this->error('添加失败');
        }
    }

    public function deleteBanner()
    {
        $param = $this->request->param();
        $ShopBannerModel = new ShopBannerModel();

        if (isset($param['id'])) {
            $id = $this->request->param('id', 0, 'intval');
            $ShopBannerModel->where(['id' => $id])->update(['delete_time' => time()]);
            $this->success("删除成功！");
        }

        if (isset($param['ids'])) {
            $ids = $this->request->param('ids/a');
            $ShopBannerModel->where(['id' => ['in', $ids]])->update(['delete_time' => time()]);
            $this->success("删除成功！");
        }
    }

    public function editBanner()
    {
        $id = $this->request->param('id',0,'intval');
        $shopBannerModel = new ShopBannerModel();
        $banner = $shopBannerModel->where('id',$id)->find();
        $this->assign('banner',$banner);
        return $this->fetch();
    }

    public function editBannerPost()
    {

        if ($this->request->isPost()){
            $data = $this->request->param();
            $id = $data['post']['id'];
            $banner = db('shop_banner')->where('id',$id)->find();
            if (empty($banner)){
                $this->error('参数错误');
            }
            $bannerData = [
                'title' => $data['post']['title'],
                'url'   => $data['post']['url'],
                'urltype' => $data['post']['urltype'],
                'img' => $data['post']['img'],
                'position' => $data['post']['position']
            ];

            $res = db('shop_banner')->where('id',$id)->update($bannerData);
            if ($res){
                $this->success('修改成功');
            }
            $this->error('修改失败');
        }
    }

    function good()
    {
        $param = $this->request->param();
        $where['delete_time'] = 0;
        if (isset($param['keyword'])) {
            $where['good_name'] = ['like', "%{$param['keyword']}%"];
        }
        if (isset($param['cate_gid']) && $param['cate_gid'] != '') {
            $where['cate_gid'] = intval($param['cate_gid']);
        }
        if (isset($param['cate_pid']) && $param['cate_pid'] != '') {
            $where['cate_pid'] = intval($param['cate_pid']);
        }
        $shop_goods = new ShopGoodsModel();
        $good_list = $shop_goods->where($where)->order("good_id desc")->paginate(20)
            ->each(function ($item, $key) {
                $shop_category = new ShopCategoryModel();
                $item['cate_name'] = $shop_category->getFieldByCate_id($item['cate_gid'], 'cate_name');
                $gid_name = $shop_category->getFieldByCate_id($item['cate_pid'], 'cate_name');
                if ($gid_name != null) {
                    $item['cate_name'] .= " -- " . $gid_name;
                }
            });
        $good_list->appends($param);
        $shop_category = db('shop_category');
        $category = $shop_category->where(" pid = 0 ")->select()->toArray();
        $this->assign("category", $category);
        $jscategory = array();
        foreach ($category as $k => $v) {
            $id = $v['cate_id'];
            $jscategory[$id] = $shop_category->where(" pid = $v[cate_id] ")->select()->toArray();
        }
        $this->assign("jscategory", json_encode($jscategory));
        $this->assign("good_list", $good_list->items());
        $this->assign("page", $good_list->render());
        $this->assign([
            'keyword' => isset($param['keyword']) ? $param['keyword'] : '',
            'cate_gid' => isset($param['cate_gid']) ? $param['cate_gid'] : '',
            'cate_pid' => isset($param['cate_pid']) ? $param['cate_pid'] : ''
        ]);
        return $this->fetch();
    }

    function publishGood()
    {
        $param = $this->request->param();
        $ShopGoodsModel = new ShopGoodsModel();

        if (isset($param['ids']) && isset($param["yes"])) {
            $ids = $this->request->param('ids/a');

            $ShopGoodsModel->where(['good_id' => ['in', $ids]])->update(['is_true' => 1]);

            $this->success("发布成功！", '');
        }

        if (isset($param['ids']) && isset($param["no"])) {
            $ids = $this->request->param('ids/a');

            $ShopGoodsModel->where(['good_id' => ['in', $ids]])->update(['is_true' => 0]);

            $this->success("取消发布成功！", '');
        }
    }

    function deleteGood()
    {
        $param = $this->request->param();
        $ShopGoodsModel = new ShopGoodsModel();

        if (isset($param['id'])) {
            $id = $this->request->param('id', 0, 'intval');
            $ShopGoodsModel->where(['good_id' => $id])->update(['delete_time' => time()]);
            $this->success("删除成功！");
        }

        if (isset($param['ids'])) {
            $ids = $this->request->param('ids/a');
            $ShopGoodsModel->where(['good_id' => ['in', $ids]])->update(['delete_time' => time()]);
            $this->success("删除成功！");
        }
    }

    function changetype()
    {
        $good_id = $_POST['good_id'];
        $type = $_POST['type'];
        $type_id = $_POST['type_id'];
        if ($type_id == 0) {
            $set_id = 1;
        }
        if ($type_id == 1) {
            $set_id = 0;
        }
        $res = db('shop_goods')->where(" good_id = '$good_id' ")->setField($type, $set_id);
        if ($res) {
            $arr['success'] = 1;
            $arr['type'] = $set_id;
        } else {
            $arr['success'] = 0;
            $arr['info'] = $type_id;
        }
        echo json_encode($arr);
    }

    function addGood()
    {
        $shop_category = db('shop_category');
        $category = $shop_category->where(" pid = 0 ")->select()->toArray();
        $this->assign("category", $category);
        foreach ($category as $k => $v) {
            $id = $v['cate_id'];
            $jscategory[$id] = $shop_category->where(" pid = $v[cate_id] ")->select()->toArray();
        }
        $shop_good_type = db('shop_good_type');
        $type_info = $shop_good_type->order("type_id desc")->select()->toArray();
        $this->assign('type_info', $type_info);
        $this->assign("jscategory", json_encode($jscategory));
        return $this->fetch();
    }

    function addGoodPost()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            if(!$data['post']['cate_gid']){
                $this->error('请选择商品主分类');
            }
            if(!$data['post']['cate_pid']){
                $this->error('请选择商品子分类');
            }

            $data['post']['best'] = 0;  // 是否精品
            $data['post']['hot'] = 0;   // 是否热销
            $data['post']['new'] = 0;   // 是否新品

            $post = $data['post'];

            $result = $this->validate($post, 'ShopGoods');
            if ($result !== true) {
                $this->error($result);
            }

            $ShopGoodsModel = new ShopGoodsModel();

            if (!empty($data['photo_names']) && !empty($data['photo_urls'])) {
                $data['post']['more']['photos'] = [];
                foreach ($data['photo_urls'] as $key => $url) {
                    $photoUrl = cmf_asset_relative_url($url);
                    array_push($data['post']['more']['photos'], ["url" => $photoUrl, "name" => $data['photo_names'][$key]]);
                }
            }

            $ShopGoodsModel->adminAddGood($data['post']);
            $this->success('添加成功!', url('shop/editGood', ['id' => $ShopGoodsModel->good_id]));
        }
    }

    function editGood()
    {
        $good_id = $this->request->param('id', 0, 'intval');
        $shop_goods = new ShopGoodsModel();
        $shop_category = db('shop_category');
        $category = $shop_category->where(" pid = 0 ")->select()->toArray();
        $good_info = $shop_goods->where('good_id', $good_id)->find();
        $this->assign("category", $category);
        $this->assign("good_info", $good_info);
        foreach ($category as $k => $v) {
            $id = $v['cate_id'];
            $jscategory[$id] = $shop_category->where(" pid = $v[cate_id] ")->select()->toArray();
        }
        $shop_good_type = db('shop_good_type');
        $type_info = $shop_good_type->order("type_id desc")->select()->toArray();
        $this->assign('type_info', $type_info);
        $this->assign("jscategory", json_encode($jscategory));
        return $this->fetch();
    }

    function editGoodPost()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            if(!$data['post']['cate_gid']){
                $this->error('请选择商品主分类');
            }
            if(!$data['post']['cate_pid']){
                $this->error('请选择商品子分类');
            }

            $post = $data['post'];

            $result = $this->validate($post, 'ShopGoods');
            if ($result !== true) {
                $this->error($result);
            }

            $ShopGoodsModel = new ShopGoodsModel();

            if (!empty($data['photo_names']) && !empty($data['photo_urls'])) {
                $data['post']['more']['photos'] = [];
                foreach ($data['photo_urls'] as $key => $url) {
                    $photoUrl = cmf_asset_relative_url($url);
                    array_push($data['post']['more']['photos'], ["url" => $photoUrl, "name" => $data['photo_names'][$key]]);
                }
            }
            $ShopGoodsModel->adminEditGood($data['post']);
            $this->success('保存成功!');
        }
    }

    public function cate()
    {
        $param = $this->request->param();
        $map['cate_id'] = ['>', 0];
        if (isset($param['cate_name'])) {
            $map['cate_name'] = ['like', "%{$param['cate_name']}%"];
        }
        $m = new ShopCategoryModel();
        $field = ["cate_id", "path", "pid", "lv", "pic_url", "cate_name", "summary", "sorts", "concat(path,'-',cate_id) as bpath"];
        $cache = appTree($m, 0, $field, $map, 'sorts desc', $keyid = 'cate_id', $keypid = 'pid', $keychild = '_child');
        $this->assign('cache', $cache);
        return $this->fetch();
    }

    public function addCate()
    {
        $m = db('shop_category');
        $field = array("cate_id", "pid", "cate_name", "sorts", "concat(path,'-',cate_id) as bpath");
        $map['cate_id'] = ['>', 0];
        $cate = appTree($m, 0, $field, $map, 'sorts desc', $keyid = 'cate_id', $keypid = 'pid', $keychild = '_child');
        $this->assign('cate', $cate);
        return $this->fetch();
    }

    public function addCatePost()
    {
        $param = $this->request->param();
        $m = new ShopCategoryModel();
        if ($this->request->isPost()) {
            if ($param['pid']) {
                $path = setPath($m, $param['pid']);
                $param['path'] = $path['path'];
                $param['lv'] = $path['lv'];
            } else {
                $param['path'] = 0;
                $param['lv'] = 1;
            }
            $param['pic_url'] = cmf_asset_relative_url($param['pic_url']);
            $res = $m->allowField(true)->save($param);
            if ($res) {
                $this->success('设置成功');
            }
            $this->error('设置失败');
        }
    }

    public function editCate()
    {
        $param = $this->request->param();
        $m = new ShopCategoryModel();
        if (isset($param['cate_id'])) {
            $cache = $m->where('cate_id', $param['cate_id'])->find();
            $this->assign('cache', $cache);
        }
        $field = array("cate_id", "pid", "cate_name", "sorts", "concat(path,'-',cate_id) as bpath");
        $map['cate_id'] = ['>', 0];
        $cate = appTree($m, 0, $field, $map, 'sorts desc', $keyid = 'cate_id', $keypid = 'pid', $keychild = '_child');
        $this->assign('cate', $cate);
        return $this->fetch();
    }

    public function editCatePost()
    {
        $param = $this->request->param();
        $m = new ShopCategoryModel();
        if ($this->request->isPost()) {
            if ($param['pid'] == $param['cate_id']) {
                $this->error('不能添加自己为父类');
            }
            $old = $m->where('cate_id', $param['cate_id'])->find();
            if ($old['pid'] != $param['pid']) {
                $hasson = $m->where('pid', $param['cate_id'])->find();
                if ($hasson) {
                    $this->error('此分类有子分类，不可以移动');
                }
            }
            if ($param['pid']) {
                $path = setPath($m, $param['pid']);
                $param['path'] = $path['path'];
                $param['lv'] = $path['lv'];
            } else {
                $param['path'] = 0;
                $param['lv'] = 1;
            }
            if ($param['pic_url'] != ''){
                $param['pic_url'] = cmf_asset_relative_url($param['pic_url']);
            } else {
                unset($param['pic_url']);
            }
            $res = $m->isUpdate(true)->allowField(true)->save($param);
            if ($res !== false) {
                $this->success('设置成功');
            }
            $this->error('设置失败');
        }
    }

    public function deleteCate()
    {
        $m = db('shop_category');
        $param = $this->request->param();
        //删除时判断
        $self = $m->where('cate_id', $param['cate_id'])->find();
        if (!$self) {
            $this->error('不存在');
        }
        Db::startTrans();
        $re = $m->where('cate_id', $param['cate_id'])->delete();
        if (!$re) {
            Db::rollback();
            $this->error('删除失败');
        }
        // 删除所有子类
        $child_path = $m->where('pid', $param['cate_id'])->field('path')->find();
        if ($child_path) {
            $map['path'] = ['like', "{$child_path['path']}%"];
            $re = $m->where($map)->delete();
            if (!$re) {
                Db::rollback();
                $this->error('删除失败');
            }
        }
        Db::commit();
        if ($re) {
            $this->success('删除成功');
        }
        $this->error('删除失败');
    }

    public function skuattr()
    {
        //绑定搜索条件与分页
        $m = db('Shop_skuattr');
        $name = input('name') ? input('name') : '';
        $map['id'] = ['gt', 0];
        if ($name) {
            $map['name'] = array('like', "%$name%");
            $this->assign('name', $name);
        }
        $cache = $m->where($map)->paginate(20);
        $this->assign('cache', $cache);
        $this->assign('page', $cache->render());
        $this->assign('name', $name);
        return $this->fetch();
    }

    //CMS后台SKU属性设置
    public function addSkuattr()
    {
        return $this->fetch();
    }

    public function addSkuattrPost()
    {
        $m = db('Shop_skuattr');
        //处理POST提交
        if ($this->request->isPost()) {
            $data = input('post.');
            $dt['name'] = $data['name'];
            $dt['cctime'] = time();
            $re = $m->insertGetId($dt);
            if (!$re) {
                $this->error('设置失败');
            }
            if ($data['newitem']) {
                $mitem = db('Shop_skuattr_item');
                $dit['pid'] = $re;
                $items = array_filter(explode(',', $data['newitem']));
                foreach ($items as $v) {
                    $dit['name'] = $v;
                    $rit = $mitem->insertGetId($dit);
                    if ($rit) {
                        $rr['path'] = $re . $rit;
                        $mitem->where('id=' . $rit)->update($rr);
                    }
                }
                $son = $mitem->where('pid=' . $re)->field('name,path')->select()->toArray();
                $dson['items'] = "";
                $dson['itemspath'] = "";
                foreach ($son as $v) {
                    $dson['items'] = $dson['items'] . $v['name'] . ',';
                    $dson['itemspath'] = $dson['itemspath'] . $v['path'] . ',';
                }
                $m->where('id=' . $re)->update($dson);
            }
            $this->success('设置成功');
        }
    }


    public function editSkuattr()
    {
        $id = input('id');
        $m = db('Shop_skuattr');
        $cache = $m->where('id=' . $id)->find();
        $this->assign('cache', $cache);
        return $this->fetch();
    }

    public function skuattrSetPost()
    {
        $id = input('id');
        $m = db('Shop_skuattr');
        if ($this->request->isPost()) {
            $data = input('post.');
            $re = $m->update($data);
            if (FALSE === $re) {
                $this->error('设置失败');
            }
            if ($data['newitem']) {
                $mitem = db('Shop_skuattr_item');
                $dit['pid'] = $id;
                $items = array_filter(explode(',', $data['newitem']));
                foreach ($items as $v) {
                    $dit['name'] = $v;
                    $rit = $mitem->insert($dit);
                    if ($rit) {
                        $rr['path'] = $id . $rit;
                        $mitem->where('id=' . $rit)->update($rr);
                    }
                }
                $son = $mitem->where('pid=' . $id)->field('name,path')->select()->toArray();
                $dson['items'] = "";
                $dson['itemspath'] = "";
                foreach ($son as $v) {
                    $dson['items'] = $dson['items'] . $v['name'] . ',';
                    $dson['itemspath'] = $dson['itemspath'] . $v['path'] . ',';
                }
                $m->where('id=' . $id)->update($dson);
            }
            $this->success('设置成功');
        }
    }

    public function skuattrDel()
    {
        $param = $this->request->param();
        $m = db('Shop_skuattr');

        if (isset($param['id'])) {
            $id = $this->request->param('id', 0, 'intval');
            $m->where(['id' => $id])->delete();
            $this->success("删除成功！", '');
        }

        if (isset($param['ids'])) {
            $ids = $this->request->param('ids/a');
            $m->where(['id' => ['in', $ids]])->delete();
            $this->success("删除成功！", '');
        }
    }

    //用于SKUINFO保存
    public function skuattrSave()
    {
        $id = input('id'); //必须使用get方法
        if (!$id) {
            $this->error('商品ID不能为空!');
        }
        //处理skuattr
        $data = input('data');
        if (!$data) {
            $this->error('您还没有选择任何属性！');
        }
        $list = array();
        $arr = array_filter(explode(';', $data));
        foreach ($arr as $k => $v) {
            $arr2 = array_filter(explode('-', $v));
            $arrattr = explode(':', $arr2[0]);
            $arritem = array_filter(explode(',', $arr2[1]));
            $list[$k]['attrid'] = $arrattr[0];
            $list[$k]['attrlabel'] = $arrattr[1];
            $checked = "";
            //循环item
            foreach ($arritem as $kk => $vv) {
                $at = explode(':', $vv);
                $list[$k]['items'][$at[0]] = $at[1];
                $checked = $checked . $at[0] . ',';
            }
            $list[$k]['checked'] = $checked;
        }
        $list = list_sort_by($list, 'attrid', 'asc');
        $m = db('Shop_goods');
        $skuinfo['skuinfo'] = serialize($list);
        $re = $m->where('good_id=' . $id)->update($skuinfo);
        if ($re !== FALSE) {
            $this->success('SKU属性保存成功!如有变更请及时更新所有SKU!');
        }
        $this->error('SKU属性保存失败!请重新尝试!');
    }

    //用于SKU生成
    public function skuattrMake()
    {
        $id = input('id'); //必须使用get方法
        if (!$id) {
            $this->error('商品ID不能为空!');
        }
        $m = db('Shop_goods');
        $goods = $m->where('good_id=' . $id)->find();
        $skuinfo = unserialize($goods['skuinfo']);
        if (!$skuinfo) {
            $this->error('您还未设置或保存SKU属性!');
        }
        $cacheattrs = array(); //缓存所有属性表
        $cache = array(); //缓存skupath列表
        $tmpsku = array(); //缓存零时sku
        $tmpskuattrs = ""; //sku属性对照表
        foreach ($skuinfo as $k => $v) {
            $cacheattrs = $cacheattrs + $skuinfo[$k]['items'];
            $cache[$k] = array_filter(explode(',', $v['checked']));
        }

        if (count($cache) > 1) {
            //快速排列
            $tmp = Descartes($cache);
            foreach ($tmp as $k => $v) {
                $sttr = array();
                foreach ($v as $kk => $vv) {
                    $sttr[$kk] = $cacheattrs[$vv];
                }
                $sk = $id . '-' . implode('-', $v);
                $tmpsku[$k] = $sk;
                $tmpskuattrs[$sk] = implode(',', $sttr);
            }
        } else {
            foreach ($cache[0] as $k => $v) {
                $sk = $id . '-' . $v;
                $tmpsku[$k] = $sk;
                $tmpskuattrs[$sk] = $cacheattrs[$v];
            }
        }
        //dump($tmpskuattrs);
        //dump($tmpsku);

        $fftmpsku = array_flip($tmpsku);
        //处理原始sku
        $msku = db('Shop_goods_sku');
        $oldsku = $msku->where('goodsid=' . $id)->select()->toArray();
        if ($oldsku) {
            foreach ($oldsku as $k => $v) {
                //如果已经建立,判断状态
                if (!in_array($v['sku'], $tmpsku)) {
                    //如果不存在，禁用该sku
                    $v['status'] = 0;
                    $ro = $msku->update($v);
                } else {
                    //如果已经存在，开启该sku
                    $v['status'] = 1;
                    $ro = $msku->update($v);
                    //移除fftmpsku对应项目
                    unset($fftmpsku[$v['sku']]);
                }

            }
        }
        //最后需要添加的新sku
        $finaltmpsku = array_flip($fftmpsku);
        //dump($finaltmpsku);
        //die();
        if ($finaltmpsku) {
            $dsku = array();
            foreach ($finaltmpsku as $k => $v) {
                $dsku[$k]['goodsid'] = $id;
                $dsku[$k]['sku'] = $v;
                $dsku[$k]['skuattr'] = $tmpskuattrs[$v];
                $dsku[$k]['price'] = $goods['good_price'];
                $dsku[$k]['num'] = $goods['num'];
                $dsku[$k]['status'] = 1;
            }
            //强制重新排序
            sort($dsku);
            //计算总库存
            $re = $msku->insertAll($dsku);
            if ($re) {
                $totalnum = $msku->where(array('goodsid' => $id, 'status' => 1))->sum('num');
                if ($totalnum) {
                    $rgg = $m->where('good_id=' . $id)->setField('num', $totalnum);
                }
                $this->success('SKU更新成功!');
            } else {
                $this->error('SKU更新失败!请重新尝试!');
            }
        } else {
            $totalnum = $msku->where(array('goodsid' => $id, 'status' => 1))->sum('num');
            if ($totalnum) {
                $rgg = $m->where('good_id=' . $id)->setField('num', $totalnum);
            }
            $this->success('SKU更新成功!没有新增SKU!');
        }
    }

    //CMS后台SKU管理
    public function sku()
    {
        $goodsid = input('id');
        $this->assign('goodsid', $goodsid);
        //绑定商品和skuinfo
        $goods = db('Shop_goods')->where('good_id=' . $goodsid)->find();
        $this->assign('goods', $goods);
        $skuinfo = array();
        if ($goods['skuinfo']) {
            $skuinfo = unserialize($goods['skuinfo']);
            $skm = db('Shop_skuattr_item');
            foreach ($skuinfo as $k => $v) {
                $checked = explode(',', $v['checked']);
                $attr = $skm->field('path,name')->where('pid=' . $v['attrid'])->select()->toArray();
                foreach ($attr as $kk => $vv) {
                    $attr[$kk]['checked'] = in_array($vv['path'], $checked) ? 1 : '';
                }
                $skuinfo[$k]['allitems'] = $attr;
            }
        }
        $this->assign('skuinfo', $skuinfo);
        //绑定搜索条件与分页
        $m = db('Shop_goods_sku');
        //追入商品条件
        $map['goodsid'] = $goodsid;
        $name = input('name') ? input('name') : '';
        $map['status'] = 1;
        if ($name) {
            $map['skuattr'] = array('like', "%$name%");

        }
        $cache = $m->where($map)->paginate(50);
        $this->assign('cache', $cache);
        $this->assign('page', $cache->render());
        $this->assign('name', $name);
        return $this->fetch();
    }

    //CMS后台sku设置
    public function skuSet()
    {
        $id = input('id');
        $m = db('Shop_goods_sku');
        //处理编辑界面
        $cache = $m->where('id=' . $id)->find();
        $this->assign('cache', $cache);
        return $this->fetch();
    }

    public function skuSetPost()
    {
        $id = input('id');
        $m = db('Shop_goods_sku');
        $cache = $m->where('id=' . $id)->find();
        //处理POST提交
        if ($this->request->isPost()) {
            //只有保存模式
            $data = input('post.');
            $re = $m->where('id=' . $id)->update($data);
            if (FALSE !== $re) {
                //重新计算总库存
                $totalnum = $m->where(array('goodsid' => $cache['goodsid'], 'status' => 1))->sum('num');
                if ($totalnum) {
                    $rgg = db('Shop_goods')->where('good_id=' . $cache['goodsid'])->setField('num', $totalnum);
                }
                $this->success('设置成功');
            }
            $this->error('设置失败');
        }
    }
    //CMS后台SKU查找带回管理器
    public function skuLoader()
    {
        $m = db('Shop_skuattr');
        $findback = input('fbid');
        $this->assign('findback', $findback);
        $map['id'] = array('not in', input('ids'));
        $cache = $m->where($map)->select()->toArray();
        $this->assign('cache', $cache);
        echo $this->fetch();
        exit;
    }

    //CMS后台SKU查找带回模板
    public function skuFindback()
    {
        if($this->request->isAjax()){
            $m = db('Shop_skuattr');
            $id = input('id');
//            $this->assign('findback', $findback);
            $map['id'] = $id;
            $cache = $m->where($map)->limit(1)->find();
            $this->assign('cache', $cache);
            $items = db('Shop_skuattr_item')->where('pid=' . $id)->select()->toArray();
            $this->assign('items', $items);
            echo $this->fetch();exit;
        }
    }

    public function order()
    {
        $param = $this->request->param();

        $ShopOrderModel = new ShopOrderModel();

        $data = $ShopOrderModel->adminOrderList($param);
        $data->appends($param);

        $this->assign('start_time', isset($param['start_time']) ? $param['start_time'] : '');
        $this->assign('end_time', isset($param['end_time']) ? $param['end_time'] : '');
        $this->assign('order_sn', isset($param['order_sn']) ? $param['order_sn'] : '');
        $this->assign('is_true', isset($param['is_true']) ? $param['is_true'] : '');
        $this->assign('state', isset($param['state']) ? $param['state'] : '');
        $this->assign('orders', $data->items());
        $this->assign('page', $data->render());
        return $this->fetch();
    }

    public function orderMore()
    {
        $id = input('order_id');
        $order = db('shop_order')->where('order_id',$id)->find();
        $order['items'] = unserialize($order['items']);
        $express = db('Express');
        $express = $express->select()->toArray();
        $this->assign('order',$order);
        $this->assign('express',$express);
        return $this->fetch();
    }
    //发货快递
    public function orderFhkd()
    {
        $map['order_id'] = input('id');
        $cache = db('Shop_order')->where($map)->find();
        $this->assign('cache', $cache);
        $express = db('Express');
        $express = $express->select()->toArray();
        $this->assign("express", $express);
        echo $this->fetch();exit;
    }

    public function orderFhkdSave()
    {
        $data = input('post.');
        if (!$data) {
            $info['status'] = 0;
            $info['msg'] = '未正常获取数据！';
        }
        $data['fahuotime'] = time();
        $re = db('Shop_order')->where('order_id=' . $data['order_id'])->update($data);
        if (FALSE !== $re) {
            $info['status'] = 1;
            $info['msg'] = '操作成功！';
        } else {
            $info['status'] = 0;
            $info['msg'] = '操作失败！';
        }
        return json($info);
    }

    public function fahuoOrder()
    {
        $id = input('id');
        $map['order_id'] = $id;
        $cache = db('Shop_order')->where($map)->find();
        $res = db('shop_order')->where('order_id',$id)->setField('state',2);
        if($res !== false){
            // 发完货给会员发送消息
            if($cache['user_id']){
                $model_news = new NewsModel();
                $news = "订单{$cache['order_sn']}已发货,等待收货";
                $type = 3;
                $model_news::toUserNews($cache['user_id'], $news, $type);
            }
            $this->success('发货成功');
        }
        $this->error('发货失败');
    }

    //订单关闭
    public function orderClose()
    {
        $map['order_id'] = input('id');
        $cache = db('Shop_order')->where($map)->find();
        $this->assign('cache', $cache);
        echo $this->fetch();exit;
    }

    public function orderCloseSave()
    {
        $data = input('post.');
        if (!$data) {
            $info['status'] = 0;
            $info['msg'] = '未正常获取数据！';
        }
        $m = db('Shop_order');
        $mslog = db('Shop_order_syslog');
        $cache = $m->where('order_id',$data['order_id'])->find();
        switch ($cache['state']) {
            case '0':
                $data['state'] = -2;
                $data['closetime'] = time();
                $re = $m->where('order_id',$data['order_id'])->update($data);

                if (FALSE !== $re) {
                    //后端LOG
                    $log['type'] = 6;
                    $log['paytype'] = $cache['paytype'];
                    $rslog = $mslog->insert($log);

                    $info['status'] = 1;
                    $info['msg'] = '关闭未支付订单成功！';
                } else {
                    //后端LOG
                    $log['type'] = -1;
                    $log['paytype'] = $cache['paytype'];
                    $rslog = $mslog->insert($log);

                    $info['status'] = 0;
                    $info['msg'] = '关闭未支付订单失败！';
                }
                break;
            default:
                $info['status'] = 0;
                $info['msg'] = '只有未付款可以关闭!';
        }
        return json($info);
    }

    //完成订单
    public function orderSuccess()
    {
        $id = input('id');
        $m = db('shop_order');

        $cache['etime'] = time(); //交易完成时间
        $cache['state'] = 3;
        $rod = $m->where('order_id',$id)->update($cache);

        // 完成订单,提醒用户晒单
        $orderinfo = $m->where('order_id',$id)->find();
        if($orderinfo['user_id']){
            $model_news = new NewsModel();
            $news = "订单{$orderinfo['order_sn']}已完成,可进行晒单";
            $type = 3;
            $model_news::toUserNews($cache['user_id'], $news, $type);
        }

        if (FALSE !== $rod) {
            //后端日志
            $mlog = db('Shop_order_syslog');
            $dlog['oid'] = $cache['id'];
            $dlog['msg'] = '交易完成-后台点击';
            $dlog['type'] = 5;
            $dlog['paytype'] = $cache['paytype'];
            $dlog['ctime'] = time();
            $rlog = $mlog->insert($dlog);

            $info['code'] = 1;
            $info['msg'] = '后台确认收货操作完成！';
        } else {
            //后端日志
            $mlog = db('Shop_order_syslog');
            $dlog['oid'] = $cache['order_id'];
            $dlog['msg'] = '确认收货失败';
            $dlog['type'] = -1;
            $dlog['paytype'] = $cache['paytype'];
            $dlog['ctime'] = time();
            $rlog = $mlog->insert($dlog);

            $info['code'] = 0;
            $info['msg'] = '后台确认收货操作失败，请重新尝试！';
        }
        return json($info);
    }


    /**
     * 导出excel表格
     */
    public function exportOrder()
    {
        $sql = session('shoporder');
        $data = Db::query($sql);
        foreach ($data as $k => $v) {
            switch ($v['is_true']) {
                case 0:
                    $data[$k]['is_true'] = '未支付';
                    break;
                case 1:
                    $data[$k]['is_true'] = '已支付';
                    break;
            }
            switch ($v['state']) {
                case -1:
                    $data[$k]['state'] = '已取消';
                    break;
                case 0:
                    $data[$k]['state'] = '待付款';
                    break;
                case 1:
                    $data[$k]['state'] = '待检测';
                    break;
                case 2:
                    $data[$k]['state'] = '已完成';
                    break;
            }
            $data[$k]['create_time'] = date('Y/m/d H:i:s',$v['create_time']);
        }

        $fileName = '商城订单_' . date('YmdHis');
        $header = array(
            '编号', '单号', '手机号', '费用', '支付状态', '订单状态', '创建时间'
        );

        $this->exportExcel($data, $fileName, $header, '用户列表');
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

    function order_more()
    {
        $pay_id = $_GET['pay_id'];
        $users = db('users');
        $order = db('shop_order')->getByPay_id($pay_id);
        if ($order['daijin_id'] > 0) {
            $daijin_id = db('daijin_order')->getFieldByOrder_id($order['daijin_id'], 'daijin_id');
            $daijin_info = db('shop_daijin')->getByDaijin_id($daijin_id);
            $this->assign('daijin_info', $daijin_info);
        }
        if ($order['is_true'] == 0) {
            $order['pay_time'] = $order['pay_name'] = $order['state'] = '待付款';
            $order['is_true'] = "待付款";
        } else {
            $order['is_true'] = "已付款";
            $order['pay_time'] = date("Y/m/d H:i:s", $order['pay_time']);
            $order['pay_name'] = "微信支付";
        }
        $order['time'] = date("Y/m/d H:i:s", $order['time']);
        $user_address = db('user_address')->getByUser_id($order['user_id']);
        $order_info = db('shop_order_detail')->where(" pay_id = '$pay_id' ")->select()->toArray();
        $hexiao_info = array();
        foreach ($order_info as $k => $v) {
            $order_info[$k]['good_fee'] = $v['good_price'] * $v['good_num'];
            $order_id = $v['order_id'];
        }
        $this->assign("order", $order);
        $this->assign("user_address", $user_address);
        $this->assign("order_info", $order_info);
        return $this->fetch();
    }

    //订单发货
    function order_serve()
    {
        $arr = array();
        $order_id = $_POST['id'];
        $arr['order_id'] = $_POST['id'];
        $res = db('shop_order')->where(" order_id = '$order_id' ")->update(array('serve_name' => $_POST['name'], 'serve_id' => $_POST['serve_id'], 'state' => 1, 'serve_time' => time()));
        if ($res) {
            $info = db('shop_order')->where("order_id = '$order_id'")->find();
            $user_id = $info['user_id'];
            $pay_id = $info['pay_id'];
            $openid = db('users')->getFieldByUser_id($user_id, 'openid');
            $order_info = db('shop_order_detail')->field("order_sn,good_name,good_num")->where(" pay_id = '$pay_id' ")->select()->toArray();
            foreach ($order_info as $vv) {
                $good_name .= $vv['good_name'];
                $good_num = $good_num + $vv['good_num'];
            }
            $template = A("Pay/Template");
            $url = 'http://' . $_SERVER['SERVER_NAdbE'] . U('/user/center/order') . "?state=wuliu";
            $template->send_shop($info['order_sn'], $good_name . '*' . $good_num, '商品已标记发货', $openid, $url);
        }
        echo json_encode($arr);
    }

    // 图标列表
    public function iconList()
    {
        $param = $this->request->param();
        $map['id'] = ['>',0];
        $map['delete_time'] = ['=',0];
        if (isset($param['title'])){
            $map['title'] = ['like',"{$param['title']}"];
        }
        $icon = db('icon')->where($map)->select();
        $this->assign('icon',$icon);
        return $this->fetch();
    }

    // 修改图标
    public function editIcon()
    {
        $id = $this->request->param('id');
        $iconInfo = db('icon')->where('id',$id)->find();
        $this->assign('iconInfo',$iconInfo);
        return $this->fetch();
    }

    public function editIconPost()
    {
        if ($this->request->isPost()){
            $data = $this->request->param();
            $id = $data['id'];
            $icon = db('icon')->where('id',$id)->find();
            if (empty($icon)){
                $this->error('参数错误');
            }
            $data['update_time'] = time();
            $res = db('icon')->where('id',$id)->update($data);

            if ($res){
                $this->success('修改成功');
            }
            $this->error('修改失败');
        }
    }

    // 删除图标
    public function deleteIcon()
    {
        $data = $this->request->param();

        if (isset($data['id'])){
            $id = $data['id'];
            $res = db('icon')->where('id',$id)->update(['delete_time' => time()]);
            if ($res){
                $this->success('删除成功');
            }
            $this->error('删除失败');
        }
    }

}