<?php if (!defined('THINK_PATH')) exit(); /*a:2:{s:59:"themes/admin_simpleboot3/admin\service\servicetype_add.html";i:1595644622;s:83:"D:\phpStudy\PHPTutorial\WWW\wsht\public\themes\admin_simpleboot3\public\header.html";i:1564199040;}*/ ?>
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
<style type="text/css">
    .pic-list li {
        margin-bottom: 5px;
    }
</style>
<script type="text/html" id="photos-item-tpl">
    <li id="saved-image{id}">
        <input id="photo-{id}" type="hidden" name="taskimg[]" value="{filepath}">
        <img id="photo-{id}-preview" src="{url}" style="height:36px;width: 36px;"
             onclick="imagePreviewDialog(this.src);">
        <a href="javascript:uploadOneImage('图片上传','#photo-{id}');">替换</a>
        <a href="javascript:(function(){$('#saved-image{id}').remove();})();">移除</a>
    </li>
</script>
</head>
<body>
<div class="wrap">
    <ul class="nav nav-tabs">
        <li><a href="<?php echo url('service/servicetype'); ?>">服务类型</a></li>
        <li class="active"><a href="<?php echo url('service/servicetypeAdd'); ?>">新增类型</a></li>
    </ul>
    <form method="post" class="form-horizontal js-ajax-form margin-top-20" action="<?php echo url('service/servicetypeAddPost'); ?>">
        <div class="form-group">
            <label for="input-device_no" class="col-sm-2 control-label"><span
                    class="form-required">*</span>所属分类</label>
            <div class="col-md-6 col-sm-10">
                <select class="form-control" name="sortid" required id="sid">
                    <?php if(is_array($sorts) || $sorts instanceof \think\Collection || $sorts instanceof \think\Paginator): $i = 0; $__LIST__ = $sorts;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?>
                        <option value="<?php echo $vo['id']; ?>" <?php if($info['id'] == $vo['id']): ?>selected<?php endif; ?> >&nbsp&nbsp&nbsp--<?php echo $vo['name']; ?></option>
                    <?php endforeach; endif; else: echo "" ;endif; ?>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label for="input-device_no" class="col-sm-2 control-label"><span
                    class="form-required">*</span>服务名称</label>
            <div class="col-md-6 col-sm-10">
                <input class="form-control" type="text" name="name" value="" autocomplete="off" />
                <!--<p class="help-block">建议在4个字内</p>-->
            </div>
        </div>
        <div class="form-group">
            <div class="col-sm-offset-2 col-sm-10">
                <button type="submit" class="btn btn-primary js-ajax-submit"><?php echo lang('ADD'); ?></button>
            </div>
        </div>
    </form>
</div>
<script src="/static/js/admin.js"></script>
<script type="text/javascript">
    //编辑器路径定义
    var editorURL = GV.WEB_ROOT;
</script>
<script type="text/javascript" src="/static/js/ueditor/ueditor.config.js"></script>
<script type="text/javascript" src="/static/js/ueditor/ueditor.all.min.js"></script>
<script type="text/javascript">
    $(function () {

        // var editorcontent = new baidu.editor.ui.Editor();
        // var editorcontent2 = new baidu.editor.ui.Editor();
        // editorcontent.render('content');
        // editorcontent2.render('content2');
        // try {
        //     editorcontent.sync();
        //     editorcontent2.sync();
        // } catch (err) {
        // }

        $('.btn-cancel-thumbnail').click(function () {
            $('#thumbnail-preview').attr('src', '/themes/admin_simpleboot3/public/assets/images/default-thumbnail.png');
            $('#thumbnail').val('');
        });
    });
    // 超链接add_url
    $('#add_url').click(function(){
        var html = '<div class="edit_right_item">'
            +'文字<span class="frm_input_box frm_input_box_150">'
            +'<input type="text" class="frm_input"  name="urlname[]" value="">'
            +'</span>链接<span class="frm_input_box frm_input_box_300">'
            +'<input type="text" class="frm_input"  name="urlurl[]" value="">'
            +'</span>'
            +'<a href="javascript:;" class="delete_params"> 删除</a>'
            +'</div>';
        $('.group_params_box').append(html);
    });
    // 删除属性
    $('body').on('click','.delete_params',function(){
        $(this).parents('.edit_right_item').remove();
    });

    $('#sid').change(function () {
        var sid = $(this).val()
        // location.href = '<?php echo url("servicesortAdd"); ?>' + "?sid=" + sid;
    })
</script>
</body>
</html>