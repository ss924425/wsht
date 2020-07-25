<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 老猫 <thinkcmf@126.com>
// +----------------------------------------------------------------------
namespace app\admin\model;

use think\Model;

class ServiceSortModel extends Model
{

    protected $type = [
        'other' => 'array',
    ];

    public function adminAddServiceSort($data)
    {
        $this->allowField(true)->isUpdate(false)->data($data, true)->save();
    }

    public function adminEditServiceSort($data)
    {
        $this->allowField(true)->isUpdate(true)->data($data, true)->save();
    }
}