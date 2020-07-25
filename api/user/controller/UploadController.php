<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace api\user\controller;

use cmf\controller\RestUserBaseController;
use think\Db;

class UploadController extends RestUserBaseController
{
    // 用户头像修改
    public function one()
    {
        $id = request()->param('id');
        $file = request()->file('file');
        // 移动到框架应用根目录/public/upload/ 目录下
        $info     = $file->validate([
            'ext' => 'jpg,png,gif'
        ]);
        $info = $info->move(ROOT_PATH . 'public' . DS . 'upload' . DS . 'avatar');
        if ($info) {
            $saveName     = $info->getSaveName();
            Db::name('user')->where('id', $id)->update(['avatar' => 'avatar/'.$saveName]);
            $this->success("修改成功!");
        } else {
            // 上传失败获取错误信息
            $this->error($file->getError());
        }
    }
}
