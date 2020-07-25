<?php
// +----------------------------------------------------------------------
// | www.umeng123.com 2018
// +----------------------------------------------------------------------
// | Author: SL <admin@umeng123.com>
// +----------------------------------------------------------------------
namespace plugins\database\controller; 

use cmf\controller\PluginAdminBaseController;
use think\Db;
use plugins\database\classes;

class AdminIndexController extends PluginAdminBaseController
{

    protected function _initialize()
    {
        parent::_initialize();
        $adminId = cmf_get_current_admin_id();//获取后台管理员id，可判断是否登录
        if (!empty($adminId)) {
            $this->assign("admin_id", $adminId);
        }
        $config = $this->getPlugin()->getConfig();
        $config['path'] = ROOT_PATH .'data/backup/'. $config['path'];                
        $this->sql=new \plugins\database\classes\Backup($config);
    }

    /**
     * 数据库管理
     * @adminMenu(
     *     'name'   => '数据库管理',
     *     'parent' => 'admin/Plugin/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '数据库备份还原插件',
     *     'param'  => ''
     * )
     */
    public function index()
    {
        $type = request()->post('type');
        $tables =explode(',',request()->post('name'));        
        switch ($type)
        {
            case "backup": //备份
                foreach($tables as $key=>$val){
                   $res = $this->sql->backup($val,0);
                }
                break;
            case "optimize":
                foreach($tables as $key=>$val){
                    $res = $this->sql->optimize($val);
                }
                break;
            case "repair":
                foreach($tables as $key=>$val){
                    $res = $this->sql->repair($val);
                }
                break;
            default: //获取所有表
                $list = Db::table('information_schema.TABLES')->where('TABLE_SCHEMA',config('database')['database'])->select();
                
                $this->assign('list', $list);
                return $this->fetch('/admin_index');
        }
        return json(['code'=>0,'data'=>'操作成功！']);
    }

    public function files(){
        $type = request()->get('type');
        $name = request()->get('name');
        switch($type){
            case 'download':
                $res = $this->sql->downloadFile($name);
                break;
            case 'restore':
                $this->sql->setFile($name);
                $res = $this->sql->import(0);
                break;
            case 'del':
                $res = $this->sql->delFile($name);
                break;
            default:
                $list = $this->sql->fileList();
                $this->assign('list', $list);
                return $this->fetch('/admin_files');
        }
        return json(['code'=>0]);
    }

}
