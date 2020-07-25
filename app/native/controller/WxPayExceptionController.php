<?php
namespace app\native\controller;
use think\Exception;

/**
 * 
 * 微信支付API异常类
 * @author widyhu
 *
 */
class WxPayExceptionController extends Exception {
	public function errorMessage()
	{
		return $this->getMessage();
	}
}
