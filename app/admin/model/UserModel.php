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

class UserModel extends Model
{

    protected $type = [
        'more' => 'array',
    ];

    protected $fenxiao_info = [];

    public function __construct()
    {
        parent::__construct();
        $this->fenxiao_info = cmf_get_option('upgrade_setting');
//        $this->fenxiao_info['fx_viptype_list'] = array($this->fenxiao_info['first_per_type'],$this->fenxiao_info['second_per_type']);
    }

    public function get_user_info_by_user_id($user_id)
    {
        $res = $this->find($user_id);
        switch ($res['vip_type']) {
            case 1:
                $res['user_type_name'] = '普通会员';
                break;
            case 2:
                $res['user_type_name'] = 'VIP会员';
                break;
            case 3:
                $res['user_type_name'] = '代理商';
                break;
            case 4:
                $res['user_type_name'] = '股东';
                break;
        }
        return $res->toArray();
    }

    public function get_team_num($user_id)
    {
        $fenxiao_info = $this->fenxiao_info;
        $fenxiao_level = $fenxiao_info['fenxiao_level'];
        $num = $this->each_team($user_id, $fenxiao_level);
        return $num;
    }

    //获取总数
    function each_team($user_id, $level, $i = 0, $num = 0)
    {
        $level_info = $this->where("pid = '$user_id'")->select();

        $level_info = $level_info->toArray();
        if ($i == $level) {
            return $num;
        }
        if ($level_info == null) {
            return $num;
        } else {
            $i++;
            $num = count($level_info) + $num;
            foreach ($level_info as $v) {
                $num = $this->each_team($v['id'], $level, $i, $num);
            }
            return $num;
        }
    }

    function get_team_level($user_id, $level, $i = 1)
    {
        $level_info = $this->where("pid='$user_id'")->select()->each(function ($item, $key) {
            switch ($item['vip_type']) {
                case 1:
                    $item['user_type_name'] = '普通会员';
                    break;
                case 2:
                    $item['user_type_name'] = 'VIP会员';
                    break;
                case 3:
                    $item['user_type_name'] = '代理商';
                    break;
                case 4:
                    $item['user_type_name'] = '股东';
                    break;
            }
            return $item;
        });
        $level_info = $level_info->toArray();

        if ($level_info == null) {
            return array();
        } else {
            if ($level == $i) {
                return $level_info;
            } else {
                $i++;
                $arr = array();
                foreach ($level_info as $k => $v) {
                    $arr1 = $this->get_team_level($v['id'], $level, $i);
                    $arr = array_merge($arr, $arr1);
                }
                return $arr;
            }
        }
    }
}