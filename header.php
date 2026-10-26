<?php
    // 从 KWKE 插件读取配置
    require_once( dirname(__FILE__).'/includes/load-yourls.php' );
    require_once( dirname(__FILE__).'/functions.php' );

    $title       = yourls_get_option( 'kwke_site_title', 'KWKE - URL Shortener' ) ?: 'KWKE - URL Shortener';
    $description = yourls_get_option( 'kwke_description', 'KWKE 短链接服务' ) ?: 'KWKE 短链接服务';
    $favicon     = yourls_get_option( 'kwke_favicon_url', '/assets/img/favicon.ico' ) ?: '/assets/img/favicon.ico';
    $logo        = yourls_get_option( 'kwke_logo_url', '/assets/img/logo-black.png' ) ?: '/assets/img/logo-black.png';
    $colour      = yourls_get_option( 'kwke_theme_color', '#7c3aed' ) ?: '#7c3aed';
    $wallpaper   = yourls_get_option( 'kwke_wallpaper', 'bird' ) ?: 'bird';
    $shortTitle  = yourls_get_option( 'kwke_short_title', 'KWKE' ) ?: 'KWKE';

    // 拼接完整 URL（如果是相对路径）
    function kwke_full_url($path) {
        return (strpos($path, 'http') === 0) ? $path : YOURLS_SITE . $path;
    }

    // 壁纸 URL
    // 小鸟壁纸 API（shanhutech.cn）
    function kwke_get_bird_wallpaper() {
        $opts = [
            'http' => [
                'method' => 'POST',
                'header'  => "Content-Type: application/x-www-form-urlencoded\r\nReferer: https://wp-front.shanhutech.cn/\r\n",
                'content' => 'pageno=1&count=20',
                'timeout' => 8,
                'user_agent' => 'Mozilla/5.0',
                'ignore_errors' => true,
            ]
        ];
        foreach (['https://wp.shanhutech.cn/intf/newestList', 'https://shcdn-wp.shanhutech.cn/intf/newestList'] as $api) {
            $raw = @file_get_contents($api, false, stream_context_create($opts));
            $d = $raw ? json_decode($raw, true) : null;
            $list = $d['data']['list'] ?? [];
            if ($list) {
                return preg_replace('#^http://#', 'https://#', (string)$list[array_rand($list)]['url']);
            }
        }
        return 'https://picsum.photos/1920/1080';
    }

    // 360 壁纸 API 函数
    function kwke_get_360_wallpaper($cid) {
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
                return str_replace('http://', 'https://', $data['data'][0]['url']);
            }
        }
        return 'https://picsum.photos/1920/1080';
    }

    $bg_url = '';
    if ($wallpaper == 'bird') {
        // 360 壁纸：随机选分类（风景/4K/小清新/动漫）
        $cids = [9, 36, 15, 26];
        $cid = $cids[array_rand($cids)];
        $bg_url = kwke_get_360_wallpaper($cid);
    } elseif ($wallpaper == 'bing') {
        // Bing 每日一图
        $bg_url = 'https://bing.biturl.top/?resolution=1920&format=image&index=0&mkt=zh-CN';
    } elseif ($wallpaper == 'scenery') {
        // 随机风景
        $bg_url = kwke_get_360_wallpaper(9); // 风景分类
    } elseif ($wallpaper == 'anime') {
        // 随机动漫
        $bg_url = kwke_get_360_wallpaper(26); // 动漫分类
    } elseif ($wallpaper == 'girl') {
        // 美女壁纸
        $bg_url = kwke_get_360_wallpaper(6); // 美女分类
    } elseif ($wallpaper == 'custom') {
        $bg_url = yourls_get_option( 'kwke_wallpaper_custom_url', '' );
    }

    // 功能开关
    $enableCustomURL = yourls_get_option( 'kwke_show_custom_url', true );
    $allowPublic     = yourls_get_option( 'kwke_allow_public', false );
    $requireAuth     = !$allowPublic;

    // 页脚链接
    $footerLinks = yourls_get_option( 'kwke_footer_links', [
        ['name' => '关于', 'url' => 'https://zwrt.de/'],
        ['name' => '管理后台', 'url' => '/admin']
    ] ) ?: [
        ['name' => '关于', 'url' => 'https://zwrt.de/'],
        ['name' => '管理后台', 'url' => '/admin']
    ];
?>
<html lang="zh-CN">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="<?php echo htmlspecialchars($description); ?>">
        <link rel="icon" href="<?php echo kwke_full_url($favicon); ?>">

        <title><?php echo htmlspecialchars($title); ?></title>

        <link rel="stylesheet" href="<?php echo kwke_full_url('/dist/styles.css'); ?>">

        <?php if ($bg_url) : ?>
            <style>
                body {
                    background: url(<?php echo $bg_url; ?>) no-repeat center center fixed !important;
                    background-size: cover !important;
                }
            </style>
        <?php else : ?>
            <style>
                body {
                    background-color: <?php echo $colour; ?>;
                }
            </style>
        <?php endif; ?>

        <style>
            .btn-primary {
                background-color: <?php echo $colour; ?>;
                border-color: <?php echo $colour; ?>;
            }
            .btn-primary:hover, .btn-primary:focus, .btn-primary:active {
                background-color: <?php echo adjustBrightness($colour, -15); ?>;
                border-color: <?php echo adjustBrightness($colour, -15); ?>;
            }
        </style>

        <style>
            /* 玻璃拟态卡片 */
            .glass-card {
                background: rgba(255, 255, 255, 0.1);
                backdrop-filter: blur(20px);
                -webkit-backdrop-filter: blur(20px);
                border: 1px solid rgba(255, 255, 255, 0.15);
                border-radius: 20px;
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            }

            /* 输入框美化 */
            .form-control {
                background: rgba(255, 255, 255, 0.9) !important;
                border: 1px solid rgba(255, 255, 255, 0.2) !important;
                border-radius: 10px !important;
                padding: 12px 16px !important;
                transition: all 0.3s ease !important;
            }
            .form-control:focus {
                box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.2) !important;
                border-color: <?php echo $colour; ?> !important;
            }

            /* 自定义链接区域 */
            #customise-link .card {
                background: rgba(255, 255, 255, 0.9) !important;
                border: none !important;
                border-radius: 10px !important;
            }
            #customise-link .card-body {
                padding: 6px 12px !important;
            }
            #customise-link .form-control {
                border: 1px solid #ccc !important;
                background: #fff !important;
            }

            /* 输入框和按钮间距 */
            .input-group .form-control {
                border-radius: 10px !important;
            }
            .input-group .btn {
                margin-top: 10px;
                border-radius: 10px !important;
            }
            @media (min-width: 768px) {
                .input-group .btn {
                    margin-top: 0 !important;
                    margin-left: 12px !important;
                }
            }

            /* 按钮美化 */
            .btn-primary {
                border-radius: 10px !important;
                font-weight: 600 !important;
                transition: all 0.3s ease !important;
                box-shadow: 0 4px 15px rgba(124, 58, 237, 0.3) !important;
            }
            .btn-primary:hover {
                transform: translateY(-2px) !important;
                box-shadow: 0 6px 20px rgba(124, 58, 237, 0.4) !important;
            }

            /* 卡片内容文字颜色 */
            .card-body h2, .card-body p, .card-body label {
                color: #fff !important;
            }
            .card-body .form-label {
                color: rgba(255, 255, 255, 0.8) !important;
            }

            /* 提示信息 */
            .info {
                color: rgba(255, 255, 255, 0.7) !important;
            }
            .info a {
                color: #fff !important;
                text-decoration: underline;
            }

            /* 动画 */
            .glass-card {
                animation: slideUp 0.5s ease;
            }
            @keyframes slideUp {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
        </style>
    </head>
