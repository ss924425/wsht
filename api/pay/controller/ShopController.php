<?php

namespace api\pay\controller;

use cmf\controller\RestBaseController;
use api\home\model\NewsModel;
use think\Controller;
use think\Db;

class ShopController extends RestBaseController
{
    public function paybag()
    {
        $param = $this->request->param();
        $orderid = input('orderid');
        if (!$orderid) {
            $this->error('支付参数不完整');
        }
        //取ORDER
        $cache = db('Shop_order')->where(array('order_id' => $orderid))->find();
        if (!$cache) {
            $this->error('此订单不存在！');
        }
        if ($cache['is_true'] || $cache['state'] != 0) {
            $this->error('此订单已被使用！');
        }
        $userinfo = db('user')->where('id', $cache['user_id'])->find();
        $payPass = request()->param('payPass');
        if (empty($userinfo['pay_pass'])) {
            $this->error(['code' => 10007, 'msg' => $this->standard_code['10007']]);
        }
        if (cmf_password($payPass) != $userinfo['pay_pass']) {
            $this->error('支付密码错误');
        }

        if ($userinfo['user_money'] <= 0) {
            $this->error(['code'=>10004,'msg'=>$this->standard_code['10004']]);
        }
        //库存检测，不够的重新下单
        $res = $this->check_kucun($orderid);
        if ($res !== true) {
            $this->error($res['msg']);
        }

        Db::startTrans();

        if ($userinfo['user_money'] < $cache['payprice']) {
            Db::rollback();
            $this->error(['code'=>10004,'msg'=>$this->standard_code['10004']]);
        }
        $res = db('user')->where('id', $cache['user_id'])->setDec('user_money', $cache['payprice']);
        //减余额影响行数一定大于零，否则就有问题
        if (!$res) {
            Db::rollback();
            $this->error('支付错误，请稍后重试');
        }

        if (empty($param['shouhuoaddress']) || empty($param['shouhuomobile']) || empty($param['shouhuoren'])) {
            Db::rollback();
            $this->error('请选择收货信息');
        }

        //修改状态
        $data['order_id'] = $orderid;
        $data['shouhuoren'] = $param['shouhuoren'];
        $data['shouhuomobile'] = $param['shouhuomobile'];
        $data['shouhuoaddress'] = $param['shouhuoaddress'];
        $data['is_true'] = 1;
        $data['state'] = 1;
        $data['paytime'] = time();
        $re = db('shop_order')->update($data);
        if ($re === false) {
            Db::rollback();
            $this->error('支付失败，请稍后重试');
        }

        //销量计算-只减不增
        $this->doSells($cache);

        //资金流水记录
//        $mlog = db('user_balance_log');
//        $flow['user_id'] = $cache['user_id'];
//        $flow['change'] = $cache['payprice'];
//        $flow['balance'] = $userinfo['user_money'] - $cache['payprice'];
//        $flow['create_time'] = time();
//        $tmpitems = unserialize($cache['items']);
//        $flow['remark'] = isset($tmpitems[0]) ? ($cache['totalnum']>1 ? '购买商品：'.$tmpitems[0]['name'].'等'.$cache['totalnum'].'件' : '购买商品：'.$tmpitems[0]['name']) : '';
//        $rflow = $mlog->insert($flow);

        $mlog = db('user_money_log');
        $flow['user_id'] = $cache['user_id'];
        $flow['sid'] = $orderid;
        $flow['create_time'] = time();
        $flow['coin'] = $cache['payprice'];
        $flow['type'] = 1;
        $flow['status'] = 0;
        $flow['channel'] = 4;
        $flow['user_money'] = $userinfo['user_money'] - $cache['payprice'];
        $flow['log_type'] = -5;
        $tmpitems = unserialize($cache['items']);
        $flow['notes'] = isset($tmpitems[0]) ? ($cache['totalnum'] > 1 ? '购买商品：' . $tmpitems[0]['name'] . '等' . $cache['totalnum'] . '件' : '购买商品：' . $tmpitems[0]['name']) : '';
        $rflow = $mlog->insert($flow);

        if($cache['user_id']){
            $model_news = new NewsModel();
            $news = "您已支付{$tmpitems[0]['name']}等,等待发货中";
            $type = 3;
            $model_news::toUserNews($cache['user_id'], $news, $type);
        }

        Db::commit();
        $this->success('支付成功，请耐心等待发货');
    }


    //销量计算
    private function doSells($order)
    {
        $mgoods = db('Shop_goods');
        $msku = db('Shop_goods_sku');
        $items = unserialize($order['items']);

        foreach ($items as $k => $v) {
            //销售总量
            $dnum = $dlog['num'] = $v['num'];
            if ($v['skuid']) {
                $rg = $mgoods->where('good_id', $v['goodsid'])->setDec('num', $dnum);
                $rg = $mgoods->where('good_id', $v['goodsid'])->setInc('sells', $dnum);
                $rs = $msku->where('id=' . $v['skuid'])->setDec('num', $dnum);
                $rs = $msku->where('id=' . $v['skuid'])->setInc('sells', $dnum);
            } else {
                $rg = $mgoods->where('good_id', $v['goodsid'])->setDec('num', $dnum);
                $rg = $mgoods->where('good_id', $v['goodsid'])->setInc('sells', $dnum);
                $rg = $mgoods->where('good_id', $v['goodsid'])->setInc('dissells', $dnum);
            }
        }
        return true;
    }


    //订单库存检测
    private function check_kucun($oid)
    {
        $m = db('shop_order');
        $mgoods = db('Shop_goods');
        $msku = db('Shop_goods_sku');
        $data = $m->where(array('order_id' => $oid))->find();

        $items = unserialize($data['items']);
        foreach ($items as $k => $v) {
            $goods = $mgoods->where('good_id', $v['goodsid'])->find();
            if ($v['sku']) {
                $sku = $msku->where(array('sku' => $v['sku']))->find();
                if ($sku && $sku['status'] && $goods && $goods['issku'] && $goods['is_true']) {
                    $nownum = $v['num'];//该商品该sku买几个
                    if ($sku['num'] - $nownum < 0) {//库存不够
                        return ['code' => 0, 'msg' => '存在已下架或库存不足商品，请重新下单!'];
                    }
                } else {
                    return ['code' => 0, 'msg' => '存在已下架或库存不足商品，请重新下单!'];
                }
            } else {
                if ($goods && $goods['is_true']) {
                    $nownum = $v['num'];
                    if ($goods['num'] - $nownum < 0) {
                        return ['code' => 0, 'msg' => '存在已下架或库存不足商品，请重新下单!'];
                    }
                } else {
                    $this->error('存在已下架或库存不足商品，请重新下单！');
                }
            }
        }
        return true;
    }
}