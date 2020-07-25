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

use app\admin\model\RouteModel;
use think\Validate;

class SettingUserValidate extends Validate
{
    protected $rule = [
    ];

    protected $message = [
    ];


    // 自定义验证规则
    protected function myfunc($value, $rule, $data)
    {

    }
}