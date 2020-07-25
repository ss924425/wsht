<?php
// +----------------------------------------------------------------------
// | www.umeng123.com 2018
// +----------------------------------------------------------------------
// | Author: SL <admin@umeng123.com>
// +----------------------------------------------------------------------
namespace plugins\database;
use cmf\lib\Plugin;
// use plugins\pay_reading\model\PluginPayReadingModel;
use \think\Db;
class DatabasePlugin extends Plugin
{

    public $info = [
        'name'        => 'Database',
        'title'       => '数据库管理插件',
        'description' => '数据库管理插件',
        'status'      => 1,
        'author'      => 'umeng123.com',
        'version'     => '1.0',
        'demo_url'    => 'https://www.2bigboy.com',
        'author_url'  => 'https://www.umeng123.com'
    ];

    public $hasAdmin = 1;//插件是否有后台管理界面

    // 插件安装
    public function install()
    {          
        return true;
    }

    // 插件卸载
    public function uninstall()
    {
        return true;
    }
    
}