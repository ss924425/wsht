<?php

namespace app\native\controller;

/**
 *
 * 统一下单输入对象
 * @author widyhu
 *
 */
/**
 *
 * 接口调用结果类
 * @author widyhu
 *
 */
class WxPayNotifyResultsController extends WxPayResultsController{
    /**
     * 将xml转为array
     * @param WxPayConfigInterface $config
     * @param string $xml
     * @return WxPayNotifyResults
     * @throws WxPayException
     */
    public static function Init($config, $xml)
    {
        $obj = new self();
        $obj->FromXml($xml);
        //失败则直接返回失败
        $bool = $obj->CheckSign($config);
        return $bool;
    }
}
