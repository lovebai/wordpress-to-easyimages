<?php
/**
 * Plugin Name: EasyImages
 * Plugin URI: https://github.com/lovebai/wordpress-to-easyimages
 * Description: 图片文件上传到简单图床(EasyImages)。
 * Author: 酷酷的白
 * Author URI: https://bducds.com
 * Version: 1.0.0
 * 
 */

 // 防止直接访问
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 插件激活时调用
function wp_easyimages_plugin_activate() {
    wp_cache_flush(); // 清除缓存
}
register_activation_hook(__FILE__, 'wp_easyimages_plugin_activate');

// 插件停用时调用
function wp_easyimages_plugin_deactivate() {
    wp_cache_flush(); // 清除缓存
}
register_deactivation_hook(__FILE__, 'wp_easyimages_plugin_deactivate');

// 在插件中心添加设置链接
function wp_easyimages_settings_link($links) {
    $settings_link = '<a href="options-general.php?page=wp-easyimages-plugin">设置</a>';
    array_push($links, $settings_link);
    return $links;
}

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'wp_easyimages_settings_link');

// 添加插件设置页面
function wp_easyimages_plugin_menu() {
    add_options_page(
        '简单图床设置',
        '简单图床',
        'manage_options',
        'wp-easyimages-plugin',
        'wp_easyimages_plugin_settings_page'
    );
}
add_action('admin_menu', 'wp_easyimages_plugin_menu');

// 设置页面内容
function wp_easyimages_plugin_settings_page() {
    ?>
    <div class="wrap">
        <h1>简单图床设置</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('wp_easyimages_plugin_options');
            do_settings_sections('wp-easyimages-plugin');
            submit_button();
            ?>
            <p class="description">
                <h1>插件说明</h1>
                1、<b>简单图床地址</b>：图床地址栏中只需填写网站地址，包含<span style="color: #dd2525;"> https </span>或<span style="color: #dd2525;"> http </span>，
                如：<span style="color: #2b0707;background-color: #e0e2e4;border-bottom: 1px solid;"> https://i.obai.cc </span>，
                无需包含<span style="color: #dd2525;"> / </span>和路径.<br>
                2、<b>简单图床Token</b>： 登录你的图床后台-左侧栏选择<span style="color: #dd2525;"> API设置 </span>，然后新增 token 或者复制已存在的 token . <br>
                3、登录图床后台->图床安全->高级设置->开启API上传.<br>
                4、在插件中删除文件会在图床后台的图片回收中.<br>
             </p>            
        </form>
    </div>
    <?php
}

// 注册设置
function wp_easyimages_plugin_settings_init() {
    register_setting('wp_easyimages_plugin_options', 'wp_easyimages_plugin_host_position');
    register_setting('wp_easyimages_plugin_options', 'wp_easyimages_plugin_token_position');

    add_settings_section(
        'wp_easyimages_plugin_section',
        '基本设置',
        null,
        'wp-easyimages-plugin'
    );

    add_settings_field(
        'wp_easyimages_plugin_host_position',
        '简单图床地址：',
        'wp_easyimages_plugin_host_position_render',
        'wp-easyimages-plugin',
        'wp_easyimages_plugin_section'
    );

    add_settings_field(
        'wp_easyimages_plugin_token_position',
        '简单图床Token：',
        'wp_easyimages_plugin_token_position_render',
        'wp-easyimages-plugin',
        'wp_easyimages_plugin_section'
    );

}
add_action('admin_init', 'wp_easyimages_plugin_settings_init');


// 设置页面
function wp_easyimages_plugin_host_position_render(){
    $options = get_option('wp_easyimages_plugin_host_position');
    ?>
    <input type='text' name='wp_easyimages_plugin_host_position' value='<?php echo esc_attr($options); ?>'>
    <?php
}

function wp_easyimages_plugin_token_position_render(){
    $options = get_option('wp_easyimages_plugin_token_position');
    ?>
    <input type='text' name='wp_easyimages_plugin_token_position' value='<?php echo esc_attr($options); ?>'>
    <?php
}


// 在后台文章编辑页面加载CSS
function custom_upload_button_enqueue($hook) {
    if ($hook === 'post.php' || $hook === 'post-new.php') {
        wp_enqueue_style('custom-upload-button-style', plugin_dir_url(__FILE__) . 'custom-upload.css');
    }
}
add_action('admin_enqueue_scripts', 'custom_upload_button_enqueue');

// 添加自定义按钮
function custom_upload_button() {
    $host = get_option('wp_easyimages_plugin_host_position');
    $token = get_option('wp_easyimages_plugin_token_position');
    echo '<button id="custom-upload-button" class="button">上传图片</button>';
    
    echo '<script type="text/javascript">
    jQuery(document).ready(function($) {
    $("body").append(`
        <div id="custom-upload-modal" style="display:none;">
        
            <div class="custom-upload-content">
            <div id="close-modal">❌</div>
                <h2>上传图片到简单图床</h2>
                <form id="external-upload-form" enctype="multipart/form-data">
                    <input type="file" id="upload-image-file" name="image" accept="image/*" required>
                    <input type="submit" class="button" value="上传图片">
                </form>
                <div id="upload-response" style="margin-top: 10px;"></div>
                
                <hr/>
                <div id="uploaded-images-list"></div> <!-- 图片列表容器 -->
            </div>
        </div>
    `);

    // 点击自定义按钮时，显示模态框
    $("#custom-upload-button").click(function(e) {
        e.preventDefault();
        $("#custom-upload-modal").show();
    });

    // 点击关闭按钮时，隐藏模态框
    $("#close-modal").click(function() {
        $("#custom-upload-modal").hide();
    });

    $("#external-upload-form").submit(function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        formData.append("token", "'.$token.'");  // 将token字段添加到FormData
        $("#upload-response").html("正在上传...");
        // 调用外部 API 上传图片
        $.ajax({
            url: "'.$host.'/api/index.php",  
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {                
                if (response.code === 200) {
                    // 上传成功后的处理
                    $("#upload-response").html("上传成功！");
                    let imageFile = $("#upload-image-file")[0]
                    imageFile.value = ""
                    // 将图片 URL 添加到图片列表中
                    $("#uploaded-images-list").prepend(`
                        <div class="uploaded-image-item"  data-image-del-url="` + response.del + `" data-image-url="`+response.url+`" data-image-title="`+response.srcName+`">
                            <img class="upload-image" src="` + response.thumb + `" alt="`+response.srcName+`">
                            <div class="delete-image">删除图片</div> 
                        </div>
                    `);
                }else{
                    $("#upload-response").html("上传失败:"+response.message);
                }
            },
            error: function() {
                $("#upload-response").html("上传失败，请重试。");
            }
        });
    });
    
    //插入图片
    $(document).on("click", ".upload-image", function() {
        let imageItem = $(this).closest(".uploaded-image-item");
        let imageUrl = imageItem.data("image-url");
        let imageTitle = imageItem.data("image-title");
        wp.media.editor.insert(\'<img src="\' + imageUrl + \'" alt="\'+imageTitle+\'" />\');
    });

    // 为上传的图片绑定删除功能
    $(document).on("click", ".delete-image", function() {
        let imageItem = $(this).closest(".uploaded-image-item");
        let imageDelUrl = imageItem.data("image-del-url");
            // 调用删除 API 请求
            $.ajax({url: imageDelUrl,type: "GET"});
            imageItem.remove();
    });

    })
    </script>';
}
add_action('media_buttons', 'custom_upload_button');

// 使用与前端相同的正则表达式进行验证
function wp_easyimages_plugin_sanitize_host($input) {
    $regex = '/^(https?:\/\/)?([a-zA-Z0-9.-]+)(\.[a-zA-Z]{2,})(:\d{1,5})?$/';
    
    if (preg_match($regex, $input)) {
        return sanitize_text_field($input); // 如果验证通过，清理并返回输入
    } else {
        add_settings_error('wp_easyimages_plugin_host_position', 'invalid-host', '请输入有效的网站地址（不包含路径）。');
        return ''; // 返回空值表示输入无效
    }
}

// 注册设置时应用验证函数
register_setting('wp_easyimages_plugin', 'wp_easyimages_plugin_host_position', 'wp_easyimages_plugin_sanitize_host');


// 防止缓存
function wp_easyimages_plugin_prevent_cache($headers) {
    $headers['Cache-Control'] = 'no-cache, no-store, must-revalidate';
    $headers['Pragma'] = 'no-cache';
    $headers['Expires'] = '0';
    return $headers;
}
add_filter('wp_headers', 'wp_easyimages_plugin_prevent_cache');