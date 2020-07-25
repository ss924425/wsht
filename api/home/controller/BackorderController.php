<?php

namespace api\home\controller;


use app\admin\controller\DouyinUrlController;
use think\console\Command;
use think\Db;
use think\Log;

class BackorderController extends Command
{
    protected function configure()
    {
        $this->setName('Backorder')->setDescription("自动下架任务");
    }
    const URL = "http://by.conglin178.com/admin_jiuwuxiaohun.php?m=home&c=api&a=down_orders&format=json&apikey=";
    const KEY = 'putian';
    const GOODSID = '42664';
    function backorder()
    {
        try {
            /***获取需要处理的数据 ***/
            $orderurl = self::URL . self::KEY . "&goods_id=" . self::GOODSID . "&state=tdz&page_size=10000&page=1";
            $res = self::http_request($orderurl);
            $res = json_decode($res,true);
            if (empty($res)) Log::info('暂无数据');

            Db::startTrans();
            foreach ($res['rows'] as $k=>$v){
                $bool = db('self_task')->where(['orderid' => $v['id'],'order_aa'=>$v['aa']])->setField('status',2);
                if ($bool){
                    // 下架之后将订单改为完成
                    self::editorder($v);
                }
            }
            Db::commit();
            Log::info('' . '成功执行事务');
        } catch (\Exception $e) {
            Db::rollback();
            Log::error($e->getMessage());
        }
    }

    //任务没做完的中途退单 完成退单后状态改为已退单
    static function editorder($order)
    {
        $orderid = $order['id'];
        $order_aa = $order['aa'];

        if (strlen($order_aa) == 19){
            $nowdata = DouyinUrlController::RmD($order_aa);
        }

        if (strlen($order_aa) > 19){
            $aa = DouyinUrlController::aa($order_aa);
            $nowdata = DouyinUrlController::RmD($aa);
        }
        $nowdata = json_decode($nowdata,true);

        $now_num = $nowdata['zan'];

        $url = "http://by.conglin178.com/admin_jiuwuxiaohun.php?m=Admin&c=Api&a=refund&apikey=" . self::KEY . "&order_id=" . $orderid . "&order_state=ytd" . "&now_num=" . $now_num;


        db('self_task')->where('orderid',$orderid)->setField('end_num',$now_num);  //修改任务结束量

        $res = self::http_curl($url);
        if ($res) {
            // 访问接口日志
            $clurllog['orderid'] = $orderid;
            $clurllog['order_aa'] = $order_aa;
            $clurllog['num'] = $now_num;
            $clurllog['url'] = $url;
            $clurllog['create_time'] = time();
            db('cl_url_log')->insert($clurllog);
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

    static function http_request($url, $data = null, $header = null)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_HTTPGET, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
        }
        curl_setopt($curl, CURLOPT_USERAGENT, $header);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        $output = curl_exec($curl);

        curl_close($curl);
        return $output;
    }

}