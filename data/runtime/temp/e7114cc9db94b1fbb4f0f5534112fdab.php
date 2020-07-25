<?php if (!defined('THINK_PATH')) exit(); /*a:2:{s:50:"themes/admin_simpleboot3/admin\setting\upload.html";i:1578559062;s:83:"D:\phpStudy\PHPTutorial\WWW\wsht\public\themes\admin_simpleboot3\public\header.html";i:1564199040;}*/ ?>
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
        <li class="active"><a>上传设置</a></li>
    </ul>
    <form method="post" class="js-ajax-form margin-top-20" role="form" action="<?php echo url('setting/uploadPost'); ?>">
        <div class="form-group">
            <label>最大同时上传文件数</label>
            <div class="row">
                <div class="col-md-4">
                    <input type="text" class="form-control" name="max_files" title="最大同时上传文件数" placeholder="最大同时上传文件数"
                           value="<?php echo (isset($upload_setting['max_files']) && ($upload_setting['max_files'] !== '')?$upload_setting['max_files']:20); ?>">
                </div>
                <div class="col-md-4">

                </div>
            </div>
            <p class="help-block">多文件上传时,用户能最大同时上传文件数,默认20个</p>
        </div>
        <div class="form-group">
            <label>文件分块上传分块大小</label>
            <div class="row">
                <div class="col-md-4">
                    <input type="text" class="form-control" name="chunk_size" title="文件分块上传分块大小KB,1M=1024KB"
                           placeholder="文件分块上传分块大小KB,1M=1024KB" value="<?php echo (isset($upload_setting['chunk_size']) && ($upload_setting['chunk_size'] !== '')?$upload_setting['chunk_size']:512); ?>">
                </div>
                <div class="col-md-4">
                </div>
            </div>
            <p class="help-block">文件上传采用分块上传,文件分块大小默认512KB,可以根据服务器最大上传限制设置此数值</p>
        </div>
        <div class="form-group">
            <label>图片文件</label>
            <div class="row">
                <div class="col-md-4">
                    <input type="text" class="form-control" name="file_types[image][upload_max_filesize]"
                           title="允许上传大小KB,1M=1024KB" placeholder="允许上传大小KB,1M=1024KB"
                           value="<?php echo (isset($upload_setting['file_types']['image']['upload_max_filesize']) && ($upload_setting['file_types']['image']['upload_max_filesize'] !== '')?$upload_setting['file_types']['image']['upload_max_filesize']:10240); ?>">
                </div>
                <div class="col-md-4">
                    <input type="text" class="form-control"
                           name="file_types[image][extensions]"
                           title="扩展名,以英文逗号分隔"
                           placeholder="扩展名,以英文逗号分隔"
                           value="<?php echo (isset($upload_setting['file_types']['image']['extensions']) && ($upload_setting['file_types']['image']['extensions'] !== '')?$upload_setting['file_types']['image']['extensions']:'jpg,jpeg,png,gif,bmp'); ?>">
                </div>
            </div>
            <p class="help-block">允许上传大小默认为10240KB,1M=1024KB，允许上传格式默认为jpg,jpeg,png,gif,bmp</p>
        </div>
        <div class="form-group">
            <label>视频文件</label>
            <div class="row">
                <div class="col-md-4">
                    <input type="text" class="form-control"
                           name="file_types[video][upload_max_filesize]"
                           title="允许上传大小KB,1M=1024KB"
                           placeholder="允许上传大小KB,1M=1024KB"
                           value="<?php echo (isset($upload_setting['file_types']['video']['upload_max_filesize']) && ($upload_setting['file_types']['video']['upload_max_filesize'] !== '')?$upload_setting['file_types']['video']['upload_max_filesize']:10240); ?>">
                </div>
                <div class="col-md-4">
                    <input type="text" class="form-control"
                           name="file_types[video][extensions]"
                           title="扩展名,以英文逗号分隔"
                           placeholder="扩展名,以英文逗号分隔"
                           value="<?php echo (isset($upload_setting['file_types']['video']['extensions']) && ($upload_setting['file_types']['video']['extensions'] !== '')?$upload_setting['file_types']['video']['extensions']:'mp4,avi,wmv,rm,rmvb,mkv'); ?>">
                </div>
            </div>
            <p class="help-block">允许上传大小默认为102400KB,1M=1024KB，允许上传格式默认为mp4,avi,wmv,rm,rmvb,mkv</p>
        </div>
        <div class="form-group">
            <label>音频文件</label>
            <div class="row">
                <div class="col-md-4">
                    <input type="text" class="form-control"
                           name="file_types[audio][upload_max_filesize]"
                           title="允许上传大小KB,1M=1024KB"
                           placeholder="允许上传大小KB,1M=1024KB"
                           value="<?php echo (isset($upload_setting['file_types']['audio']['upload_max_filesize']) && ($upload_setting['file_types']['audio']['upload_max_filesize'] !== '')?$upload_setting['file_types']['audio']['upload_max_filesize']:10240); ?>">
                </div>
                <div class="col-md-4">
                    <input type="text" class="form-control"
                           name="file_types[audio][extensions]"
                           title="扩展名,以英文逗号分隔"
                           placeholder="扩展名,以英文逗号分隔"
                           value="<?php echo (isset($upload_setting['file_types']['audio']['extensions']) && ($upload_setting['file_types']['audio']['extensions'] !== '')?$upload_setting['file_types']['audio']['extensions']:'mp3,wma,wav'); ?>">
                </div>
            </div>
            <p class="help-block">允许上传大小默认为10240KB,1M=1024KB，允许上传格式默认为mp3,wma,wav</p>
        </div>
        <div class="form-group">
            <label>附件</label>
            <div class="row">
                <div class="col-md-4">
                    <input type="text" class="form-control" name="file_types[file][upload_max_filesize]"
                           title="允许上传大小KB,1M=1024KB" placeholder="允许上传大小KB,1M=1024KB"
                           value="<?php echo (isset($upload_setting['file_types']['file']['upload_max_filesize']) && ($upload_setting['file_types']['file']['upload_max_filesize'] !== '')?$upload_setting['file_types']['file']['upload_max_filesize']:10240); ?>">
                </div>
                <div class="col-md-4">
                    <input type="text" class="form-control"
                           name="file_types[file][extensions]"
                           title="扩展名,以英文逗号分隔"
                           placeholder="扩展名,以英文逗号分隔"
                           value="<?php echo (isset($upload_setting['file_types']['file']['extensions']) && ($upload_setting['file_types']['file']['extensions'] !== '')?$upload_setting['file_types']['file']['extensions']:'txt,pdf,doc,docx,xls,xlsx,ppt,pptx,zip,rar'); ?>">
                </div>
            </div>
            <p class="help-block">
                允许上传大小默认为10240KB,1M=1024KB，允许上传格式默认为除以上文档类型以外的其它常用文件,如：txt,pdf,doc,docx,xls,xlsx,ppt,pptx,zip,rar</p>
        </div>
        <div class="form-group">
            <label>accessKey</label>
            <div class="row">
                <div class="col-md-4">
                    <input type="text" class="form-control" name="file_types[file][accessKey]"
                           placeholder="accessKey_七牛云"
                           value="<?php echo (isset($upload_setting['file_types']['file']['accessKey']) && ($upload_setting['file_types']['file']['accessKey'] !== '')?$upload_setting['file_types']['file']['accessKey']:''); ?>">
                </div>

            </div>
        </div>
        <div class="form-group">
            <label>secretKey</label>
            <div class="row">
                <div class="col-md-4">
                    <input type="text" class="form-control" name="file_types[file][secretKey]"
                           placeholder="secretKey_七牛云"
                           value="<?php echo (isset($upload_setting['file_types']['file']['secretKey']) && ($upload_setting['file_types']['file']['secretKey'] !== '')?$upload_setting['file_types']['file']['secretKey']:''); ?>">
                </div>

            </div>
        </div>
        <div class="form-group">
            <label>bucket</label>
            <div class="row">
                <div class="col-md-4">
                    <input type="text" class="form-control" name="file_types[file][bucket]"
                           placeholder="上传空间_七牛云"
                           value="<?php echo (isset($upload_setting['file_types']['file']['bucket']) && ($upload_setting['file_types']['file']['bucket'] !== '')?$upload_setting['file_types']['file']['bucket']:''); ?>">
                </div>

            </div>
        </div>
        <div class="form-group">
            <label>bomain</label>
            <div class="row">
                <div class="col-md-4">
                    <input type="text" class="form-control" name="file_types[file][bomain]"
                           placeholder="空间绑定的域名_七牛云"
                           value="<?php echo (isset($upload_setting['file_types']['file']['bomain']) && ($upload_setting['file_types']['file']['bomain'] !== '')?$upload_setting['file_types']['file']['bomain']:''); ?>">
                </div>

            </div>
        </div>
        <div class="form-group">
            <button type="submit" class="btn btn-primary js-ajax-submit"><?php echo lang('SAVE'); ?></button>
        </div>
    </form>
</div>
<script src="/static/js/admin.js"></script>
</body>
</html>