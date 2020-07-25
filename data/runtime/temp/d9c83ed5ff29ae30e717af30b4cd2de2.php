<?php if (!defined('THINK_PATH')) exit(); /*a:2:{s:48:"themes/admin_simpleboot3/admin\setting\user.html";i:1574323722;s:83:"D:\phpStudy\PHPTutorial\WWW\wsht\public\themes\admin_simpleboot3\public\header.html";i:1564199040;}*/ ?>
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
<div class="wrap">
    <ul class="nav nav-tabs">
        <li class="active"><a href="<?php echo url('setting/user'); ?>">参数设置</a></li>
    </ul>
    <form method="post" class="form-horizontal js-ajax-form margin-top-20" action="<?php echo url('setting/userPost'); ?>">
        <div class="form-group">
            <label for="input-from_name" class="col-sm-2 control-label">
                <span class="form-required">*</span>
                最小提现金额（元）
            </label>
            <div class="col-md-6 col-sm-10">
                <input type="text" class="form-control" name="minimum"
                       value="<?php echo (isset($user_setting['minimum']) && ($user_setting['minimum'] !== '')?$user_setting['minimum']:''); ?>">
            </div>
        </div>
        <div class="form-group">
            <label for="input-from_name" class="col-sm-2 control-label">
                <span class="form-required">*</span>
                余额提现费率（%）
            </label>
            <div class="col-md-6 col-sm-10">
                <input type="text" class="form-control" name="balancerate"
                       value="<?php echo (isset($user_setting['balancerate']) && ($user_setting['balancerate'] !== '')?$user_setting['balancerate']:'0'); ?>">
            </div>
        </div>
        <div class="form-group">
            <label for="input-from_name" class="col-sm-2 control-label">
                <span class="form-required">*</span>
                佣金提现费率（%）
            </label>
            <div class="col-md-6 col-sm-10">
                <input type="text" class="form-control" name="commissionrate"
                       value="<?php echo (isset($user_setting['commissionrate']) && ($user_setting['commissionrate'] !== '')?$user_setting['commissionrate']:'0'); ?>">
            </div>
        </div>
        <div class="form-group">
            <label for="input-from_name" class="col-sm-2 control-label">
                <span class="form-required">*</span>
                保证金提现费率（%）
            </label>
            <div class="col-md-6 col-sm-10">
                <input type="text" class="form-control" name="bondrate"
                       value="<?php echo (isset($user_setting['bondrate']) && ($user_setting['bondrate'] !== '')?$user_setting['bondrate']:'0'); ?>">
            </div>
        </div>
        <div class="form-group">
            <label for="input-from_name" class="col-sm-2 control-label">
                <span class="form-required">*</span>
                提现次数(日)
            </label>
            <div class="col-md-6 col-sm-10">
                <input type="number" class="form-control" name="frequency"
                       value="<?php echo (isset($user_setting['frequency']) && ($user_setting['frequency'] !== '')?$user_setting['frequency']:'2'); ?>">
            </div>
        </div>
        <div class="form-group">
            <div class="col-sm-offset-2 col-sm-10">
                <button type="submit" class="btn btn-primary js-ajax-submit"><?php echo lang('SAVE'); ?></button>
            </div>
        </div>
    </form>
</div>
<script src="/static/js/admin.js"></script>
</body>
</html>