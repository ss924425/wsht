<?php
// +----------------------------------------------------------------------
// | ThinkCdbF [ WE CAN DO IT dbORE SIdbPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 老猫 <thinkcmf@126.com>
// +----------------------------------------------------------------------
namespace app\admin\model;

use think\Model;

class ShopGoodsModel extends Model
{
    protected $type = [
        'more' => 'array',
    ];

    function goods_list($type)
    {
        $where = array();
        switch ($type) {
            case 'best':
                $where['best'] = 1;
                break;
            case 'hot':
                $where['hot'] = 1;
                break;
            case 'new':
                $where['new'] = 1;
                break;
        }
        $where['is_true'] = 1;
        $res = $this->where($where)->order("code desc")->select()->toArray();
        $good_pic = db('good_pic');
        foreach ($res as $k => $v) {
            $good_id = $v['good_id'];
            $res[$k]['pic_url'] = $good_pic->where("good_id = '$v[good_id]'")->order('code desc')->select()->toArray();
        }
        return $res;
    }

    function get_one($good_id)
    {
        $res = $this->where("good_id='$good_id'")->find();
        $res['good_pic'] = db('good_pic')->where("good_id='$good_id'")->order("code desc")->select()->toArray();
        return $res;
    }

    function get_category_num($user_id)
    {
        $num = db('shop_order_temp')->where(" user_id = '$user_id' ")->sum('good_num');
        return $num;
    }

    function get_good_type($type_id)
    {
        $spec_info = db('shop_spec')->where("type_id='$type_id'")->order("spec_id desc")->select()->toArray();
        foreach ($spec_info as $k => $v) {
            $spec_info[$k]['info'] = explode(',', $v['value']);
        }
        return $spec_info;
    }

    function save_good_temp($good_id, $user_id, $type = '')
    {
        $shop_order_temp = db('shop_order_temp');
        $res = $shop_order_temp->where(array("good_id" => $good_id, "user_id" => $user_id))->find();
        if ($res == null) {
            $data = array('good_id' => $good_id, 'user_id' => $user_id);
            if ($type != '') {
                $data['type'] = $type;
            }
            $result = $shop_order_temp->insert($data);
        } else {
            if ($type == $res['type']) {
                $order_id = $res['order_id'];
                $result = $shop_order_temp->where(" order_id = '$order_id' ")->setInc('good_num');
            } else {
                $data = array('good_id' => $good_id, 'user_id' => $user_id);
                if ($type != '') {
                    $data['type'] = $type;
                }
                $result = $shop_order_temp->insert($data);
            }
        }
        return $result;
    }

    public function adminAddGood($data)
    {
        if (!empty($data['more']['thumbnail'])) {
            $data['more']['thumbnail'] = cmf_asset_relative_url($data['more']['thumbnail']);
            $data['thumbnail']         = $data['more']['thumbnail'];
        }

        $this->allowField(true)->data($data, true)->isUpdate(false)->save();
        return $this;
    }

    public function adminEditGood($data)
    {
        if (!empty($data['more']['thumbnail'])) {
            $data['more']['thumbnail'] = cmf_asset_relative_url($data['more']['thumbnail']);
            $data['thumbnail']         = $data['more']['thumbnail'];
        }

        $this->allowField(true)->data($data, true)->isUpdate(true)->save();
        return $this;
    }
}