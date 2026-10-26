# KWKE-Theme

基于 [Sleeky](https://github.com/Flynntes/Sleeky) 二次开发的 YOURLS 主题，品牌统一改名为 **KWKE**，全中文界面。

## 功能特性

- **全中文界面**：前台、后台、登录页全部中文化
- **后台美化插件**：`user/plugins/kwke-backend/`，统一管理后台主题样式
- **独立前端公开页**：根目录 `index.php`，带壁纸背景的短链接提交页
- **全局配置中心**：所有配置项集中到后台插件设置页管理
  - 首页 Logo、后台左上角 Logo、登录页 Logo 分开设置
  - 页脚链接可修改、增减
- **壁纸功能**：多种壁纸类型可选
  - 无壁纸 / 纯色
  - 360 壁纸（风景 / 4K / 小清新 / 动漫 / 美女）
  - Bing 每日一图
  - 自定义 URL
- **登录后跳转首页**：基于 `yourls_add_filter('redirect_location')` 实现，仅允许站内绝对路径，防止开放重定向

## 目录结构

```
├── index.php                     # 前端主页面（全中文短链接提交页）
├── header.php                    # 前端头部（读取配置 + 壁纸 API 逻辑）
├── footer.php                    # 前端底部
├── functions.php                 # 公共函数（adjustBrightness 等）
├── assets/                       # 前端资源（图片、sass、svg）
├── dist/styles.css               # 前端编译后样式
└── user/plugins/kwke-backend/    # 后台美化插件
    ├── plugin.php                # 后台主插件 v2.0.1（配置中心 + 登录页样式 + redirect filter）
    └── assets/                   # 插件资源（css、js、img、html）
```

## 部署

将本目录内容解压到 YOURLS 安装目录即可。登录 `https://你的域名/admin/`，进入「插件 → KWKE 后台美化」设置各项配置。

## 壁纸 API 说明

- 360 壁纸：`http://wallpaper.apc.360.cn/...`（cid：9=风景，36=4K，15=小清新，26=动漫，6=美女）
- Bing：`https://bing.biturl.top/...`

## 许可

基于 Sleeky 二次开发，请遵循原项目许可证。
