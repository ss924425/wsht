<?php
// +----------------------------------------------------------------------
// | www.umeng123.com 2018
// +----------------------------------------------------------------------
// | Author: SL <admin@umeng123.com>
// +----------------------------------------------------------------------
return [
    'path' => [// 在后台插件配置表单中的键名 ,会是config[custom_config]，这个键值很特殊，是自定义插件配置的开关
        'title' => '备份路径', // 表单的label标题
        'type'  => 'text',// 表单的类型：text,password,textarea,checkbox,radio,select等
        'value' => './Data/',// 如果值为1，表示由插件自己处理插件配置，配置入口在 AdminIndex/setting
        'tip'   => '默认位于网站根目录/data/backup/Data/' //表单的帮助提示
    ],
    'part'=> [// 在后台插件配置表单中的键名 ,会是config[text]
        'title' => '备份卷大小', // 表单的label标题
        'type'  => 'text',// 表单的类型：text,password,textarea,checkbox,radio,select等
        'value' => '20971520',// 表单的默认值
        'tip'   => '数据库备份卷大小' //表单的帮助提示
    ],
    'compress'  => [// 在后台插件配置表单中的键名 ,会是config[password]
        'title' => '是否启用压缩',
        'type'  => 'select',
        'value' => '1',
        'options'=>[
            '0' => '否',
            '1' => '是'
        ],
        'tip'   => '是否启用压缩'
    ],
    'level'    => [
        'title' => '压缩级别',
        'type'  => 'text',
        'value' => '9',
        'tip'   => '压缩级别'
    ]
];
