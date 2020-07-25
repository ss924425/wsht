<?php if (!defined('THINK_PATH')) exit(); /*a:2:{s:48:"themes/admin_simpleboot3/admin\setting\site.html";i:1573089360;s:83:"D:\phpStudy\PHPTutorial\WWW\wsht\public\themes\admin_simpleboot3\public\header.html";i:1564199040;}*/ ?>
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
        <li class="active"><a href="#A" data-toggle="tab"><?php echo lang('WEB_SITE_INFOS'); ?></a></li>
    </ul>
    <form class="form-horizontal js-ajax-form margin-top-20" role="form" action="<?php echo url('setting/sitePost'); ?>"
          method="post">
        <fieldset>
            <div class="tabbable">
                <div class="tab-content">
                    <div class="tab-pane active" id="A">
                        <div class="form-group">
                            <label for="input-site-name" class="col-sm-2 control-label"><span
                                    class="form-required">*</span><?php echo lang('WEBSITE_NAME'); ?></label>
                            <div class="col-md-6 col-sm-10">
                                <input type="text" class="form-control" id="input-site-name" name="options[site_name]"
                                       value="<?php echo (isset($site_info['site_name']) && ($site_info['site_name'] !== '')?$site_info['site_name']:''); ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="input-site-name" class="col-sm-2 control-label"><span
                                    class="form-required">*</span>下载地址</label>
                            <div class="col-md-6 col-sm-10">
                                <input type="text" class="form-control"  name="options[site_link]"
                                       value="<?php echo $site_info['site_link']; ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="input-site-name" class="col-sm-2 control-label"><span
                                    class="form-required">*</span>官方邮箱</label>
                            <div class="col-md-6 col-sm-10">
                                <input type="text" class="form-control"  name="options[site_admin_email]"
                                       value="<?php echo $site_info['site_admin_email']; ?>">
                            </div>
                        </div>
                        <!--<div class="form-group">-->
                            <!--<label for="input-site-name" class="col-sm-2 control-label"><span-->
                                    <!--class="form-required">*</span>默认头像</label>-->
                            <!--<div class="col-md-6 col-sm-10">-->
                                <!--<input type="hidden" name="options[dhead]" id="thumbnail"-->
                                       <!--value="<?php echo (isset($site_info['dhead']) && ($site_info['dhead'] !== '')?$site_info['dhead']:''); ?>">-->
                                <!--<a href="javascript:uploadOneImage('图片上传','#thumbnail');">-->
                                    <!--<?php if(empty($site_info['dhead'])): ?>-->
                                        <!--<img src="/themes/admin_simpleboot3/public/assets/images/default-thumbnail.png"-->
                                             <!--id="thumbnail-preview"-->
                                             <!--width="135" style="cursor: pointer"/>-->
                                        <!--<?php else: ?>-->
                                        <!--<img src="<?php echo cmf_get_image_preview_url($site_info['dhead']); ?>"-->
                                             <!--id="thumbnail-preview"-->
                                             <!--width="135" style="cursor: pointer"/>-->
                                    <!--<?php endif; ?>-->
                                <!--</a>-->
                                <!--<input type="button" class="btn btn-sm btn-cancel-thumbnail" value="取消图片">-->
                            <!--</div>-->
                        <!--</div>-->
                        <!--<div class="form-group">-->
                            <!--<label for="input-site-name" class="col-sm-2 control-label"><span-->
                                    <!--class="form-required">*</span>客服二维码</label>-->
                            <!--<div class="col-md-6 col-sm-10">-->
                                <!--<input type="hidden" name="options[kefucode]" id="thumbnail1"-->
                                       <!--value="<?php echo (isset($site_info['kefucode']) && ($site_info['kefucode'] !== '')?$site_info['kefucode']:''); ?>">-->
                                <!--<a href="javascript:uploadOneImage('图片上传','#thumbnail1');">-->
                                    <!--<?php if(empty($site_info['kefucode'])): ?>-->
                                        <!--<img src="/themes/admin_simpleboot3/public/assets/images/default-thumbnail.png"-->
                                             <!--id="thumbnail1-preview"-->
                                             <!--width="135" style="cursor: pointer"/>-->
                                        <!--<?php else: ?>-->
                                        <!--<img src="<?php echo cmf_get_image_preview_url($site_info['kefucode']); ?>"-->
                                             <!--id="thumbnail1-preview"-->
                                             <!--width="135" style="cursor: pointer"/>-->
                                    <!--<?php endif; ?>-->
                                <!--</a>-->
                                <!--<input type="button" class="btn btn-sm btn-cancel-thumbnail1" value="取消图片">-->
                            <!--</div>-->
                        <!--</div>-->
                        <!--<div class="form-group">-->
                            <!--<label for="input-site-name" class="col-sm-2 control-label"><span-->
                                    <!--class="form-required">*</span>QQ群二维码</label>-->
                            <!--<div class="col-md-6 col-sm-10">-->
                                <!--<input type="hidden" name="options[qqcode]" id="thumbnail2"-->
                                       <!--value="<?php echo (isset($site_info['qqcode']) && ($site_info['qqcode'] !== '')?$site_info['qqcode']:''); ?>">-->
                                <!--<a href="javascript:uploadOneImage('图片上传','#thumbnail2');">-->
                                    <!--<?php if(empty($site_info['qqcode'])): ?>-->
                                        <!--<img src="/themes/admin_simpleboot3/public/assets/images/default-thumbnail.png"-->
                                                 <!--id="thumbnail2-preview"-->
                                             <!--width="135" style="cursor: pointer"/>-->
                                        <!--<?php else: ?>-->
                                        <!--<img src="<?php echo cmf_get_image_preview_url($site_info['qqcode']); ?>"-->
                                             <!--id="thumbnail2-preview"-->
                                             <!--width="135" style="cursor: pointer"/>-->
                                    <!--<?php endif; ?>-->
                                <!--</a>-->
                                <!--<input type="button" class="btn btn-sm btn-cancel-thumbnail2" value="取消图片">-->
                            <!--</div>-->
                        <!--</div>-->
                        <div class="form-group">
                            <label for="input-site-name" class="col-sm-2 control-label"><span
                                    class="form-required">*</span>联系客服:</label>
                            <div class="col-md-6 col-sm-10">
                                <script type="text/plain" id="content" name="options[site_kefu]"><?php echo (htmlspecialchars_decode($site_info['site_kefu']) ?: ''); ?></script>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="input-site-name" class="col-sm-2 control-label"><span
                                    class="form-required">*</span>推荐二维码说明:</label>
                            <div class="col-md-6 col-sm-10">
                                <script type="text/plain" id="content1" name="options[qrcode_info]"><?php echo (htmlspecialchars_decode($site_info['qrcode_info']) ?: ''); ?></script>
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
        editorcontent2.render('content1');
        try {
            editorcontent1.sync();
            editorcontent2.sync();
        } catch (err) {
        }
    });
</script>
<script type="text/javascript">
    $(function () {
        $('.btn-cancel-thumbnail').click(function () {
            $('#thumbnail-preview').attr('src', '/themes/admin_simpleboot3/public/assets/images/default-thumbnail.png');
            $('#thumbnail').val('');
        });
        $('.btn-cancel-thumbnail1').click(function () {
            $('#thumbnail1-preview').attr('src', '/themes/admin_simpleboot3/public/assets/images/default-thumbnail.png');
            $('#thumbnail1').val('');
        });
        $('.btn-cancel-thumbnail2').click(function () {
            $('#thumbnail2-preview').attr('src', '/themes/admin_simpleboot3/public/assets/images/default-thumbnail.png');
            $('#thumbnail2').val('');
        });
    });
</script>
</body>
</body>
</html>
