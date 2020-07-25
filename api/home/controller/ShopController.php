<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace api\home\controller;

use api\home\model\ShopBasketModel;
use api\home\model\NewsModel;
use think\Db;
use think\Validate;
use cmf\controller\RestBaseController;

class ShopController extends RestBaseController
{
    public function config()
    {
        $shop_setting = cmf_get_option('shop_setting');
        $data['config'] = array(
            'title' => $shop_setting['shoptitle']
        );
        $map['delete_time'] = 0;
        $map['position'] = 0;
        $shop_ad = db('shop_banner')->where($map)->order('id', 'desc')->limit(5)->select()->toArray();
        if (!$shop_ad) {
            $tmp = array();
        }
        $tmp = array();
        foreach ($shop_ad as $k => $v) {
            $tmp[] = cmf_get_image_url($v['img']);
        }
        $data['slide']['img'] = $tmp;
        $data['slide']['info'] = $shop_ad;

        $where['is_show'] = 1;
        $where['hidden'] = 0;
        $where['pid'] = ['neq',0];
        $category = db('shop_category')->where($where)->order('sorts', 'desc')
            ->field('cate_id,cate_name')->limit(12)->select()->toArray();
        $tmpid = array();
        $tmpdata = array();
        if ($category) {
            foreach ($category as $k => $v) {
                $tmpid[] = array('id' => $v['cate_id'], 'title' => $v['cate_name']);
                $tmpdata[] = array('title' => $v['cate_name'], 'bgSelected' => '#fff', 'bg' => '#f0f0f0');
            }
            array_unshift($tmpid, array('id' => 0, 'title' => '全部商品'));
            array_unshift($tmpdata, array('title' => '全部商品', 'bgSelected' => '#fff', 'bg' => '#f0f0f0'));
        }
        $data['typeid']['id'] = $tmpid;
        $data['typeid']['data'] = $tmpdata;

        return json($data);
    }

    /**
     * 首页商品列表
     */
    public function get_index_goods()
    {
        $p = isset($_GET['p']) ? intval($_GET['p']) : 1;
        $end = input('limit') ? input('limit') : 6;
        $start = ($p - 1) * $end;
        $type = isset($_POST['type']) ? $_POST['type'] : 'recommend';
        $shoplist = array();
        $order['good_price'] = 'asc';
        $order['good_id'] = 'desc';
        $field = "good_id,good_name,good_price,thumbnail,sells,num";
        $m = db('shop_goods');
        switch ($type) {
            case 'recommend':
                $map = array();
                $map['is_true'] = 1;
                $map['delete_time'] = 0;
                $map['recommend'] = 1;
                $shoplist = $m->field($field)->where($map)->order($order)->limit($start, $end)->select()
                    ->each(function ($item, $key) {
                        $item['thumbnail'] = cmf_get_image_url($item['thumbnail']);
                        return $item;
                    })->toArray();
                break;
            case 'new':
                $map = array();
                $map['is_true'] = 1;
                $map['delete_time'] = 0;
                $map['new'] = 1;
                $shoplist = $m->field($field)->where($map)->order($order)->limit($start, $end)->select()
                    ->each(function ($item, $key) {
                        $item['thumbnail'] = cmf_get_image_url($item['thumbnail']);
                        return $item;
                    })->toArray();
                break;
            case 'hot':
                $map = array();
                $map['is_true'] = 1;
                $map['delete_time'] = 0;
                $map['hot'] = 1;
                $shoplist = $m->field($field)->where($map)->order($order)->limit($start, $end)->select()
                    ->each(function ($item, $key) {
                        $item['thumbnail'] = cmf_get_image_url($item['thumbnail']);
                        return $item;
                    })->toArray();
                break;
            case 'best':
                $map = array();
                $map['is_true'] = 1;
                $map['delete_time'] = 0;
                $map['best'] = 1;
                $shoplist = $m->field($field)->where($map)->order($order)->limit($start, $end)->select()
                    ->each(function ($item, $key) {
                        $item['thumbnail'] = cmf_get_image_url($item['thumbnail']);
                        return $item;
                    })->toArray();
                break;
            default:
                $map = array();
                $map['is_true'] = 1;
                $map['delete_time'] = 0;
                $map['recommend'] = 1;
                $shoplist = $m->field($field)->where($map)->order($order)->limit($start, $end)->select()
                    ->each(function ($item, $key) {
                        $item['thumbnail'] = cmf_get_image_url($item['thumbnail']);
                        return $item;
                    })->toArray();
        }
        $count = $m->where($map)->count();
        $data['code'] = 1;
        $data['total'] = floor($count / $end);
        $data['data'] = $shoplist;
        return json($data);
    }

    /**
     * 所有商品列表信息（搜索）
     */
    public function goodslist_all()
    {
        $p = isset($_GET['p']) ? intval($_GET['p']) : 1;
        $title = input('title');
        $end = 20;
        $where = [];

        //搜索
        $where['is_true'] = 1;
        $where['num'] = ['gt',0];
        $where['delete_time'] = 0;

        if($title){
            $where['good_name'] = ['like',"%$title%"];
        }
        $start = ($p - 1) * $end;
        $order['good_price'] = 'asc';
        $order['good_id'] = 'desc';
        $field = "good_id,good_name,good_price,thumbnail,num,sells";
        $total = db('shop_goods')->where($where)->count();
        $shoplist = db('shop_goods')->field($field)->where($where)->order($order)->limit($start,$end)->select()
            ->each(function ($item,$key){
                $item['thumbnail'] = empty($item['thumbnail']) ? '' : cmf_get_image_url($item['thumbnail']);
                return $item;
            })->toArray();

        if(!$shoplist){
            $this->error('暂无数据');
        }

        $data['status'] = 1;
        $data['data'] = $shoplist;
        $data['total'] = floor($total/$end);
        $this->ajaxReturn($data);
    }

    /**
     * 所有商品列表信息（分类页）
     */
    public function goodslist_type()
    {
        $p = isset($_GET['p']) ? intval($_GET['p']) : 1;
        $typeid = input('typeid');
        $limit = input('limit');
        $end = $limit ? $limit : 20;
        $start = ($p-1)*$end;
        $where = [];

        //搜索
        $where['is_true'] = 1;
        $where['num'] = ['gt',0];
        $where['delete_time'] = 0;

        if($typeid){
            $where['cate_pid'] = $typeid;
        }

        $start = ($p - 1) * $end;
        $order['good_price'] = 'asc';
        $order['good_id'] = 'desc';
        $field = "good_id,good_name,good_price,thumbnail,num,sells";
        $total = db('shop_goods')->where($where)->count();
        $shoplist = db('shop_goods')->field($field)->where($where)->order($order)->limit($start,$end)->select()
            ->each(function ($item,$key){
                $item['thumbnail'] = empty($item['thumbnail']) ? '' : cmf_get_image_url($item['thumbnail']);
                return $item;
            })->toArray();

        if(!$shoplist){
            $this->error('暂无数据');
        }

        $data['status'] = 1;
        $data['data'] = $shoplist;
        $data['total'] = floor($total/$end);
        $this->ajaxReturn($data);
    }

    //商品详情
    public function get_goods_detail()
    {
        $id = input('goods_id');
        if (!$id) {
            $this->error('此商品不存在');
        }
        $m = db('Shop_goods');
        $cache = $m->where('good_id=' . $id)->find();
        if (!$cache) {
            $this->error('此商品已下架！');
        }
        if (!$cache['is_true']) {
            $this->error('此商品已下架！');
        }

        //点击量
        $m->where('good_id=' . $id)->setInc('clicks', 1);
        //整理商品标签
        $label = array();
        if ($cache['postage'] == 0) {
            $label[] = '免邮';
        }
        if ($cache['recommend']) {
            $label[] = '推荐';
        }
        if ($cache['new']) {
            $label[] = '最新';
        }
        if ($cache['best']) {
            $label[] = '精品';
        }
        if ($cache['hot']) {
            $label[] = '热销';
        }
        $cache['label'] = $label;

        //处理sku
        if ($cache['issku']) {
            //此商品sku列表
            if ($cache['skuinfo']) {
                $skuinfo = unserialize($cache['skuinfo']);
                $skm = db('Shop_skuattr_item');
                foreach ($skuinfo as $k => $v) {
                    $checked = explode(',', $v['checked']);
                    $attr = $skm->field('path,name')->where('pid', $v['attrid'])->select()->toArray();
                    foreach ($attr as $kk => $vv) {
                        $attr[$kk]['checked'] = in_array($vv['path'], $checked) ? 1 : '';
                    }
                    $skuinfo[$k]['allitems'] = $attr;
                }
                $cache['skuinfo'] = $skuinfo;
            } else {
                $this->error('此商品还没有设置SKU属性！');
            }

            //此商品库存价格信息
            $skuitems = db('Shop_goods_sku')->field('sku,skuattr,price,vprice,num,hdprice,hdnum')->where(array('goodsid' => $id, 'status' => 1))->select()->toArray();
            if (!$skuitems) {
                $this->error('此商品还未生成SKU!');
            }
            $skujson = array();
            foreach ($skuitems as $k => $v) {
                $skujson[$v['sku']]['sku'] = $v['sku'];
                $skujson[$v['sku']]['skuattr'] = $v['skuattr'];
                $skujson[$v['sku']]['price'] = $v['price'];
                $skujson[$v['sku']]['vprice'] = $v['vprice'];
                $skujson[$v['sku']]['num'] = $v['num'];
                $skujson[$v['sku']]['hdprice'] = $v['hdprice'];
                $skujson[$v['sku']]['hdnum'] = $v['hdnum'];
            }
            $cache['skujson'] = $skujson;
        }

        //绑定图集
        if ($cache['more']) {
            $appalbum = $this->getAlbum($cache['more']);
            $cache['album'] = $appalbum ? $appalbum : '';
        }

        //绑定购物车数量
        $basket = db('Shop_basket')->field("id,good_id")->where('user_id', $this->userId)->select()->toArray();
        $cache['basketnum'] = $basket ? count($basket) : 0;

        //处理商品内容
        $cache['good_desc'] = htmlspecialchars_decode($cache['good_desc']);

        $this->success('', $cache);
    }

    //商品晒单列表
    public function get_goods_shaidan()
    {
        $good_id = input('good_id');
        $p = isset($_GET['p']) ? intval($_GET['p']) : 1;
        $end = input('limit') ? input('limit') : 6;
        $start = ($p - 1) * $end;

        $map['delete_time'] = 0;
        $map['good_id'] = $good_id;
        $list = db('shop_shaidan')->where($map)->order('id', 'desc')->limit($start, $end)->select()->toArray();
        if (!$list) {
            $this->error('没有更多晒单');
        }
        $count = db('shop_shaidan') ->where($map)->count();
        $data['code'] = 1;
        $data['total'] = floor($count / $end);
        $data['data'] = $list;
        return json($data);
    }

    //单个商品加入购物车
    public function add_cart()
    {
        $m = db('Shop_basket');
        $data = input('post.');
        if (!$data) {
            $this->error('未获取数据，请重新尝试');
        }
        $data['user_id'] = $this->getUserId();
        //区分SKU模式
        if ($data['sku']) {
            $old = $m->where(array('user_id' => $this->getUserId(), 'sku' => $data['sku']))->find();

            $goodInfo = db('shop_goods')->where('good_id', $data['good_id'])->find();
            if (is_null($goodInfo)) {
                $this->error('无法获取商品信息');
            }
            if ($data['num'] > $goodInfo['num']) {
                $this->error('库存不足');
            }

            if ($old) {
                $old['num'] = $old['num'] + $data['num'];
                $rold = $m->update($old);
                if ($rold === FALSE) {
                    $this->error('添加购物车失败，请重新尝试！');
                } else {
                    $newdata = $this->get_cart();
                    $this->success('添加购物车成功！', $newdata);
                }
            } else {
                $rold = $m->insert($data);
                if ($rold) {
                    $newdata = $this->get_cart();
                    $this->success('添加购物车成功！', $newdata);
                } else {
                    $this->error('添加购物车失败，请重新尝试！');
                }
            }
        } else {
            $old = $m->where(array('user_id' => $this->userId, 'good_id' => $data['good_id']))->find();

            $goodInfo = db('shop_goods')->where('good_id', $data['good_id'])->find();
            if (is_null($goodInfo)) {
                $this->error('无法获取商品信息');
            }
            if ($data['num'] > $goodInfo['num']) {
                $this->error('库存不足');
            }
            if ($old) {
                $old['num'] = $old['num'] + $data['num'];
                $rold = $m->update($old);
                if ($rold === FALSE) {
                    $this->error('添加购物车失败，请重新尝试！');
                } else {
                    $newdata = $this->get_cart();
                    $this->success('添加购物车成功！', $newdata);
                }
            } else {
                $rold = $m->insert($data);
                if ($rold) {
                    $newdata = $this->get_cart();
                    $this->success('添加购物车成功！', $newdata);
                } else {
                    $this->error('添加购物车失败，请重新尝试！');
                }
            }
        }
    }

    //获取购物车数据
    private function get_cart($ids = null)
    {
        $m = db('Shop_basket');
        $mgoods = db('Shop_goods');
        $msku = db('Shop_goods_sku');

        if (is_null($ids)){
            $cache = $m->where(array('user_id' => $this->userId))->select()->toArray();
        } else {
            if (!is_array($ids)){
                return false;
            }
            $cache = $m->where(array('id' => array('in',$ids)))->select()->toArray();
        }
        //无库存、下架，删除ID
        $todelids = '';
        $totalprice = 0;
        $totalnum = 0;
        foreach ($cache as $k => $v) {
            //sku模型
            $field = "good_id,good_name,good_price,num,sorts,thumbnail,issku,is_true";
            $goods = $mgoods->field($field)->where('good_id=' . $v['good_id'])->find();
            $thumbnail = cmf_get_image_url($goods['thumbnail']);
            if ($v['sku']) {
                //取商品数据
                if ($goods['issku'] && $goods['is_true']) {
                    $map['sku'] = $v['sku'];
                    $sku = $msku->where($map)->find();
                    if ($sku['status']) {
                        if ($sku['num']) {
                            //调整购买量
                            $cache[$k]['name'] = $goods['good_name'];
                            $cache[$k]['skuattr'] = $sku['skuattr'];
                            $cache[$k]['num'] = $v['num'] > $sku['num'] ? $sku['num'] : $v['num'];
                            $cache[$k]['kucun'] = $sku['num'];
                            $cache[$k]['price'] = $sku['price'];
                            $cache[$k]['total'] = $sku['num'];
                            $cache[$k]['thumbnail'] = $thumbnail;
                            $totalnum = $totalnum + $cache[$k]['num'];
                            $totalprice = $totalprice + $cache[$k]['price'] * $cache[$k]['num'];
                        } else {
                            //无库存删除
                            $todelids = $todelids . $v['id'] . ',';
                            unset($cache[$k]);
                        }
                    } else {
                        //下架删除
                        $todelids = $todelids . $v['id'] . ',';
                        unset($cache[$k]);
                    }
                } else {
                    //下架删除
                    $todelids = $todelids . $v['id'] . ',';
                    unset($cache[$k]);
                }

            } else {
                if ($goods['is_true']) {
                    if ($goods['num']) {
                        //调整购买量
                        $cache[$k]['name'] = $goods['good_name'];
                        $cache[$k]['skuattr'] = '';
                        $cache[$k]['num'] = $v['num'] > $goods['num'] - $goods['sorts'] ? $goods['num'] - $goods['sorts'] : $v['num'];
                        $cache[$k]['kucun'] = $goods['num'];
                        $cache[$k]['price'] = $goods['good_price'];
                        $cache[$k]['total'] = $goods['num'];
                        $cache[$k]['thumbnail'] = $thumbnail;
                        $totalnum = $totalnum + $cache[$k]['num'];
                        $totalprice = $totalprice + $cache[$k]['price'] * $cache[$k]['num'];
                    } else {
                        //无库存删除
                        $todelids = $todelids . $v['id'] . ',';
                        unset($cache[$k]);
                    }
                } else {
                    //下架删除
                    $todelids = $todelids . $v['id'] . ',';
                    unset($cache[$k]);
                }
            }
        }
        if ($todelids) {
            $rdel = $m->delete($todelids);
            if (!$rdel) {
                $this->error('购物车获取失败，请重新尝试！');
            }
        }

        if (empty($cache)) {
            return array();
        }
        return array('cache' => $cache, 'totalprice' => $totalprice, 'totalnum' => $totalnum);
    }

    //购物车商品列表
    public function cart_goods_list()
    {
        $data = $this->get_cart();
        if (!$data) {
            $this->error('购物车空空如也');
        }
        $this->success('', $data);
    }

    //删除购物车中的某一项
    public function del_basket()
    {
        $id = input('basket_id');
        if (!$id) {
            $this->error('未获取ID参数,请重新尝试！');
        }
        $m = db('Shop_basket');
        $re = $m->where('id', $id)->delete();
        if ($re) {
            $this->success('删除成功，更新购物车状态...');
        } else {
            $this->error('删除失败，自动重新加载购物车...');
        }
    }

    //清空购物车
    public function clear_basket()
    {
        $m = db('Shop_basket');
        $re = $m->where(array('user_id' => $this->userId))->delete();
        if ($re) {
            $this->success('购物车已清空');
        } else {
            $this->error('购物车清空失败，请重新尝试！');
        }
    }

    //根据前台做出的改变，更新购物车，库存检测
    public function update_basket()
    {
        $postStr = file_get_contents("php://input");
        $arr = json_decode($postStr,true);
        $cartdata = $arr['cache'];
        $m = new ShopBasketModel();
        $mgoods = db('Shop_goods');
        $msku = db('Shop_goods_sku');
        //now
        $origindata = $m->where(array('user_id' => $this->userId))->column('id,user_id,good_id,sku,num','id');
        foreach($cartdata as $k => $v){
            $goods = $mgoods->where('good_id', $v['good_id'])->find();
            if ($v['sku']) {
                $sku = $msku->where(array('sku' => $v['sku']))->find();
                if ($sku && $sku['status'] && $goods && $goods['issku'] && $goods['is_true']) {
                    $nownum = $cartdata[$k]['num'];//前台传来的新数据
                    if ($sku['num'] - $nownum >= 0) {
                        //保存购物车新库存
                        if ($nownum <> $origindata[$v['id']]) {
                            $m->allowField(true)->data($v, true)->isUpdate(true)->save();
                        }
                    } else {
                        $this->error('存在已下架或库存不足商品！');
                    }
                }
            }else{
                if ($goods && $goods['is_true']) {
                    $nownum = $cartdata[$k]['num'];
                    if ($goods['num'] - $nownum >= 0) {
                        //保存购物车新库存
                        if ($nownum <> $origindata[$v['id']]) {
                            $m->allowField(true)->data($v, true)->isUpdate(true)->save();
                        }
                    } else {
                        $this->error('存在已下架或库存不足商品，请修改购物车！');
                    }
                } else {
                    $this->error('存在已下架或库存不足商品！');
                }
            }
        }
        $this->success('更新购物车成功！');
    }

    //单件商品立即购买(下单)
    public function fastbuy()
    {
        $data = input('post.');//获取商品信息，立即下单
        if (!$data) {
            $this->error('未获取数据，请重新尝试');
        }
        $user_id = $this->userId;
        if ($user_id == 0) {
            $this->error('请先登陆');
        }
        $userinfo = $this->getUserInfo();
        $goodInfo = db('shop_goods')->where('good_id', $data['good_id'])->find();
        if (is_null($goodInfo)) {
            $this->error('无法获取商品信息');
        }
        if ($data['num'] > $goodInfo['num']) {
            $this->error('库存不足');
        }

        $cache = array();
        array_push($cache,$data);
        $result = $this->calculate_price_fast($cache);
        if($result['status']) {
            $totalprice = $result['result']['totalprice'];
        } else {
            $this->error($result['msg']);
        }

        $morder = db('Shop_order');

        $order_data['total_fee'] = $totalprice;
        $order_data['totalnum'] = $result['result']['totalnum'];
        $order_data['payprice'] = $totalprice;
        $order_data['user_id'] = $user_id;
        $order_data['items'] = stripslashes(htmlspecialchars_decode($result['result']['allitems']));
        $order_data['is_true'] = 0;
        $order_data['state'] = 0;//订单成功，未付款
        $order_data['create_time'] = time();

        $reid = $morder->insertGetId($order_data);
        if ($reid) {
            $old = $morder->where('order_id=' . $reid)->setField('order_sn', 'G'.date('YmdHis') . '-' . $reid);
            if (FALSE !== $old) {
                //后端日志
                $mlog = db('Shop_order_syslog');
                $dlog['oid'] = $reid;
                $dlog['msg'] = '订单创建成功';
                $dlog['type'] = 1;
                $dlog['ctime'] = time();
                $rlog = $mlog->insert($dlog);

                //下单未支付给会员发送消息

                if($user_id){
                    $model_news = new NewsModel();
                    $news = "您已下单{$goodInfo['good_name']},请前往支付";
                    $type = 3;
                    $model_news::toUserNews($order_data['user_id'], $news, $type);
                }

               $this->success('下单成功，前往支付',$reid);
            } else {
                $old = $morder->delete($reid);
                $this->error('订单生成失败！请重新尝试！');
            }
        } else {
            //可能存在代金券问题
            $this->error('订单生成失败！请重新尝试！');
        }
    }

    // 删除购物车中商品
    public function delete()
    {
        if (!request() ->isPost()){
            $this->error('非法请求');
        }
        $cartId = request() ->param('id');
        $cartInfo = db('Shop_basket') ->where('id',$cartId) ->find();
        if (is_null($cartInfo)){
            $this->error('无法获取购物车商品信息');
        }
        $delete = db('Shop_basket') ->where('id',$cartId) ->delete();
        if (!$delete){
            $this->error('删除失败');
        } else {
            $this->success('删除成功');
        }
    }

    // 购物车结算
    public function settlement()
    {
        if (!request() ->isPost()){
            $this->error('非法请求');
        }
        $token = request()->param('token');
        $userId = db('user_token') ->where('token',$token) ->value('user_id');
        if (is_null($userId)){
            $this->error('请重新登陆');
        }
        $this->userId = $userId;
        $userinfo = $this->getUserInfo();
        // 被选中的商品ID
        $cartIds = request() ->param('cartIds');
        if ($cartIds == ''){
            $carts = $this->get_cart();
        } else {
            $cartIds = explode(',',$cartIds);
            if (!is_array($cartIds)){
                $this->error('请选择要支付的商品');
            }
            $carts = $this->get_cart($cartIds);
        }
        if (!$carts){
            $this->error('购物车中没有商品');
        }

        // 生成订单
        foreach ($carts['cache'] as $k => $v) {
            $goodsId = db('shop_goods_sku') ->where('sku',$v['sku']) ->value('goodsid');
            $data[] = ['good_id' => $goodsId,'sku' => $v['sku'],'num' => $v['num']];
        }
        $items = $this->calculate_price_fast($data);
        if ($items['status'] == 0){
            $this->error($items['msg']);
        }

        $morder = db('Shop_order');

        $order_data['total_fee'] = $carts['totalprice'];
        $order_data['totalnum'] = $carts['totalnum'];
        $order_data['payprice'] = $carts['totalprice'];
        $order_data['user_id'] = $this->userId;
        $order_data['items'] = stripslashes(htmlspecialchars_decode($items['result']['allitems']));
        $order_data['is_true'] = 0;
        $order_data['state'] = 0;
        $order_data['create_time'] = time();


        Db::startTrans();

        $re = $morder->insertGetId($order_data);
        if (!$re) {
            Db::rollback();
            $this->error('订单生成失败！请重新尝试！');
        }
        $order_sn = 'G' . date('YmdHis') . '-' . $re;
        $old = $morder->where('order_id', $re)->setField('order_sn', $order_sn);
        if (!$old) {
            Db::rollback();
            $this->error('订单生成失败！请重新尝试！');
        }
        //后端日志
        $mlog = db('Shop_order_syslog');
        $dlog['oid'] = $re;
        $dlog['msg'] = '订单创建成功';
        $dlog['type'] = 1;
        $dlog['ctime'] = time();
        $mlog->insert($dlog);
        //清空购物车

        if ($cartIds == '') {
            $res = db('Shop_basket')->where(array('user_id' => $this->userId))->delete();
        } else {
            $res = db('Shop_basket')->where(array('id' => array('in',$cartIds)))->delete();
        }
        if (!$res) {
            Db::rollback();
            $this->error('订单生成失败！请重新尝试！');
        }
        // 下单成功 给用户发消息
        if($userinfo['user_id']){
            $model_news = new NewsModel();
            $news = "您已下单{$re}，请前往支付";
            $type = 3;
            $model_news::toUserNews($userinfo['user_id'], $news, $type);
        }

        Db::commit();

        $this->success('订单生成成功，请及时支付',$re);
    }


    // 更新购物车总金额
    public function chooseOne()
    {
        if (!request() ->isPost()){
            $this->error('非法请求');
        }
        $cartId = request() ->param('cart_id');
        $cartInfo = db('Shop_basket') ->where('id',$cartId) ->find();
        if (is_null($cartInfo)){
            $this->error('无法获取购物车商品信息');
        }
        $goodInfo = db('Shop_goods') ->where('good_id',$cartInfo['good_id']) ->find();
        if (is_null($goodInfo)){
            $this->error('无法获取商品信息');
        }
        $response = [
            'money' => $goodInfo['good_price'] * $cartInfo['num'],
            'number'=> $cartInfo['num']
        ];
        $this->success('',$response);
    }

    public function numChange()
    {
        if (!request() ->isPost()){
            $this->error('非法请求');
        }
        $cartId = request() ->param('cart_id');
        $cartInfo = db('Shop_basket') ->where('id',$cartId) ->find();
        if (is_null($cartInfo)){
            $this->error('无法获取购物车商品信息');
        }
        $type = request() ->param('type');
        if ($type == 1){
            db('Shop_basket') ->where('id',$cartId) ->setInc('num');
        } elseif ($type == 2) {
            db('Shop_basket') ->where('id',$cartId) ->setDec('num');
        }
    }

    /**
     * @param $vipid 用户ID
     * 计算购物车价格
     */
    private function calculate_price()
    {
        $m = db('Shop_basket');
        $mgoods = db('Shop_goods');
        $msku = db('Shop_goods_sku');
        $user_id = $this->getUserId();
        $cache = $m->where(array('user_id' => $user_id))->select()->toArray();
        //等待删除ID
        $todelids = array();
        //totalprice
        $totalprice = 0;
        //totalnum
        $totalnum = 0;
        foreach ($cache as $k => $v) {
            //sku模型
            $goods = $mgoods->where('good_id=' . $v['good_id'])->find();
            $thumbnail = cmf_get_image_url($goods['thumbnail']);;
            if ($v['sku']) {
                //取商品数据
                if ($goods['issku'] && $goods['is_true']) {
                    $map['sku'] = $v['sku'];
                    $sku = $msku->where($map)->find();
                    if ($sku['status']) {
                        if ($sku['num']) {
                            //调整购买量
                            $cache[$k]['goodsid'] = $goods['good_id'];
                            $cache[$k]['skuid'] = $sku['id'];
                            $cache[$k]['name'] = $goods['good_name'];
                            $cache[$k]['skuattr'] = $sku['skuattr'];
                            $cache[$k]['num'] = $v['num'] > $sku['num'] ? $sku['num'] : $v['num'];
                            $cache[$k]['price'] = $sku['price'];//暂未启用会员价
                            $cache[$k]['total'] = $v['num'] * $sku['price'];
                            $cache[$k]['thumbnail'] = $thumbnail;
                            $totalnum = $totalnum + $cache[$k]['num'];

                            $totalprice = $totalprice + $cache[$k]['price'] * $cache[$k]['num'];
                        } else {
                            //无库存删除
                            array_push($todelids,$v['id']);
                            unset($cache[$k]);
                        }
                    } else {
                        //下架删除
                        array_push($todelids,$v['id']);
                        unset($cache[$k]);
                    }
                } else {
                    //下架删除
                    array_push($todelids,$v['id']);
                    unset($cache[$k]);
                }

            } else {
                if ($goods['is_true']) {
                    if ($goods['num']) {
                        //调整购买量
                        $cache[$k]['goodsid'] = $goods['good_id'];
                        $cache[$k]['skuid'] = 0;
                        $cache[$k]['name'] = $goods['good_name'];
                        $cache[$k]['skuattr'] = '';
                        $cache[$k]['num'] = $v['num'] > $goods['num'] ? $goods['num'] : $v['num'];
                        $cache[$k]['price'] = $goods['good_price'];//不启用会员价
                        $cache[$k]['total'] = $v['num'] * $goods['good_price'];
                        $cache[$k]['thumbnail'] = $thumbnail;
                        $totalnum = $totalnum + $cache[$k]['num'];
                        $totalprice = $totalprice + $cache[$k]['price'] * $cache[$k]['num'];
                    } else {
                        //无库存删除
                        array_push($todelids,$v['id']);
                        unset($cache[$k]);
                    }
                } else {
                    //下架删除
                    array_push($todelids,$v['id']);
                    unset($cache[$k]);
                }
            }
        }
        if ($todelids) {
            $m->where('user_id',$this->userId)->delete($todelids);
        }
        if (empty($cache)) {
            return array('status' => 0, 'msg' => "没有可购买商品");
        }
        //将商品列表
        sort($cache);
        $allitems = serialize($cache);
        //商品数量 订单总价   订单商品
        $result = array(
            'totalnum' => $totalnum,
            'totalprice' => $totalprice,
            'allitems' => $allitems,
            'goodslist' => $cache,
        );
        return array('status' => 1, 'msg' => "计算价钱成功", 'result' => $result); // 返回结果状态
    }

    private function calculate_price_fast($cache)
    {
        $mgoods = db('Shop_goods');
        $msku = db('Shop_goods_sku');

        //totalprice
        $totalprice = 0;
        //totalnum
        $totalnum = 0;
        foreach ($cache as $k => $v) {
            //sku模型
            $goods = $mgoods->where('good_id', $v['good_id'])->find();
            $thumbnail = cmf_get_image_url($goods['thumbnail']);;
            if ($v['sku']) {
                //取商品数据
                if ($goods['issku'] && $goods['is_true']) {
                    $map['sku'] = $v['sku'];
                    $sku = $msku->where($map)->find();
                    if ($sku['status']) {
                        if ($sku['num']) {
                            //调整购买量
                            $cache[$k]['goodsid'] = $goods['good_id'];
                            $cache[$k]['skuid'] = $sku['id'];
                            $cache[$k]['name'] = $goods['good_name'];
                            $cache[$k]['skuattr'] = $sku['skuattr'];
                            $cache[$k]['num'] = $v['num'] > $sku['num'] ? $sku['num'] : $v['num'];
                            $cache[$k]['price'] = $sku['price'];//暂未启用会员价
                            $cache[$k]['total'] = $v['num'] * $sku['price'];
                            $cache[$k]['thumbnail'] = $thumbnail;
                            $totalnum = $totalnum + $cache[$k]['num'];

                            $totalprice = $totalprice + $cache[$k]['price'] * $cache[$k]['num'];
                        } else {
                            //无库存删除
                            unset($cache[$k]);
                        }
                    } else {
                        //下架删除
                        unset($cache[$k]);
                    }
                } else {
                    //下架删除
                    unset($cache[$k]);
                }

            } else {
                if ($goods['is_true']) {
                    if ($goods['num']) {
                        //调整购买量
                        $cache[$k]['goodsid'] = $goods['good_id'];
                        $cache[$k]['skuid'] = 0;
                        $cache[$k]['name'] = $goods['good_name'];
                        $cache[$k]['skuattr'] = '';
                        $cache[$k]['num'] = $v['num'] > $goods['num'] ? $goods['num'] : $v['num'];
                        $cache[$k]['price'] = $goods['good_price'];//不启用会员价
                        $cache[$k]['total'] = $v['num'] * $goods['good_price'];
                        $cache[$k]['thumbnail'] = $thumbnail;
                        $totalnum = $totalnum + $cache[$k]['num'];
                        $totalprice = $totalprice + $cache[$k]['price'] * $cache[$k]['num'];
                    } else {
                        //无库存删除
                        unset($cache[$k]);
                    }
                } else {
                    //下架删除
                    unset($cache[$k]);
                }
            }
        }
        if (empty($cache)) {
            return array('status' => 0, 'msg' => "没有可购买商品");
        }
        //将商品列表
        sort($cache);
        $allitems = serialize($cache);
        //商品数量 订单总价   订单商品
        $result = array(
            'totalnum' => $totalnum,
            'totalprice' => $totalprice,
            'allitems' => $allitems,
            'goodslist' => $cache,
        );
        return array('status' => 1, 'msg' => "计算价钱成功", 'result' => $result); // 返回结果状态
    }

//    //根据购物车下订单页
//    public function ordermakrpage()
//    {
//        //计算订单信息
//        $result = $this->calculate_price();
//        if ($result['status']) {
//            $totalprice = $result['result']['totalprice'];
//            $totalnum = $result['result']['totalnum'];//用于校验数据
//            $allitems = $result['result']['allitems'];
//            $cache = $result['result']['goodslist'];
//        } else {
//            $this->error($result['msg']);
//        }
//
//        //计算运费
//        $yf = 0;
//        foreach ($cache as $key => $value) {
//            $postage = db('Shop_goods')->where('good_id', $value['goodsid'])->value('postage');
//            if ($value['num'] > 1) {
//                $postage += $postage * ($value['num'] - 1) * 0.5;
//            }
//            $yf += $postage;
//        }
//
//        //实际支付价格
//        $payprice = $totalprice + $yf;
//
//        $data['totalprice'] = $totalprice;
//        $data['totalnum'] = $totalnum;
//        $data['payprice'] = $payprice;
//        $data['allitems'] = $allitems;
//        $data['goodslist'] = $cache;
//        $data['yf'] = $yf;
//
//        $this->success('', $data);
//    }

    //购物车全部或部分结算(确认提交订单)
    public function ordermake()
    {
        $user_id = $this->getUserId();
        $result = $this->calculate_price();
        if ($result['status']) {
            $totalprice = $result['result']['totalprice'];
            $totalnum = $result['result']['totalnum'];
            $cache = $result['result']['goodslist'];
        } else {
            $this->error($result['msg']);
        }

        $morder = db('Shop_order');
        $data = $this->request->param();

        //邮费逻辑
        $yf = 0;

        foreach ($cache as $key => $value) {
            $postage = db('Shop_goods')->where('id = ' . $value['goodsid'])->value('postage');
            if ($value['num'] > 1) {
                $postage += $postage * ($value['num'] - 1) * 0.5;
            }
            $yf += $postage;
        }

        $data['yf'] = $yf;

        if ($totalnum != $data['totalnum']) {
            $this->error('数据异常，请重新下单');
        }
        if ($totalprice != $data['totalprice']) {
            $this->error('数据异常，请重新下单');
        }
        if ($data['totalprice'] <= 0) {
            $this->error('金额异常');
        }

        if (!$data['address_id']) {
            $this->error('未选择收货地址！');
        }
        $address = db('user_address')->where('id', $data['address_id'])->find();
        $data['shouhuoaddress'] = $address['sheng'] . $address['shi'] . $address['xian'] . $address['address'];
        $data['shouhuomobile'] = $address['telphone'];
        $data['shouhuoren'] = $address['username'];

        $data['items'] = stripslashes(htmlspecialchars_decode($data['items']));
        $data['user_id'] = $this->userId;
        $data['is_true'] = 0;
        $data['state'] = 0;//订单成功，未付款
        $data['create_time'] = time();
        $data['payprice'] = $totalprice + $yf;
        $data['total_fee'] = $totalprice;

        Db::startTrans();

        $re = $morder->insertGetId($data);
        if (!$re) {
            Db::rollback();
            $this->error('订单生成失败！请重新尝试！');
        }
        $old = $morder->where('order_id', $re)->setField('oid', 'G' . date('YmdHis') . '-' . $re);
        if (!$old) {
            Db::rollback();
            $this->error('订单生成失败！请重新尝试！');
        }
        //后端日志
        $mlog = db('Shop_order_syslog');
        $dlog['oid'] = $re;
        $dlog['msg'] = '订单创建成功';
        $dlog['type'] = 1;
        $dlog['ctime'] = time();
        $mlog->insert($dlog);
        //清空购物车
        $res = db('Shop_basket')->where(array('user_id' => $data['user_id']))->delete();
        if (!$res) {
            Db::rollback();
            $this->error('订单生成失败！请重新尝试！');
        }

        Db::commit();

        $this->success('订单生成成功，请及时支付');
    }

    //$more为json
    private function getAlbum($more)
    {
        if (!is_array($more)) {
            $more = json_decode($more, true);
        }
        if (!$more || !isset($more['photos'])) {
            return false;
        }
        $cache = array();
        foreach ($more['photos'] as $v) {
            $cache[] = cmf_get_image_url($v['url']);
        }
        return $cache;
    }
}
