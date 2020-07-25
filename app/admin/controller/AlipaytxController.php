<?php

namespace app\admin\controller;
use cmf\controller\AdminBaseController;
use think\Db;
use think\Validate;
use Sms;



class AlipaytxController extends AdminBaseController
{

    public function transfer()
    {
        if ($this->request->isAjax()){
            $id = $this->request->param('id');
            $cashInfo = db("cash")->alias('c')
                ->join('user u','c.uid = u.id','left')
                ->where('c.id',$id)
                ->field('c.*,u.apply_name,u.apply_account,u.mobile')
                ->find();
            if (!$cashInfo){
                $info = [
                    'code' => 0,
                    'msg' => '未找到信息！'
                ];
                return json($info);
            } else {
                if (empty($cashInfo['apply_account']) ||
                    empty($cashInfo['apply_name']) ||
                    empty($cashInfo['mobile']) ||
                    empty($cashInfo['cash_money']) ||
                    $cashInfo['cash_status'] != 0) {
                    $info = [
                        'code' => 0,
                        'msg' => '参数错误！'
                    ];
                    return json($info);
                    exit;
                }
                $ali = new AlipayTransfer();
                $order_number = 'TX'.md5(time());
                $pay_no = $cashInfo['apply_account'];
                $pay_name = $cashInfo['apply_name'];
                $amount = $cashInfo['cash_money'];
                $memo = '[尉氏后台] 提现到账,金额'.$cashInfo['cash_money'];

                // 用户信息
                $userinfo = db('user')->where('id',$cashInfo['uid'])->find();

                /* 转账 */
                $res = $ali->transfer($order_number, $pay_no, $pay_name, $amount, $memo);
                if ($res === true) {
                    $data['cash_status'] = 1;
                    $data['handle_time'] = time();
                    $data['cash_notes'] = '自动提现成功';
                    $data['handle_id'] = cmf_get_current_admin_id();
                    $re = db('cash')->where('id', $id)->update($data);
                    if ($re) {
                        /* 发送短信 Start */
                        $sms = new Sms;
                        $content = '您的支付宝提现申请已同意，请注意查收';
                        $mobile = $cashInfo['mobile'];
                        $sms->send($mobile, $content);
                        /* 发送短信 end */

                        /* 会员提现记录 Start */
                        if ($cashInfo['cash_type'] == 1) {
                            $logData['user_money'] = $userinfo['user_money'];
                            $logData['cash_type'] = 1;
                            $logData['notes'] = '提现余额：￥' . $cashInfo['cash_money'] . "！提现方式：支付宝！，当前账户余额:￥" . $logData['user_money'] . '，如有异常请联系客服！';
                        } elseif ($cashInfo['cash_type'] == 2) {
                            $logData['deposit'] = $userinfo['deposit'];
                            $logData['cash_type'] = 2;
                            $logData['notes'] = '提现保证金：￥' . $cashInfo['cash_money'] . "！提现方式：支付宝！，当前账户保证金余额:￥" . $logData['deposit'] . '，如有异常请联系客服！';
                        } elseif ($cashInfo['cash_type'] == 3) {
                            $logData['income'] = $userinfo['income'];
                            $logData['cash_type'] = 3;
                            $logData['notes'] = '提现收入余额：￥' . $cashInfo['cash_money'] . "！提现方式：支付宝！，当前账户收入余额:￥" . $logData['income'] . '，如有异常请联系客服！';
                        }
                        $logData['cash_money'] = $cashInfo['cash_money'];
                        $logData['user_id'] = $cashInfo['uid'];
                        $logData['coin'] = $cashInfo['cash_money'];
                        $logData['mobile'] = $userinfo['mobile'];
                        $logData['apply_account'] = $cashInfo['apply_account'];
                        $logData['apply_name'] = $cashInfo['apply_name'];
                        $logData['status'] = 1;
                        $logData['add_time'] = time();
                        db('cash_log')->insert($logData);
                        /* 会员提现记录 end */
                        $info = [
                            'code' => 1,
                            'msg' => '审核通过'
                        ];
                        return json($info);
                    }
                } else {
                    $data['cash_status'] = 3;  // 失败
                    $data['handle_time'] = time();
                    $data['cash_notes'] = $res;
                    $re = db('cash')->where('id', $id)->update($data);
                    if ($re) {
                        if ($cashInfo['cash_type'] == 1) {
                            $newMoney['user_money'] = $userinfo['user_money'] + $cashInfo['cash_money'] + $cashInfo['procedures'];
                            db('user')->where('id', $cashInfo['uid'])->update($newMoney);
                        } elseif ($cashInfo['cash_type'] == 2) {
                            $newMoney['deposit'] = $userinfo['deposit'] + $cashInfo['cash_money'] + $cashInfo['procedures'];
                            db('user')->where('id', $cashInfo['uid'])->update($newMoney);
                        } elseif ($cashInfo['cash_type'] == 3) {
                            $newMoney['income'] = $userinfo['income'] + $cashInfo['cash_money'] + $cashInfo['procedures'];
                            db('user')->where('id', $cashInfo['uid'])->update($newMoney);
                        }
                        /* 添加流水记录 Start */
                        $moneylog['user_id'] = $userinfo['id'];
                        $moneylog['cid'] = $cashInfo['id'];
                        $moneylog['type'] = 0;
                        $moneylog['coin'] = $cashInfo['cash_money'] + $cashInfo['procedures'];
                        $moneylog['user_money'] = $cashInfo['cash_money'] + $cashInfo['procedures'] + $userinfo['user_money'];
                        $moneylog['deposit'] = $cashInfo['cash_money'] + $cashInfo['procedures'] + $userinfo['deposit'];
                        $moneylog['income'] = $cashInfo['cash_money'] + $cashInfo['procedures'] + $userinfo['income'];
                        $moneylog['log_type'] = 5;  //提现自动转账失败
                        $moneylog['notes'] = '支付宝提现失败';
                        $moneylog['create_time'] = time();

                        db('user_money_log')->insert($moneylog);
                        /* 添加流水记录 end */
                    }
                    $info = [
                        'code' => 0,
                        'msg' => $res
                    ];
                    return json($info);
                }
            }
        } else {
            $this->error('非法请求');
        }
    }
}
