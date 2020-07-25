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

class ServiceSortModelSelfTaskSortModel extends Model
{

    protected $type = [
        'other' => 'array',
    ];

    public function adminAddTaskSort($data)
    {
        $data['other']['taskimg'] = isset($data['taskimg']) ? $data['taskimg'] : array();
        if(!empty($data['urlname'])){
            $urlarr = array();
            foreach($data['urlname'] as $k => $v){
                array_push($urlarr,['text'=>$v,'url'=>$data['urlurl'][$k]]);
            }
            $data['other']['urlarr'] = $urlarr;
        }
        $data['other']['hcontent'] = $data['hcontent'];
        $this->allowField(true)->isUpdate(false)->data($data, true)->save();
    }

    public function adminEditTaskSort($data)
    {
        $data['other']['taskimg'] = isset($data['taskimg']) ? $data['taskimg'] : array();
        if(!empty($data['urlname'])){
            $urlarr = array();
            foreach($data['urlname'] as $k => $v){
                array_push($urlarr,['text'=>$v,'url'=>$data['urlurl'][$k]]);
            }
            $data['other']['urlarr'] = $urlarr;
        }
        $data['other']['hcontent'] = isset($data['hcontent']) ? $data['hcontent'] : '';
        $this->allowField(true)->isUpdate(true)->data($data, true)->save();
    }
}