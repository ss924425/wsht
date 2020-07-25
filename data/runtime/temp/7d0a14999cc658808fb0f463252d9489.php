<?php if (!defined('THINK_PATH')) exit(); /*a:2:{s:50:"themes/admin_simpleboot3/admin\setting\charge.html";i:1564199040;s:83:"D:\phpStudy\PHPTutorial\WWW\wsht\public\themes\admin_simpleboot3\public\header.html";i:1564199040;}*/ ?>
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
        <li class="active"><a href="#A" data-toggle="tab">充值配置</a></li>
    </ul>
    <form class="form-horizontal js-ajax-form margin-top-20" role="form" action="<?php echo url('setting/chargePost'); ?>"
          method="post">
        <fieldset>
            <div class="tabbable">
                <div class="tab-content">
                    <div class="tab-pane active" id="A">
                        <div class="form-group">
                            <label for="input-site-name" class="col-sm-2 control-label"><span
                                    class="form-required">*</span>充值选项</label>
                            <div class="col-md-6 col-sm-10">
                                <div class="form-inline row">
                                    <input type="text" class="form-control"
                                           name="options[]" value="<?php echo (isset($setting['options'][0]) && ($setting['options'][0] !== '')?$setting['options'][0]:5); ?>" placeholder="选项1">
                                    <input type="text" class="form-control"
                                           name="options[]" value="<?php echo (isset($setting['options'][1]) && ($setting['options'][1] !== '')?$setting['options'][1]:10); ?>" placeholder="选项2">
                                    <input type="text" class="form-control"
                                           name="options[]" value="<?php echo (isset($setting['options'][2]) && ($setting['options'][2] !== '')?$setting['options'][2]:20); ?>" placeholder="选项3">
                                </div>
                                <div class="form-inline row">
                                    <input type="text" class="form-control"
                                           name="options[]" value="<?php echo (isset($setting['options'][3]) && ($setting['options'][3] !== '')?$setting['options'][3]:50); ?>" placeholder="选项4">
                                    <input type="text" class="form-control"
                                           name="options[]" value="<?php echo (isset($setting['options'][4]) && ($setting['options'][4] !== '')?$setting['options'][4]:100); ?>" placeholder="选项5">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="input-site-name" class="col-sm-2 control-label"><span
                                    class="form-required">*</span>余额充值说明</label>
                            <div class="col-md-6 col-sm-10">
                                <script type="text/plain" id="content" name="yue_desc"><?php echo (htmlspecialchars_decode($setting['yue_desc']) ?: ''); ?></script>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="input-site-name" class="col-sm-2 control-label"><span
                                    class="form-required">*</span>保证金充值说明</label>
                            <div class="col-md-6 col-sm-10">
                                <script type="text/plain" id="content2" name="deposit_desc"><?php echo (htmlspecialchars_decode($setting['deposit_desc']) ?: ''); ?></script>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-sm-offset-2 col-sm-10">
                                <button type="submit" class="btn btn-primary js-ajax-submit" data-refresh="1">
                                    <?php echo lang('SAVE'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </fieldset>
    </form>
</div>
<script type="text/javascript" src="/static/js/admin.js"></script>
<script type="text/javascript">
    //编辑器路径定义
    var editorURL = GV.WEB_ROOT;
</script>
<script type="text/javascript" src="/static/js/ueditor/ueditor.config.js"></script>
<script type="text/javascript" src="/static/js/ueditor/ueditor.all.min.js"></script>
<script type="text/javascript">
    $(function () {
        editorcontent1 = new baidu.editor.ui.Editor();
        editorcontent1.render('content');
        editorcontent2 = new baidu.editor.ui.Editor();
        editorcontent2.render('content2');
        try {
            editorcontent1.sync();
            editorcontent2.sync();
        } catch (err) {
        }
    });
</script>
</body>
</html>
