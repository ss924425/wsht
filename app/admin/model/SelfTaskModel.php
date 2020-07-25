<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 老猫 <thinkcmf@126.com>
// +----------------------------------------------------------------------
namespace app\admin\model;

use think\Model;

class SelfTaskModel extends Model
{

    protected $type = [
        'link' => 'array',
    ];

    static function deleteTaskImg($id, $type = 'task')
    {
        set_time_limit(0);

        if ($type == 'task') {
            if (is_array($id)) {
                $task = $id;
            } else {
                $task = db('self_task')->where(array('id' => $id))->find();
            }

            if (empty($task)) return false;

            $images = json_decode($task['images'], true);
            if (!empty($images)) {
                foreach ($images as $v) {
                    \TbUtil::deleteImage($v);
                }
            }

            $taked = db('self_task_receive')->where('taskid', $task['id'])->select()->toArray();

            if (!empty($taked)) {
                foreach ($taked as $v) {
                    $images = json_decode($v['images'], true);
                    if (!empty($images)) {
                        foreach ($images as $vv) {
                            \TbUtil::deleteImage($vv);
                        }
                    }

                    // 补充内容
                    $addlist = db('self_task_remindlog')->where(array('takedid' => $v['id']))->select()->toArray();
                    if (!empty($addlist)) {
                        foreach ($addlist as $vv) {
                            if (!empty($vv['images'])) $images = json_decode($vv['images'], true);
                            if (!empty($images) && is_array($images)) {
                                foreach ($images as $vvv) {
                                    \TbUtil::deleteImage($vvv);
                                }
                            }
                        }
                    }

                }
            }

        }
    }
}