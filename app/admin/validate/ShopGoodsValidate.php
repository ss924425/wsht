<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 小夏 < 449134904@qq.com>
// +----------------------------------------------------------------------
namespace app\admin\validate;

use think\Validate;

class ShopGoodsValidate extends Validate
{
    protected $rule = [
        'good_name' => 'require|min:2|max:50',
        'good_price'  => 'require',
    ];
    protected $message = [
        'good_name.require' => '商品名称不能为空',
        'good_name.min'  => '商品名称最小长度为3',
        'good_name.max'  => '商品名称最大长度为16',
        'good_price.require'  => '商品售价不能为空',
    ];

    protected $scene = [
        'add'  => ['good_name', 'good_price'],
        'edit' => ['good_name', 'good_price'],
    ];
}