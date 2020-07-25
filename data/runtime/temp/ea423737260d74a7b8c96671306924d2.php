<?php if (!defined('THINK_PATH')) exit(); /*a:2:{s:60:"themes/admin_simpleboot3/admin\service\servicesort_edit.html";i:1595584472;s:83:"D:\phpStudy\PHPTutorial\WWW\wsht\public\themes\admin_simpleboot3\public\header.html";i:1564199040;}*/ ?>
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
        <input class="form-control" id="photo-{id}-name" type="text" name="photo_names[]" value="{name}"
               style="width: 200px;" title="图片名称">
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
        <li><a href="<?php echo url('service/servicesort'); ?>">服务分类</a></li>
        <li class="active"><a href="<?php echo url('service/servicesortEdit',array('id'=>$id)); ?>">修改分类</a></li>
    </ul>
    <form method="post" class="form-horizontal js-ajax-form margin-top-20" action="<?php echo url('service/servicesortEditPost'); ?>">
        <input type="hidden" name="id" value="<?php echo $id; ?>">
        <div class="form-group">
            <label for="input-device_no" class="col-sm-2 control-label"><span
                    class="form-required">*</span>分类名称</label>
            <div class="col-md-6 col-sm-10">
                <input class="form-control" type="text" name="name" value="<?php echo (isset($info['name']) && ($info['name'] !== '')?$info['name']:''); ?>"/>
                <!--<p class="help-block">建议在4个字内</p>-->
            </div>
        </div>
        <div class="form-group">
            <label for="input-device_no" class="col-sm-2 control-label"><span
                    class="form-required"></span>服务类型</label>
            <div class="col-md-6 col-sm-10">
                <?php if($pname == '粮油棉糖'): ?>
                    <label class="radio-inline"><input type="radio" name="service_type" value="1" checked>翻犁</label>
                    <label class="radio-inline"><input type="radio" name="service_type" value="2">旋犁</label>
                    <?php elseif($pname == '果树茶叶'): ?>
                    <label class="radio-inline"><input type="radio" name="service_type" value="3" checked>修剪</label>
                    <label class="radio-inline"><input type="radio" name="service_type" value="4">植保</label>
                    <?php elseif($pname == '蔬菜花卉'): ?>
                    <label class="radio-inline"><input type="radio" name="service_type" value="4" checked>植保</label>
                    <label class="radio-inline"><input type="radio" name="service_type" value="5">技术指导</label>
                    <?php elseif($pname == '畜牧养殖'): ?>
                    <label class="radio-inline"><input type="radio" name="service_type" value="6" checked>销售</label>
                    <label class="radio-inline"><input type="radio" name="service_type" value="7">饲料</label>
                    <?php elseif($pname == '渔业养殖'): ?>
                    <label class="radio-inline"><input type="radio" name="service_type" value="6" checked>销售</label>
                    <label class="radio-inline"><input type="radio" name="service_type" value="7">饲料</label>
                <?php endif; ?>
            </div>
        </div>
        <div class="form-group">
            <label for="input-device_no" class="col-sm-2 control-label"><span
                    class="form-required"></span>图标</label>
            <div class="col-md-6 col-sm-10">
                <div style="text-align: center;">
                    <input type="hidden" name="img" id="thumbnail" value="<?php echo (isset($info['img']) && ($info['img'] !== '')?$info['img']:''); ?>">
                    <a href="javascript:uploadOneImage('图片上传','#thumbnail');">
                        <?php if(empty($info['img'])): ?>
                            <img src="/themes/admin_simpleboot3/public/assets/images/default-thumbnail.png"
                                 id="thumbnail-preview"
                                 width="135" style="cursor: pointer"/>
                            <?php else: ?>
                            <img src="<?php echo cmf_get_image_preview_url($info['img']); ?>"
                                 id="thumbnail-preview"
                                 width="135" style="cursor: pointer"/>
                        <?php endif; ?>
                    </a>
                    <input type="button" class="btn btn-sm btn-cancel-thumbnail" value="取消图片">
                </div>
                <p class="help-block">提示：若需要设置圆形导航图片，请将图片处理成圆形图片。也可全部不设置图片，那么前端只显示文字</p>
            </div>
        </div>
        <div class="form-group">
            <label for="input-device_no" class="col-sm-2 control-label"><span
                    class="form-required"></span>排序序号</label>
            <div class="col-md-6 col-sm-10">
                <input class="form-control" type="number" name="number" value="<?php echo $info['number']; ?>"/>
                <p class="help-block">填数字，数字越大越靠前</p>
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
    $('#add_url').click(function () {
        var html = '<div class="edit_right_item">'
            + '文字<span class="frm_input_box frm_input_box_150">'
            + '<input type="text" class="frm_input"  name="urlname[]" value="">'
            + '</span>链接<span class="frm_input_box frm_input_box_300">'
            + '<input type="text" class="frm_input"  name="urlurl[]" value="">'
            + '</span>'
            + '<a href="javascript:;" class="delete_params"> 删除</a>'
            + '</div>';
        $('.group_params_box').append(html);
    });
    // 删除属性
    $('body').on('click', '.delete_params', function () {
        $(this).parents('.edit_right_item').remove();
    });
</script>
</body>
</html>