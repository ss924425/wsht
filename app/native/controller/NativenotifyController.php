<?php

namespace app\native\controller;

class NativenotifyController extends WxPayNotifyController
{
    function notify()
    {
        $config = new WxPayConfigController();
        $notify = new self();
        $notify->Handle($config, true);
    }

    function taskusernotify()
    {
        $config = new WxPayConfigController();
        $notify = new self();
        $notify->taskuserHandle($config, true);
    }
}






