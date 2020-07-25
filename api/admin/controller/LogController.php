<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace api\admin\controller;

use cmf\controller\RestBaseController;
use think\Db;
use think\Validate;

class LogController extends RestBaseController
{
    /**
     * 会员操作日志
     * @uid         会员id
     * @path        操作路径
     * @incubator   设备类型
     * @url_id      请求中的id参数
     */
    public function user_operation_log()
    {
        $request = $this->request->param();

        $syslog['user_id'] = $request['uid'];
//      $syslog['path_info'] = $_SERVER['PATH_INFO'];
        $syslog['path_info'] = $request['path'];
        $syslog['time'] = date('Y-m-d H:i:s',time());
        $syslog['incubator'] = $request['incubator'];
        $syslog['url_id'] = $request['url_id'];

        //用户操作记录表
        $res = db('user_syslog') -> insert($syslog);
        if($res){
            $this -> success('成功');
        }else{
            $this -> error('失败');
        }
    }
}
