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

class TimingController extends RestBaseController
{
    //任务库存监控
    public function task_inventory_control()
    {
        $task = db('task_branch')-> alias('A')
            -> join('mc_task B','A.tid = B.id')
            -> where(['A.b_isdelete' => 0,'B.isdelete' => 0,'B.invalid' => 0])
            -> whereTime('create_time', 'today')
            -> field('A.id,A.b_title,A.quantity,A.tid,B.title')
            -> select() -> toArray();

        foreach($task as $k => $v){
            $task[$k]['use_task'] =$use_task = db('task_receive') -> where(['bid' => $v['id']]) -> count();
            if(($v['quantity'] * 0.2) > $use_task){
                unset($task[$k]);
            }
        }
        //处理查询出来的数据，编辑成消息发送至指定邮箱
        if($task){
            $text = [];
            foreach($task as $k => $v){
                $text[] = $v['title'].'('.$v['tid'].'-'.$v['id'].')';
            }
            $text = implode(',',array_unique($text));
            $text = str_replace(',','<br>',$text);
            //----------------------------------------------------------------------------------------------------------
            //收件人邮箱
            $user_setting = cmf_get_option('user_setting');
            $toemail = $user_setting['notice_email'];
            $subject='尉氏后台库存不足20%的任务提醒';
            $content='<h1>以下任务库存已不足20%</h1>'.$text.'<h2>请及时处理</h2>';
            $this -> send_mail($toemail,$subject,$content);
        }
    }

}
