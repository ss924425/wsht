<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace api\home\controller;

use api\user\controller\VerificationController;
use think\Db;
use think\Validate;
use cmf\controller\RestBaseController;

class TopController extends RestBaseController
{
    /**
     * 获取榜单数据
     * @param type：1 任务榜，2 团队榜，3 收入榜
     * @return 返回格式json
     */
    public function getTopList()
    {
        $type = input('type');
        if (empty($type))
            $this->error('参数错误');
        $endLastweek = mktime(23, 59, 59, date('m'), date('d') - date('w') + 7 - 7, date('Y'));
        $weeks = date("YW", $endLastweek);
        $fieldone = 'r.uid as userid,r.money,r.numbertext num,u.agentId,u.pid,u.user_type,u.vip_type,u.user_nickname,u.truename,u.avatar,u.sex,u.user_money,u.deposit,u.income,u.yong_money,u.income';
        $dataone = db('rankinglist')->alias('r')
            ->join('user u', 'r.uid = u.id', 'left')
            ->where(['type' => $type, 'weeks' => $weeks])
            ->field($fieldone)
            ->order('top asc')
            ->limit(10)
            ->select()->each(function ($item) {
                $item['avatar'] = 'http://' . $_SERVER['HTTP_HOST'] . '/upload/' . $item['avatar'];
                return $item;
            })->toArray();
        if (empty($dataone)) {
            $this->error('暂无榜单数据');
            return true;
        }
        $this->success('查询完成', $dataone);
    }

    public function pmlist()
    {
        $type = input('type');
        $data = Db::name('rankinglist')->alias('r')
            ->join('user u','r.uid = u.id','left')
            ->where(['r.type' => $type])
            ->field('r.*,u.id,u.truename,u.avatar')
            ->order('weeks desc')
            ->order('numbertext desc')
            ->select()->each(function ($item) {
                $item['avatar'] = 'http://' . $_SERVER['HTTP_HOST'] . '/upload/' . $item['avatar'];
                return $item;
            })->toArray();
        if ($data){
            $this->success('查询完成', $data);
        }
    }


    function disuser()
    {
        $p = $this->request->param('p',1);
        $p = max($p, 1);
        $pagesize = 10;
        $start = ($p - 1) * $pagesize;

        $list = db('user')
            ->where('user_status',0)
            ->where('user_type','not in',[2,3])
            ->limit($start, $pagesize)
            ->field('id,user_login,user_nickname,credit_score,user_status,avatar')
            ->order('credit_score desc')
            ->select()->each(function ($item) {
                if ($item['avatar']) $item['avatar'] = 'http://' . $_SERVER['HTTP_HOST'] . '/upload/' . $item['avatar'];
                return $item;
            });
        if ($list){
            $this->success('查询完成', $list);
        }
    }

}
