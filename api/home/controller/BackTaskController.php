<?php

namespace api\home\controller;


use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;
use think\Log;

class BackTaskController extends Command
{
    protected function configure()
    {
        $this->setName('SelfTask')->setDescription("自动退单");
    }
    function back()
    {
        try {
            // 账号
            $accounts = db("account_list")->field('account_id,api_key')->select()->toArray();
            $rows = [];
            foreach ($accounts as $v){
                $url = "http://by.conglin178.com/admin_jiuwuxiaohun.php?m=home&c=api&a=down_orders&format=json&apikey=" . $v['api_key'] . "&goods_id=" . $v['account_id'] . "&state=tdz&page_size=10000";
                $rows[] = self::http_curl($url)['rows'];
            }
            $newarr = array_filter($rows);
            Db::startTrans();
            if (!empty($newarr)){
                foreach ($newarr as $item){
                    foreach ($item as $vv){
                        db("self_task")->where(['orderid' => $vv['id']])->setField('cl_is_back',1);
                    }
                }
            }
            Db::commit();
            Log::info('' . '成功执行事务');
        } catch (\Exception $e) {
            Db::rollback();
            Log::error($e->getMessage());
        }
    }

    static function http_curl($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $output = curl_exec($ch);
        curl_close($ch);
        return json_decode($output, true);
    }
}