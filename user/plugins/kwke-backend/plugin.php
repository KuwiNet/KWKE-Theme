<?php
/*
Plugin Name: KWKE Backend
Plugin URI: https://github.com/kuwinet/kwke-theme
Description: KWKE 风格的 YOURLS 主题 + 全局配置中心
Version: 2.0.1
Author: KuWiNet
Author URI: https://zwrt.de
*/

// No direct call
if( !defined( 'YOURLS_ABSPATH' ) ) die();

// ========== 短网址跳转统计 ==========
yourls_add_filter( 'pre_redirect_shorturl', 'kwke_tracking_redirect', 10, 2 );
function kwke_tracking_redirect( $url, $keyword ) {
    $tracking_code = yourls_get_option( 'kwke_tracking_code', '' );
    if (empty($tracking_code)) {
        return $url; // 未配置统计代码，正常 302 跳转
    }
    // 输出过渡页：带统计代码 + JS 自动跳转（几乎无感，约 100ms）
    $target = htmlspecialchars($url, ENT_QUOTES);
    echo '<!DOCTYPE html><html lang="zh-CN"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>跳转中...</title>';
    echo $tracking_code;
    echo '<script>setTimeout(function(){window.location.href="' . $target . '";}, 100);</script>';
    echo '</head><body style="margin:0;padding:0;background:#f5f5f5;display:flex;align-items:center;justify-content:center;height:100vh;font-family:sans-serif;color:#666;">';
    echo '<p>正在跳转...</p>';
    echo '<noscript><a href="' . $target . '">点击跳转</a></noscript>';
    echo '</body></html>';
    exit; // 输出页面后结束，不让 YOURLS 再发 302
}

// ========== 后台主题注入 ==========
yourls_add_action( 'html_head', 'kwke_head_scripts' );

function kwke_head_scripts() {
    $url = yourls_plugin_url( __DIR__ );

    // 输出 pluginURL meta 标签，供 theme.js 使用
    echo '<meta name="pluginURL" content="' . $url . '">';

    // 输出 logo 路径 meta 标签
    $login_logo = yourls_get_option( 'kwke_login_logo_url', $url . '/assets/img/logo_white.png' );
    $admin_logo = yourls_get_option( 'kwke_admin_logo_url', $url . '/assets/img/logo_white.png' );
    echo '<meta name="kwke_login_logo" content="' . kwke_full_url($login_logo) . '">';
    echo '<meta name="kwke_admin_logo" content="' . kwke_full_url($admin_logo) . '">';

    $theme = yourls_get_option( 'kwke_theme_mode', 'dark' );
    echo '<meta name="kwke_theme" content="' . $theme . '">';

    if ($theme == "light") {
        echo '<link rel="stylesheet" href="' . $url . '/assets/css/light.css">';
    } else {
        echo '<link rel="stylesheet" href="' . $url . '/assets/css/dark.css">';
    }
    echo '<link rel="stylesheet" href="' . $url . '/assets/css/animate.min.css">';
    echo '<script src="' . $url . '/assets/js/theme.js"></script>';

    // 统计代码（仅在前端公开页输出，不在后台输出）
    if (!function_exists('yourls_is_admin') || !yourls_is_admin()) {
        $tracking_code = yourls_get_option( 'kwke_tracking_code', '' );
        if (!empty($tracking_code)) {
            echo "\n" . $tracking_code . "\n";
        }
    }

    // 隐藏导航里的 Ozh 链接
    echo '<style>nav li:has(a[href*="ozh.org"]) { display: none !important; }</style>';

    // 登录页样式（和首页风格一致，带壁纸背景）
    $wallpaper_type = yourls_get_option( 'kwke_wallpaper', 'bird' );
    $wallpaper_bg = '';
    if ($wallpaper_type == 'bird') {
        $cids = [9, 36, 15, 26];
        $cid = $cids[array_rand($cids)];
        $start = rand(0, 100);
        $api_url = "http://wallpaper.apc.360.cn/index.php?c=WallPaper&a=getAppsByCategory&from=360chrome&count=1&start={$start}&cid={$cid}";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $response = curl_exec($ch);
        curl_close($ch);
        if ($response) {
            $data = json_decode($response, true);
            if (!empty($data['data'][0]['url'])) {
                $wallpaper_bg = str_replace('http://', 'https://', $data['data'][0]['url']);
            }
        }
    } elseif ($wallpaper_type == 'bing') {
        $wallpaper_bg = 'https://bing.biturl.top/?resolution=1920&format=image&index=0&mkt=zh-CN';
    } elseif ($wallpaper_type == 'custom') {
        $wallpaper_bg = yourls_get_option( 'kwke_wallpaper_custom_url', '' );
    }
    if (empty($wallpaper_bg)) {
        $wallpaper_bg = 'https://picsum.photos/1920/1080';
    }

    $login_logo = yourls_get_option( 'kwke_login_logo_url', '/user/plugins/kwke-backend/assets/img/logo_white.png' );
    $login_logo_url = (strpos($login_logo, 'http') === 0) ? $login_logo : YOURLS_SITE . $login_logo;

    echo '
    <style>
        body.login {
            background: url(' . $wallpaper_bg . ') no-repeat center center fixed !important;
            background-size: cover !important;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 !important;
            padding: 20px !important;
        }
        body.login header {
            display: block !important;
            position: absolute;
            top: -45px;
            left: 50%;
            transform: translateX(-50%);
            margin: 0;
            padding: 0;
        }
        body.login header h1 {
            margin: 0;
            padding: 0;
            font-size: 0 !important;
            line-height: 0 !important;
            text-indent: -9999px !important;
        }
        body.login header h1 a {
            display: block;
            font-size: 0 !important;
            line-height: 0 !important;
            text-decoration: none;
        }
        body.login header h1 a *:not(img) {
            display: none !important;
        }
        body.login #yourls-logo {
            width: 95px !important;
            height: auto !important;
            display: block !important;
        }
        body.login header {
            display: block !important;
            margin: 0;
            padding: 0;
        }
        body.login header h1 {
            margin: 0;
            padding: 0;
            font-size: 0 !important;
            line-height: 0 !important;
        }
        body.login header h1 a {
            display: block;
            font-size: 0 !important;
            line-height: 0 !important;
            text-decoration: none;
        }
        body.login header h1 a *:not(img) {
            display: none !important;
        }
        body.login #yourls-logo {
            display: none !important;
        }
        body.login .login-logo {
            display: none !important;
        }
        .custom-login-logo {
            position: absolute !important;
            top: -55px !important;
            left: 50% !important;
            transform: translateX(-50%) !important;
            z-index: 10 !important;
        }
        .custom-login-logo img {
            width: 95px !important;
            height: auto !important;
            display: block !important;
        }
        body.login #wrap {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            padding: 60px 30px 40px 30px !important;
            margin: 60px 0 0 0 !important;
            max-width: 600px !important;
            width: 100% !important;
            position: relative !important;
        }
        body.login #login {
            text-align: center;
            margin: 0 auto;
            padding: 0 !important;
        }
        body.login #login form {
            text-align: left;
            padding: 0 !important;
            margin: 0 !important;
        }
        body.login #login p {
            margin-bottom: 20px;
            padding: 0 !important;
        }
        body.login #error-message {
            background: rgba(255, 68, 68, 0.9) !important;
            color: white !important;
            border-radius: 10px;
            padding: 14px 16px !important;
            margin-bottom: 20px;
            position: static !important;
            width: 100% !important;
            text-align: center;
            box-sizing: border-box !important;
        }
        .login #login form p {
            margin: 0 0 15px 0 !important;
            padding: 0 !important;
        }
        .login label {
            color: #fff !important;
            font-weight: 500;
            margin: 0 0 4px 0 !important;
            display: block;
        }
        body.login #login form p {
            margin: 0 0 20px 0 !important;
            padding: 0 !important;
        }
        body.login #login form p input.text {
            background: rgba(255, 255, 255, 0.9) !important;
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
            border-radius: 10px !important;
            padding: 12px 16px !important;
            color: #333 !important;
            width: 100% !important;
            max-width: 100% !important;
            box-sizing: border-box !important;
            margin: 0 !important;
            display: block !important;
        }
        /* 强制覆盖原站样式 */
        .login #login,
        .login #login form {
            width: 100% !important;
            max-width: 100% !important;
        }
        .login input.text,
        .login input[type="password"] {
            width: 100% !important;
            max-width: 100% !important;
            font-size: 1rem !important;
        }
        .login label {
            font-size: 1rem !important;
        }
        body.login #error-message.error {
            display: none !important;
        }
        .login input.button {
            font-size: 1rem !important;
        }
        .login-title {
            color: #fff !important;
            font-size: 2rem !important;
            font-weight: 700 !important;
            text-align: center !important;
            margin: 0 0 10px 0 !important;
        }
        .login-subtitle {
            color: rgba(255, 255, 255, 0.8) !important;
            font-size: 1rem !important;
            text-align: center !important;
            margin: 0 0 30px 0 !important;
        }
        .kwke-label {
            color: #fff !important;
            font-size: 1rem !important;
            font-weight: 500 !important;
            display: block !important;
            margin: 0 0 4px 0 !important;
        }
        .login form p {
            margin: 0 0 15px 0 !important;
            padding: 0 !important;
        }
        body.login input.button {
            background: #7c3aed !important;
            border: none !important;
            border-radius: 10px !important;
            padding: 12px 24px !important;
            font-weight: 600 !important;
            width: 100% !important;
            margin-top: 10px !important;
            box-sizing: border-box !important;
        }
        body.login input.button:hover {
            background: #6d2bde !important;
        }
        body.login #error-message.error {
            background: rgba(255, 68, 68, 0.9) !important;
            color: white !important;
            border-radius: 10px;
            padding: 12px;
            margin-bottom: 20px;
            position: static !important;
            width: 100% !important;
        }
        body.login footer { display: none !important; }
        body.login main {
            padding: 0 !important;
        }
    </style>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var wrap = document.querySelector("body.login #wrap");
            if (wrap) {
                var logoDiv = document.createElement("div");
                logoDiv.className = "custom-login-logo";
                var logoImg = document.createElement("img");
                logoImg.src = "' . $login_logo_url . '";
                logoImg.alt = "Logo";
                logoDiv.appendChild(logoImg);
                wrap.insertBefore(logoDiv, wrap.firstChild);
            }
        });
    </script>
    ';
}

// 辅助函数：拼接完整 URL
if (!function_exists('kwke_full_url')) {
    function kwke_full_url($path) {
        return (strpos($path, 'http') === 0) ? $path : YOURLS_SITE . $path;
    }
}

// ========== 登录后跳转 filter ==========
yourls_add_filter( 'redirect_location', function ( $location, $code = 301 ) {
    if ( empty( $_REQUEST['redirect'] ) ) {
        return $location;
    }
    $target = (string) $_REQUEST['redirect'];
    // 只允许站内绝对路径，防开放重定向
    if ( ! preg_match( '#^/[A-Za-z0-9_\-/.]*$#', $target ) ) {
        return $location;
    }
    if ( strpos( $target, '..' ) !== false || strpos( $target, '//' ) === 0 ) {
        return $location;
    }
    $safe = yourls_sanitize_url_safe( $target );
    return $safe !== '' ? $safe : $target;
}, 10, 2 );

yourls_add_filter( 'redirect_code', function ( $code ) {
    return ! empty( $_REQUEST['redirect'] ) ? 302 : $code;
}, 10, 1 );

// ========== 壁纸 URL（前端用） ==========
function kwke_get_wallpaper_url($type) {
    switch ($type) {
        case 'bird':
            return yourls_plugin_url( __DIR__ . '/assets/img/bird-wallpaper.jpg' );
        case 'bing':
            return 'https://bing.biturl.top/?resolution=1920&format=image&index=0&mkt=zh-CN';
        case 'custom':
            return yourls_get_option( 'kwke_wallpaper_custom_url', '' );
        default:
            return '';
    }
}

// ========== 注册设置页 ==========
yourls_add_action( 'plugins_loaded', 'kwke_add_settings' );
function kwke_add_settings() {
    yourls_register_plugin_page( 'kwke_settings', 'KWKE 主题设置', 'kwke_do_settings_page' );
}

// ========== 设置页面 ==========
function kwke_do_settings_page() {

    if( isset( $_POST['kwke_save_settings'] ) ) {
        if ( yourls_verify_nonce( 'kwke_settings' ) ) {
            kwke_settings_update();
            echo '<div class="notice notice-success"><p>设置已保存</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>安全验证失败，请重试</p></div>';
        }
    }

    // 读取当前配置
    $site_title    = yourls_get_option( 'kwke_site_title', 'KWKE - URL Shortener' );
    $short_title   = yourls_get_option( 'kwke_short_title', 'KWKE' );
    $description   = yourls_get_option( 'kwke_description', 'KWKE 短链接服务' );
    $theme_mode    = yourls_get_option( 'kwke_theme_mode', 'dark' );
    $theme_color   = yourls_get_option( 'kwke_theme_color', '#7c3aed' );
    $wallpaper     = yourls_get_option( 'kwke_wallpaper', 'bird' );
    $wallpaper_url = yourls_get_option( 'kwke_wallpaper_custom_url', '' );
    $allow_public  = yourls_get_option( 'kwke_allow_public', false );
    $show_custom   = yourls_get_option( 'kwke_show_custom_url', true );
    $footer_links  = yourls_get_option( 'kwke_footer_links', [
        ['name' => '关于', 'url' => 'https://zwrt.de/'],
        ['name' => '管理后台', 'url' => '/admin']
    ] );
    $logo_url      = yourls_get_option( 'kwke_logo_url', '/assets/img/logo-black.png' );
    $admin_logo_url = yourls_get_option( 'kwke_admin_logo_url', '/user/plugins/kwke-backend/assets/img/logo_white.png' );
    $login_logo_url = yourls_get_option( 'kwke_login_logo_url', '/user/plugins/kwke-backend/assets/img/logo_white.png' );
    $favicon_url   = yourls_get_option( 'kwke_favicon_url', '/assets/img/favicon.ico' );
    $tracking_code = yourls_get_option( 'kwke_tracking_code', '' );

    $nonce = yourls_create_nonce( 'kwke_settings' );

    $checked_public = $allow_public ? 'checked' : '';
    $checked_custom = $show_custom ? 'checked' : '';
    $theme_dark_selected = $theme_mode == 'dark' ? 'selected' : '';
    $theme_light_selected = $theme_mode == 'light' ? 'selected' : '';
    $wp_none_checked = $wallpaper == 'none' ? 'checked' : '';
    $wp_bird_checked = $wallpaper == 'bird' ? 'checked' : '';
    $wp_bing_checked = $wallpaper == 'bing' ? 'checked' : '';
    $wp_scenery_checked = $wallpaper == 'scenery' ? 'checked' : '';
    $wp_anime_checked = $wallpaper == 'anime' ? 'checked' : '';
    $wp_girl_checked = $wallpaper == 'girl' ? 'checked' : '';
    $wp_custom_checked = $wallpaper == 'custom' ? 'checked' : '';

    // 提前计算页脚链接文本
    $footer_links_text = '';
    foreach ($footer_links as $link) {
        $footer_links_text .= htmlspecialchars($link['name'] . ' | ' . $link['url']) . "\n";
    }

    echo <<<HTML
    <div class="wrap">
        <h1>KWKE 主题设置</h1>
        <style>
            .kwke-settings .setting-row {
                display: flex;
                align-items: flex-start;
                margin-bottom: 20px;
            }
            .kwke-settings .setting-label {
                width: 120px;
                flex-shrink: 0;
                padding-top: 8px;
                font-weight: 600;
            }
            .kwke-settings .setting-field {
                flex: 1;
                max-width: 700px;
            }
            .kwke-settings input[type="text"],
            .kwke-settings input[type="url"],
            .kwke-settings textarea {
                width: 100%;
                padding: 8px 12px;
                border: 1px solid #ccc;
                border-radius: 4px;
            }
            .kwke-settings .description {
                margin-top: 6px;
                color: #666;
                font-size: 13px;
            }
            .kwke-settings .section-title {
                margin: 30px 0 15px 0;
                padding-bottom: 8px;
                border-bottom: 1px solid #ddd;
            }
        </style>
        <form method="post" class="kwke-settings">
        <input type="hidden" name="nonce" value="$nonce" />

        <h2 class="section-title">网站信息</h2>
        <div class="setting-row">
            <div class="setting-label">页面标题</div>
            <div class="setting-field"><input type="text" name="kwke_site_title" id="kwke_site_title" value="$site_title"></div>
        </div>
        <div class="setting-row">
            <div class="setting-label">短标题</div>
            <div class="setting-field">
                <input type="text" name="kwke_short_title" id="kwke_short_title" value="$short_title">
                <p class="description">页脚显示用</p>
            </div>
        </div>
        <div class="setting-row">
            <div class="setting-label">描述文字</div>
            <div class="setting-field"><textarea name="kwke_description" id="kwke_description" rows="2">$description</textarea></div>
        </div>
        <div class="setting-row">
            <div class="setting-label">首页 Logo</div>
            <div class="setting-field">
                <input type="text" name="kwke_logo_url" id="kwke_logo_url" value="$logo_url">
                <p class="description">首页卡片上方显示的 logo</p>
            </div>
        </div>
        <div class="setting-row">
            <div class="setting-label">后台左上角 Logo</div>
            <div class="setting-field">
                <input type="text" name="kwke_admin_logo_url" id="kwke_admin_logo_url" value="$admin_logo_url">
                <p class="description">后台页面左上角的 logo</p>
            </div>
        </div>
        <div class="setting-row">
            <div class="setting-label">登录页 Logo</div>
            <div class="setting-field">
                <input type="text" name="kwke_login_logo_url" id="kwke_login_logo_url" value="$login_logo_url">
                <p class="description">登录页面显示的 logo</p>
            </div>
        </div>
        <div class="setting-row">
            <div class="setting-label">Favicon 路径</div>
            <div class="setting-field"><input type="text" name="kwke_favicon_url" id="kwke_favicon_url" value="$favicon_url"></div>
        </div>

        <h2 class="section-title">界面与壁纸</h2>
        <div class="setting-row">
            <div class="setting-label">主题模式</div>
            <div class="setting-field">
                <select name="kwke_theme_mode" id="kwke_theme_mode">
                    <option value="dark" $theme_dark_selected>深色</option>
                    <option value="light" $theme_light_selected>浅色</option>
                </select>
            </div>
        </div>
        <div class="setting-row">
            <div class="setting-label">主题色</div>
            <div class="setting-field">
                <input type="color" name="kwke_theme_color" id="kwke_theme_color" value="$theme_color" style="width: 60px; height: 36px; border: none; cursor: pointer;">
                <span style="margin-left: 8px;">$theme_color</span>
            </div>
        </div>
        <div class="setting-row">
            <div class="setting-label">壁纸类型</div>
            <div class="setting-field">
                <label style="margin-right: 16px;"><input type="radio" name="kwke_wallpaper" value="none" $wp_none_checked> 纯色</label>
                <label style="margin-right: 16px;"><input type="radio" name="kwke_wallpaper" value="bird" $wp_bird_checked> 360壁纸</label>
                <label style="margin-right: 16px;"><input type="radio" name="kwke_wallpaper" value="bing" $wp_bing_checked> Bing每日一图</label>
                <label style="margin-right: 16px;"><input type="radio" name="kwke_wallpaper" value="scenery" $wp_scenery_checked> 随机风景</label>
                <label style="margin-right: 16px;"><input type="radio" name="kwke_wallpaper" value="anime" $wp_anime_checked> 随机动漫</label>
                <label style="margin-right: 16px;"><input type="radio" name="kwke_wallpaper" value="girl" $wp_girl_checked> 美女壁纸</label>
                <label><input type="radio" name="kwke_wallpaper" value="custom" $wp_custom_checked> 自定义URL</label>
            </div>
        </div>
        <div class="setting-row">
            <div class="setting-label">自定义壁纸 URL</div>
            <div class="setting-field"><input type="url" name="kwke_wallpaper_custom_url" id="kwke_wallpaper_custom_url" value="$wallpaper_url" placeholder="https://example.com/bg.jpg"></div>
        </div>

        <h2 class="section-title">功能开关</h2>
        <div class="setting-row">
            <div class="setting-label">允许匿名生成</div>
            <div class="setting-field"><label><input type="checkbox" name="kwke_allow_public" value="1" $checked_public> 未登录用户也可以在前端创建短链接</label></div>
        </div>
        <div class="setting-row">
            <div class="setting-label">显示自定义后缀</div>
            <div class="setting-field"><label><input type="checkbox" name="kwke_show_custom_url" value="1" $checked_custom> 前端显示"自定义链接"输入框</label></div>
        </div>

        <h2 class="section-title">页脚链接</h2>
        <div class="setting-row">
            <div class="setting-label">链接列表</div>
            <div class="setting-field">
                <textarea name="kwke_footer_links" id="kwke_footer_links" rows="5" placeholder="关于 | https://zwrt.de/&#10;管理后台 | /admin">$footer_links_text</textarea>
                <p class="description">每行一个链接，格式：名称 | URL。留空行自动忽略。</p>
            </div>
        </div>

        <h2 class="section-title">统计代码</h2>
        <div class="setting-row">
            <div class="setting-label">统计代码</div>
            <div class="setting-field">
                <textarea name="kwke_tracking_code" id="kwke_tracking_code" rows="6" placeholder='粘贴统计代码，如 Google Analytics、百度统计、51.la 等&#10;例如：&lt;script async src="https://www.googletagmanager.com/gtag/js?id=G-XXXXXXX"&gt;&lt;/script&gt;'>$tracking_code</textarea>
                <p class="description">粘贴第三方统计/分析代码（如 Google Analytics、百度统计等），将在前端公开页 &lt;head&gt; 中输出。留空则不输出。</p>
            </div>
        </div>

        <p class="submit">
            <input type="submit" name="kwke_save_settings" value="保存设置" class="button button-primary" />
        </p>
        </form>
    </div>
HTML;
}

// ========== 保存设置 ==========
function kwke_settings_update() {
    yourls_update_option( 'kwke_site_title', trim($_POST['kwke_site_title'] ?? '') ?: 'KWKE - URL Shortener' );
    yourls_update_option( 'kwke_short_title', trim($_POST['kwke_short_title'] ?? '') ?: 'KWKE' );
    yourls_update_option( 'kwke_description', trim($_POST['kwke_description'] ?? '') ?: 'KWKE 短链接服务' );
    yourls_update_option( 'kwke_logo_url', trim($_POST['kwke_logo_url'] ?? '') ?: '/assets/img/logo-black.png' );
    yourls_update_option( 'kwke_admin_logo_url', trim($_POST['kwke_admin_logo_url'] ?? '') ?: '/user/plugins/kwke-backend/assets/img/logo_white.png' );
    yourls_update_option( 'kwke_login_logo_url', trim($_POST['kwke_login_logo_url'] ?? '') ?: '/user/plugins/kwke-backend/assets/img/logo_white.png' );
    yourls_update_option( 'kwke_favicon_url', trim($_POST['kwke_favicon_url'] ?? '') ?: '/assets/img/favicon.ico' );
    yourls_update_option( 'kwke_theme_mode', sanitize_text_field( $_POST['kwke_theme_mode'] ?? 'dark' ) );
    yourls_update_option( 'kwke_theme_color', sanitize_hex_color( $_POST['kwke_theme_color'] ?? '#7c3aed' ) );
    yourls_update_option( 'kwke_wallpaper', sanitize_text_field( $_POST['kwke_wallpaper'] ?? 'none' ) );
    yourls_update_option( 'kwke_wallpaper_custom_url', esc_url_raw( $_POST['kwke_wallpaper_custom_url'] ?? '' ) );
    yourls_update_option( 'kwke_allow_public', isset( $_POST['kwke_allow_public'] ) );
    yourls_update_option( 'kwke_show_custom_url', isset( $_POST['kwke_show_custom_url'] ) );
    yourls_update_option( 'kwke_tracking_code', trim($_POST['kwke_tracking_code'] ?? '') );

    // 解析页脚链接
    $footer_links_text = trim($_POST['kwke_footer_links'] ?? '');
    $footer_links = [];
    if ($footer_links_text) {
        $lines = explode("\n", $footer_links_text);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            $parts = explode('|', $line, 2);
            if (count($parts) === 2) {
                $footer_links[] = [
                    'name' => trim($parts[0]),
                    'url' => trim($parts[1])
                ];
            }
        }
    }
    if (empty($footer_links)) {
        $footer_links = [
            ['name' => '关于', 'url' => 'https://zwrt.de/'],
            ['name' => '管理后台', 'url' => '/admin']
        ];
    }
    yourls_update_option( 'kwke_footer_links', $footer_links );
}

// ========== 辅助函数 ==========
if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str) { return trim(strip_tags($str)); }
}
if (!function_exists('esc_url_raw')) {
    function esc_url_raw($url) { return filter_var($url, FILTER_SANITIZE_URL); }
}
if (!function_exists('sanitize_hex_color')) {
    function sanitize_hex_color($color) {
        return preg_match('/^#[0-9A-Fa-f]{6}$/', $color) ? $color : '#7c3aed';
    }
}

// ========== 导航链接 ==========
yourls_add_filter( 'admin_links', 'kwke_admin_links' );
function kwke_admin_links( $admin_links ) {
    if ( true !== yourls_is_valid_user() ) { $admin_links = []; }
    $admin_links['frontend'] = [
        'url'    => YOURLS_SITE . '/index.php',
        'anchor' => '<i class="material-icons">arrow_back</i> 返回首页'
    ];
    return $admin_links;
}

// 去掉 Help 链接（ozh.org）
yourls_add_filter( 'help_link', 'kwke_help_link' );
function kwke_help_link( $help_link ) { return ''; }

// 去掉页脚的 "Powered by" / ozh 链接
yourls_add_action( 'html_footer', 'kwke_remove_footer_links' );
function kwke_remove_footer_links() {
    echo '<style>footer a[href*="ozh.org"], footer a[href*="yourls.org"] { display: none !important; }</style>';
}
