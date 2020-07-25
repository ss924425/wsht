<?php

namespace app\native\controller;
/**
 * 
 * 回调基础类
 * @author widyhu
 *
 */
class WxPayNotifyController extends WxPayNotifyReplyController
{
	private $config = null;
	/**
	 * 
	 * 回调入口
	 * @param bool $needSign  是否需要签名返回
	 */
	final public function Handle($config, $needSign = true)
	{
		$this->config = $config;
		$msg = "OK";
		//当返回false的时候，表示notify中调用NotifyCallBack回调失败获取签名校验失败，此时直接回复失败
		$result = WxPayApiController::notify($config, array($this, 'NotifyCallBack'), $msg);

		if($result['result'] == false){
			$this->SetReturn_code("FAIL");
			$this->SetReturn_msg($msg);
			return;
		} else {
			//该分支在成功回调到NotifyCallBack方法，处理完成之后流程
			$this->SetReturn_code("SUCCESS");
			$this->SetReturn_msg("OK");

			// 加钱
            $order = db("clczorder_log")->where('out_trade_no',$result['order']['out_trade_no'])->find();
            if ($order){
                if($order['is_true'] == 0){
                    db("user")->where('id',$order['user_id'])->setInc('cl_money',$order['WIDtotal_amount']);
                }
                db("clczorder_log")->where('out_trade_no',$result['order']['out_trade_no'])->setField('is_true',1);
            }
		}

	}


    final public function taskuserHandle($config, $needSign = true)
    {
        $this->config = $config;
        $msg = "OK";
        //当返回false的时候，表示notify中调用NotifyCallBack回调失败获取签名校验失败，此时直接回复失败
        $result = WxPayApiController::notify($config, array($this, 'NotifyCallBack'), $msg);

        if($result['result'] == false){
            $this->SetReturn_code("FAIL");
            $this->SetReturn_msg($msg);
            return;
        } else {
            //该分支在成功回调到NotifyCallBack方法，处理完成之后流程
            $this->SetReturn_code("SUCCESS");
            $this->SetReturn_msg("OK");

            // 加钱
            $order = db("task_user_order")->where('out_trade_no',$result['order']['out_trade_no'])->find();
            if ($order){
                if($order['is_true'] == 0){
                    if ($order['type'] == 1){ //余额充值
                        $res = db("user")->where('id',$order['user_id'])->setInc('user_money',$order['WIDtotal_amount']);
                        if ($res){
                            $newdata = db('user')->where('id',$order['user_id'])->field('user_money,deposit,income')->find();
                            $logdata = [
                                'user_id' => $order['user_id'],
                                'coin' => $order['WIDtotal_amount'],
                                'channel' => 66, //任务用户充钱
                                'user_money' => $newdata['user_money'],
                                'deposit' => $newdata['deposit'],
                                'income' => $newdata['income'],
                                'create_time' => time(),
                                'notes' => '任务用户充值余额'.$order['WIDtotal_amount']
                            ];
                            db('user_money_log')->insert($logdata);
                        }
                        db("task_user_order")->where('out_trade_no',$result['order']['out_trade_no'])->setField('is_true',1);
                    } elseif ($order['type'] ==2){
                        $res1 = db("user")->where('id',$order['user_id'])->setInc('deposit',$order['WIDtotal_amount']);
                        if ($res1){
                            $newdata1 = db('user')->where('id',$order['user_id'])->field('user_money,deposit,income')->find();
                            $logdata1 = [
                                'user_id' => $order['user_id'],
                                'coin' => $order['WIDtotal_amount'],
                                'channel' => 67, //任务用户充钱
                                'user_money' => $newdata1['user_money'],
                                'deposit' => $newdata1['deposit'],
                                'income' => $newdata1['income'],
                                'create_time' => time(),
                                'notes' => '任务用户充值保证金'.$order['WIDtotal_amount']
                            ];
                            db('user_money_log')->insert($logdata1);
                        }
                        db("task_user_order")->where('out_trade_no',$result['order']['out_trade_no'])->setField('is_true',1);
                    }
                }
            }
        }
    }
}