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

class SmsController extends RestBaseController
{
    //活动短信
    public function sms_info()
    {
        $data = ['15638923259','17398953710','15596182919','18937690211'];
        //短信内容
        $message = '尉氏后台APP最新福利，普通会员 也可以抢任务做任务啦，赚零花钱真的很轻松，赶紧下载尉氏后台体验吧！下载链接:http://download.mayipai1616.com';

        foreach($data as $k => $v){
//            $result = cmf_send_sms($data['mobile'],$message);
            $result = cmf_send_sms($v,$message);
            if ($result['error']) {
//                $this->error($result['message']);
                $aa[$k] = $v;
            } else {
//                $this->success('短信发送成功!');
                $bb[$k] = $v;
            }
        }

        var_dump($aa);
        var_dump($bb);

    }

}
