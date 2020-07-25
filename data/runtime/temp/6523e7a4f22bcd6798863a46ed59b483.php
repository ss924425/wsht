<?php if (!defined('THINK_PATH')) exit(); /*a:2:{s:53:"themes/admin_simpleboot3/admin\selftask\tasksort.html";i:1573089360;s:83:"D:\phpStudy\PHPTutorial\WWW\wsht\public\themes\admin_simpleboot3\public\header.html";i:1564199040;}*/ ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <!-- Set render engine for 360 browser -->
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- HTML5 shim for IE8 support of HTML5 elements -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
    <![endif]-->


    <link href="/themes/admin_simpleboot3/public/assets/themes/simpleadmin/bootstrap.min.css" rel="stylesheet">
    <link href="/themes/admin_simpleboot3/public/assets/simpleboot3/css/simplebootadmin.css" rel="stylesheet">
    <link href="/static/font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css">
    <!--[if lt IE 9]>
    <script src="https://cdn.bootcss.com/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
    <style>
        form .input-order {
            margin-bottom: 0px;
            padding: 0 2px;
            width: 42px;
            font-size: 12px;
        }

        form .input-order:focus {
            outline: none;
        }

        .table-actions {
            margin-top: 5px;
            margin-bottom: 5px;
            padding: 0px;
        }

        .table-list {
            margin-bottom: 0px;
        }

        .form-required {
            color: red;
        }
    </style>
    <script type="text/javascript">
        //全局变量
        var GV = {
            ROOT: "/",
            WEB_ROOT: "/",
            JS_ROOT: "static/js/",
            APP: '<?php echo \think\Request::instance()->module(); ?>'/*当前应用名*/
        };
    </script>
    <script src="/themes/admin_simpleboot3/public/assets/js/jquery-1.10.2.min.js"></script>
    <script src="/static/js/wind.js"></script>
    <script src="/themes/admin_simpleboot3/public/assets/js/bootstrap.min.js"></script>
    <script>
        Wind.css('artDialog');
        Wind.css('layer');
        $(function () {
            $("[data-toggle='tooltip']").tooltip({
                container:'body',
                html:true,
            });
            $("li.dropdown").hover(function () {
                $(this).addClass("open");
            }, function () {
                $(this).removeClass("open");
            });
        });
    </script>
    <?php if(APP_DEBUG): ?>
        <style>
            #think_page_trace_open {
                z-index: 9999;
            }
        </style>
    <?php endif; ?>
    <script src="/static/js/layer/layer.js"></script>
</head>
<body>
<div class="wrap js-check-wrap">
    <ul class="nav nav-tabs">
        <li class="active"><a href="<?php echo url('selftask/tasksort'); ?>">任务分类</a></li>
        <li><a href="<?php echo url('selftask/tasksortAdd'); ?>">新增分类</a></li>
    </ul>
    <form class="js-ajax-form" action="<?php echo url('selftask/listOrder'); ?>" method="post">
        <button class="btn btn-primary btn-sm js-ajax-submit" type="submit"
                data-action="<?php echo url('selftask/tasksortPublish',array('yes'=>1)); ?>" data-subcheck="true">发布
        </button>
        <button class="btn btn-primary btn-sm js-ajax-submit" type="submit"
                data-action="<?php echo url('selftask/tasksortPublish',array('no'=>1)); ?>" data-subcheck="true">取消发布
        </button>
        <button class="btn btn-danger btn-sm js-ajax-submit" type="submit"
                data-action="<?php echo url('selftask/tasksortDelete'); ?>" data-subcheck="true" data-msg="您确定删除吗？">
            <?php echo lang('DELETE'); ?>
        </button>
        <table class="table table-hover table-bordered">
            <thead>
            <tr>
                <th width="15">
                    <label>
                        <input type="checkbox" class="js-check-all" data-direction="x" data-checklist="js-check-x">
                    </label>
                </th>
                <th width="60">ID</th>
                <th width="80">上级ID</th>
                <th>分类名称</th>
                <th>上级分类名称</th>
                <th>排序序号</th>
                <th width="70">状态</th>
                <th>操作</th>
            </tr>
            </thead>
            <tbody>
            <?php if(is_array($list) || $list instanceof \think\Collection || $list instanceof \think\Paginator): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$v): $mod = ($i % 2 );++$i;?>
                <tr>
                    <td>
                        <input type="checkbox" class="js-check" data-yid="js-check-y" data-xid="js-check-x" name="ids[]"
                               value="<?php echo $v['id']; ?>" title="ID:<?php echo $v['id']; ?>">
                    </td>
                    <td><?php echo $v['id']; ?></td>
                    <td><?php echo $v['pid']; ?></td>
                    <td>
                        <?php if($v['pid'] == 0): ?>
                            <span style="color: blue"><?php echo $v['name']; ?></span>
                            <?php else: ?>
                            <?php echo $v['name']; endif; ?>
                    </td>
                    <td>
                        <?php if($v['pname'] == ''): ?>
                            <span style="color: blue">顶级分类</span>
                            <?php else: ?>
                            <?php echo $v['pname']; endif; ?>
                    </td>
                    <td><input name='list_orders[<?php echo $v['id']; ?>]' type='text' size='3' value='<?php echo $v['number']; ?>' class='input-order'>
                    </td>
                    <td>
                        <?php if(!(empty($v['status']) || (($v['status'] instanceof \think\Collection || $v['status'] instanceof \think\Paginator ) && $v['status']->isEmpty()))): ?>
                            <a data-toggle="tooltip" title="已发布"><i class="fa fa-check"></i></a>
                            <?php else: ?>
                            <a data-toggle="tooltip" title="未发布"><i class="fa fa-close"></i></a>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if($v['pid'] > 0): ?>
                            <a href="<?php echo url('setting/doTaskExplain',['tasktype' => $v['id']]); ?>">任务步骤</a>
                        <?php endif; if($v['pid'] == 0): ?>
                            <a href="<?php echo url('setting/bindStep',['tasktype' => $v['id']]); ?>">账号绑定步骤</a>
                        <?php endif; ?>
                        <a href="<?php echo url('selftask/tasksortEdit',['id' => $v['id']]); ?>" style="padding-left: 10px"><?php echo lang('EDIT'); ?></a>
                        <?php if($v['pid'] > 0): ?>
                            <a class="js-ajax-delete"
                               href="<?php echo url('selftask/tasksortDelete',['id' => $v['id']]); ?>" style="color: red; padding-left: 10px"><?php echo lang('DELETE'); ?></a>
                        <?php endif; ?>

                    </td>
                </tr>
            <?php endforeach; endif; else: echo "" ;endif; ?>
            </tbody>
        </table>
        <div class="table-actions">
            <button type="submit" class="btn btn-primary btn-sm js-ajax-submit"><?php echo lang('SORT'); ?></button>
        </div>
        <ul class="pagination"><?php echo $page; ?></ul>
    </form>
</div>
<script src="/static/js/admin.js"></script>
</body>
</html>