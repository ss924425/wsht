<?php

namespace app\admin\model;

use think\Model;

class ShopOrderModel extends Model
{
    protected $autoWriteTimestamp = true;

    public function getIsTrueAttr($value)
    {
        $res = '';
        switch ($value) {
            case 0:
                $res = "未支付";
                break;
            case 1:
                $res = "已支付";
                break;
        }
        return $res;
    }

    public function adminOrderList($filter)
    {
        $where = [
            'a.create_time' => ['>=', 0],
        ];

        $field = 'a.*';

        $startTime = empty($filter['start_time']) ? 0 : strtotime($filter['start_time']);
        $endTime = empty($filter['end_time']) ? 0 : strtotime($filter['end_time']);
        if (!empty($startTime) && !empty($endTime)) {
            $where['a.create_time'] = [['>= time', $startTime], ['<= time', $endTime]];
        } else {
            if (!empty($startTime)) {
                $where['a.create_time'] = ['>= time', $startTime];
            }
            if (!empty($endTime)) {
                $where['a.create_time'] = ['<= time', $endTime];
            }
        }

        $order_sn = empty($filter['order_sn']) ? '' : $filter['order_sn'];
        if (!empty($order_sn)) {
            $where['a.order_sn'] = $order_sn;
        }

        $order_sn = empty($filter['order_sn']) ? '' : $filter['order_sn'];
        if (!empty($order_sn)) {
            $where['a.order_sn'] = $order_sn;
        }
        if (isset($filter['is_true']) && $filter['is_true'] != '') {
            $is_true = $filter['is_true'] ? 1 : 0;
            $where['a.is_true'] = $is_true;
        }

        if (isset($filter['state']) && $filter['state'] != '') {
            $where['a.state'] = $filter['state'];
        }

        $orders = $this->alias('a')->field($field)
            ->where($where)
            ->order('a.order_id', 'DESC')
            ->paginate(20)
            ->each(function ($item, $key) {
                if ($item['items']) {
                    $item['items'] = unserialize($item['items']);
                }
                return $item;
            });

        $join = [
            ['__USER__ u', 'a.user_id=u.id', 'left']
        ];
        $field = "a.order_id,a.order_sn,u.mobile,a.payprice,a.is_true,a.state,a.create_time";
        $sql = $this->alias('a')->field($field)
            ->where($where)
            ->join($join)
            ->order('order_id', 'DESC')
            ->fetchSql(true)
            ->select();
        session('shoporder', $sql);
        return $orders;
    }
}