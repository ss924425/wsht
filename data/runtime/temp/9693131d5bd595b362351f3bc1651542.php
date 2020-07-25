<?php if (!defined('THINK_PATH')) exit(); /*a:2:{s:55:"themes/admin_simpleboot3/admin\service\servicetype.html";i:1595645087;s:83:"D:\phpStudy\PHPTutorial\WWW\wsht\public\themes\admin_simpleboot3\public\header.html";i:1564199040;}*/ ?>
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
        <li class="active"><a href="<?php echo url('service/servicetype'); ?>">服务类型</a></li>
        <li><a href="<?php echo url('service/servicetypeAdd'); ?>">新增类型</a></li>
    </ul>
    <form class="js-ajax-form" action="<?php echo url('service/listOrder'); ?>" method="post">
        <table class="table table-hover table-bordered">
            <thead>
            <tr>
                </th>
                <th width="60">ID</th>
                <th>服务类型</th>
                <th>分类ID/分类名称</th>
                <th>操作</th>
            </tr>
            </thead>
            <tbody>
            <?php if(is_array($list) || $list instanceof \think\Collection || $list instanceof \think\Paginator): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$v): $mod = ($i % 2 );++$i;?>
                <tr>
                    <td><?php echo $v['id']; ?></td>
                    <td>
                       <?php echo $v['name']; ?>
                    </td>
                    <td>
                        <?php echo $v['sortid']; ?>/<?php echo $v['sname']; ?>
                    </td>
                    <td>
                        <a href="<?php echo url('service/servicetypeEdit',['id' => $v['id']]); ?>" style="padding-left: 10px"><?php echo lang('EDIT'); ?></a>

                        <a class="js-ajax-delete"
                               href="<?php echo url('service/servicetypeDelete',['id' => $v['id']]); ?>" style="color: red; padding-left: 10px"><?php echo lang('DELETE'); ?></a>
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