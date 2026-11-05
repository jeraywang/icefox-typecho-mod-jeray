<?php

/**
 * 前端发布文章
 *
 * @package custom
 */
// 检查用户是否登录
if (!$this->user->hasLogin()) {
    // 用户未登录，跳转到登录页面
    // 设置 HTTP 重定向头  
    header('Location: ' . $this->options->adminUrl);
    // 确保重定向后停止执行后续代码  
    exit;
}
$security = $this->widget('Widget_Security');
?>

<html>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="viewport" content="width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <title>
        发布朋友圈 - <?php echo $this->options->title ?>
    </title>
    <!-- 使用url函数转换相关路径 -->
    <link rel="stylesheet" href="<?php $this->options->themeUrl('assets/css/style.css'); ?>?v=<?php echo __THEME_VERSION__; ?>">
    <link rel="stylesheet" href="<?php $this->options->themeUrl('assets/css/viewer.min.css'); ?>">
    <link rel="stylesheet" href="<?php $this->options->themeUrl('uno.css'); ?>?v=<?php echo __THEME_VERSION__; ?>">
    <link rel="stylesheet" href="<?php $this->options->themeUrl('assets/css/fancybox.css'); ?>">
    <link rel="stylesheet" href="<?php $this->options->themeUrl('assets/css/plyr.css'); ?>">
    <style>
        <?php echo $this->options->css; ?>
    </style>
    <!-- 2026-10-21：发布页音乐/视频卡片样式补丁。
         uno.css 是预编译的，卡片标记里大量 bg-[#xxx]、rounded-xl 等任意值类根本不存在，
         导致两张卡片裸奔。这里用 [class~=...] 属性选择器按原设计补齐缺失的工具类，
         JS 切换 tab 时增删的类名同样生效；全部作用域限定在两张卡片内，不影响页面其他部分。 -->
    <style id="publish-card-styles">
        /* ===== 卡片容器 ===== */
        #music-publish-card, #video-publish-card {
            margin: .75rem 1.25rem;
            padding: 1rem;
            background: #f8f9fa;
            border: 1px solid #ebebeb;
            border-radius: .75rem;
        }
        /* ===== 布局工具 ===== */
        #music-publish-card [class~="flex"], #video-publish-card [class~="flex"] { display: flex; }
        #music-publish-card [class~="flex-row"], #video-publish-card [class~="flex-row"] { flex-direction: row; }
        #music-publish-card [class~="flex-1"], #video-publish-card [class~="flex-1"] { flex: 1 1 0%; min-width: 0; }
        #music-publish-card [class~="justify-between"], #video-publish-card [class~="justify-between"] { justify-content: space-between; }
        #music-publish-card [class~="items-center"], #video-publish-card [class~="items-center"] { align-items: center; }
        #music-publish-card [class~="gap-2"], #video-publish-card [class~="gap-2"] { gap: .5rem; }
        #music-publish-card [class~="gap-3"], #video-publish-card [class~="gap-3"] { gap: .75rem; }
        #music-publish-card [class~="w-full"], #video-publish-card [class~="w-full"] { width: 100%; }
        #music-publish-card [class~="mt-1\.5"], #video-publish-card [class~="mt-1\.5"] { margin-top: .375rem; }
        #music-publish-card [class~="mt-2"], #video-publish-card [class~="mt-2"] { margin-top: .5rem; }
        #music-publish-card [class~="mt-3"], #video-publish-card [class~="mt-3"] { margin-top: .75rem; }
        #music-publish-card [class~="mt-3\.5"], #video-publish-card [class~="mt-3\.5"] { margin-top: .875rem; }
        #music-publish-card [class~="mb-1\.5"], #video-publish-card [class~="mb-1\.5"] { margin-bottom: .375rem; }
        #music-publish-card [class~="pt-1"], #video-publish-card [class~="pt-1"] { padding-top: .25rem; }
        #music-publish-card [class~="pt-3"], #video-publish-card [class~="pt-3"] { padding-top: .75rem; }
        #music-publish-card [class~="p-2"], #video-publish-card [class~="p-2"] { padding: .5rem; }
        #music-publish-card [class~="py-2"], #video-publish-card [class~="py-2"] { padding-top: .5rem; padding-bottom: .5rem; }
        #music-publish-card [class~="py-3"], #video-publish-card [class~="py-3"] { padding-top: .75rem; padding-bottom: .75rem; }
        #music-publish-card [class~="px-3"], #video-publish-card [class~="px-3"] { padding-left: .75rem; padding-right: .75rem; }
        #music-publish-card [class~="h-16"], #video-publish-card [class~="h-16"] { height: 4rem; }
        #music-publish-card [class~="w-12"], #video-publish-card [class~="w-12"] { width: 3rem; }
        #music-publish-card [class~="h-12"], #video-publish-card [class~="h-12"] { height: 3rem; }
        #music-publish-card [class~="p-2\.5"], #video-publish-card [class~="p-2\.5"] { padding: .625rem; }
        #music-publish-card [class~="p-3"], #video-publish-card [class~="p-3"] { padding: .75rem; }
        #music-publish-card [class~="px-2\.5"], #video-publish-card [class~="px-2\.5"] { padding-left: .625rem; padding-right: .625rem; }
        #music-publish-card [class~="py-1"], #video-publish-card [class~="py-1"] { padding-top: .25rem; padding-bottom: .25rem; }
        #music-publish-card [class~="mr-3"], #video-publish-card [class~="mr-3"] { margin-right: .75rem; }
        #music-publish-card [class~="mt-0\.5"], #video-publish-card [class~="mt-0\.5"] { margin-top: .125rem; }
        #music-publish-card [class~="space-y-2\.5"] > * + *, #video-publish-card [class~="space-y-2\.5"] > * + * { margin-top: .625rem; }
        #music-publish-card [class~="overflow-hidden"], #video-publish-card [class~="overflow-hidden"] { overflow: hidden; }
        #music-publish-card [class~="shrink-0"], #video-publish-card [class~="shrink-0"],
        #music-publish-card [class~="flex-shrink-0"], #video-publish-card [class~="flex-shrink-0"] { flex-shrink: 0; }
        #music-publish-card [class~="object-cover"], #video-publish-card [class~="object-cover"] { object-fit: cover; }
        /* 关键：卡片内多个元素是「hidden + flex」双类，上面补的 display:flex 特异性高于 uno 的 .hidden，
           必须显式压回，否则 music-preview-card 等一进页面就会露出来 */
        #music-publish-card [class~="hidden"], #video-publish-card [class~="hidden"] { display: none; }
        /* ===== 边框 ===== */
        #music-publish-card [class~="border"], #video-publish-card [class~="border"] { border-width: 1px; border-style: solid; border-color: #e5e7eb; }
        #music-publish-card [class~="border-0"], #video-publish-card [class~="border-0"] { border-width: 0; }
        #music-publish-card [class~="border-2"], #video-publish-card [class~="border-2"] { border-width: 2px; }
        #music-publish-card [class~="border-t"], #video-publish-card [class~="border-t"] { border-top-width: 1px; border-top-style: solid; border-top-color: #e5e7eb; }
        #music-publish-card [class~="border-dashed"], #video-publish-card [class~="border-dashed"] { border-style: dashed; }
        #music-publish-card [class~="border-transparent"], #video-publish-card [class~="border-transparent"] { border-color: transparent; }
        #music-publish-card [class~="border-[#ebebeb]"], #video-publish-card [class~="border-[#ebebeb]"] { border-color: #ebebeb; }
        #music-publish-card [class~="border-[#c7cbd1]"], #video-publish-card [class~="border-[#c7cbd1]"] { border-color: #c7cbd1; }
        #music-publish-card [class~="border-[#e5e7eb]"], #video-publish-card [class~="border-[#e5e7eb]"] { border-color: #e5e7eb; }
        #music-publish-card [class~="border-[#07c160]"], #video-publish-card [class~="border-[#07c160]"] { border-color: #07c160; }
        #music-publish-card [class~="border-[#f3c1c1]"], #video-publish-card [class~="border-[#f3c1c1]"] { border-color: #f3c1c1; }
        #music-publish-card [class~="border-red-200"], #video-publish-card [class~="border-red-200"] { border-color: #fecaca; }
        #music-publish-card [class~="border-gray-200"], #video-publish-card [class~="border-gray-200"] { border-color: #e5e7eb; }
        #music-publish-card [class~="border-gray-300"], #video-publish-card [class~="border-gray-300"] { border-color: #d1d5db; }
        /* ===== 圆角 / 阴影 / 交互 ===== */
        #music-publish-card [class~="rounded"], #video-publish-card [class~="rounded"] { border-radius: .25rem; }
        #music-publish-card [class~="rounded-lg"], #video-publish-card [class~="rounded-lg"] { border-radius: .5rem; }
        #music-publish-card [class~="shadow-xs"], #video-publish-card [class~="shadow-xs"] { box-shadow: 0 1px 2px rgba(0, 0, 0, .05); }
        #music-publish-card [class~="cursor-pointer"], #video-publish-card [class~="cursor-pointer"] { cursor: pointer; }
        #music-publish-card [class~="transition"], #video-publish-card [class~="transition"] { transition: all .15s ease; }
        #music-publish-card [class~="outline-none"], #video-publish-card [class~="outline-none"] { outline: none; }
        #music-publish-card [class~="resize-none"], #video-publish-card [class~="resize-none"] { resize: none; }
        #music-publish-card [class~="truncate"], #video-publish-card [class~="truncate"] { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        /* ===== 字号 / 字重 / 颜色 ===== */
        #music-publish-card [class~="text-xs"], #video-publish-card [class~="text-xs"] { font-size: .75rem; line-height: 1.05rem; }
        #music-publish-card [class~="text-sm"], #video-publish-card [class~="text-sm"] { font-size: .875rem; line-height: 1.25rem; }
        #music-publish-card [class~="text-center"], #video-publish-card [class~="text-center"] { text-align: center; }
        #music-publish-card [class~="font-normal"], #video-publish-card [class~="font-normal"] { font-weight: 400; }
        #music-publish-card [class~="font-medium"], #video-publish-card [class~="font-medium"] { font-weight: 500; }
        #music-publish-card [class~="font-semibold"], #video-publish-card [class~="font-semibold"] { font-weight: 600; }
        #music-publish-card [class~="font-bold"], #video-publish-card [class~="font-bold"] { font-weight: 700; }
        #music-publish-card [class~="font-mono"], #video-publish-card [class~="font-mono"] { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; }
        #music-publish-card [class~="leading-relaxed"], #video-publish-card [class~="leading-relaxed"] { line-height: 1.625; }
        #music-publish-card [class~="text-white"], #video-publish-card [class~="text-white"] { color: #fff; }
        #music-publish-card [class~="text-gray-400"], #video-publish-card [class~="text-gray-400"] { color: #9ca3af; }
        #music-publish-card [class~="text-gray-500"], #video-publish-card [class~="text-gray-500"] { color: #6b7280; }
        #music-publish-card [class~="text-gray-600"], #video-publish-card [class~="text-gray-600"] { color: #4b5563; }
        #music-publish-card [class~="text-gray-700"], #video-publish-card [class~="text-gray-700"] { color: #374151; }
        #music-publish-card [class~="text-gray-800"], #video-publish-card [class~="text-gray-800"] { color: #1f2937; }
        #music-publish-card [class~="text-red-500"], #video-publish-card [class~="text-red-500"] { color: #ef4444; }
        #music-publish-card [class~="text-[#07c160]"], #video-publish-card [class~="text-[#07c160]"] { color: #07c160; }
        #music-publish-card [class~="text-[#b45309]"], #video-publish-card [class~="text-[#b45309]"] { color: #b45309; }
        /* ===== 背景 ===== */
        #music-publish-card [class~="bg-white"], #video-publish-card [class~="bg-white"] { background-color: #fff; }
        #music-publish-card [class~="bg-white/70"], #video-publish-card [class~="bg-white/70"] { background-color: rgba(255, 255, 255, .7); }
        #music-publish-card [class~="bg-black"], #video-publish-card [class~="bg-black"] { background-color: #000; }
        #music-publish-card [class~="bg-[#f0f0f0]"], #video-publish-card [class~="bg-[#f0f0f0]"] { background-color: #f0f0f0; }
        #music-publish-card [class~="bg-gray-100"], #video-publish-card [class~="bg-gray-100"] { background-color: #f3f4f6; }
        #music-publish-card [class~="bg-red-50"], #video-publish-card [class~="bg-red-50"] { background-color: #fef2f2; }
        #music-publish-card [class~="bg-[#333]"], #video-publish-card [class~="bg-[#333]"] { background-color: #333; }
        #music-publish-card [class~="bg-[#07c160]"], #video-publish-card [class~="bg-[#07c160]"] { background-color: #07c160; }
        #music-publish-card [class~="bg-[#383838]"], #video-publish-card [class~="bg-[#383838]"] { background-color: #383838; }
        #music-publish-card [class~="hover:bg-gray-50"]:hover, #video-publish-card [class~="hover:bg-gray-50"]:hover { background-color: #f9fafb; }
        #music-publish-card [class~="hover:bg-red-50"]:hover, #video-publish-card [class~="hover:bg-red-50"]:hover { background-color: #fef2f2; }
        #music-publish-card [class~="hover:bg-[#06ad56]"]:hover, #video-publish-card [class~="hover:bg-[#06ad56]"]:hover { background-color: #06ad56; }
        #music-publish-card [class~="hover:border-[#07c160]"]:hover, #video-publish-card [class~="hover:border-[#07c160]"]:hover { border-color: #07c160; }
        #music-publish-card [class~="hover:text-[#07c160]"]:hover, #video-publish-card [class~="hover:text-[#07c160]"]:hover { color: #07c160; }
        #music-publish-card [class~="hover:text-red-600"]:hover, #video-publish-card [class~="hover:text-red-600"]:hover { color: #dc2626; }
        #music-publish-card [class~="focus:border-[#07c160]"]:focus, #video-publish-card [class~="focus:border-[#07c160]"]:focus { border-color: #07c160; }
        /* ===== 深色模式（html.dark）===== */
        .dark #music-publish-card, .dark #video-publish-card { background: #2c2c2c; border-color: #3d3d3d; }
        .dark #music-publish-card [class~="dark:bg-[#353535]"], .dark #video-publish-card [class~="dark:bg-[#353535]"] { background-color: #353535; }
        .dark #music-publish-card [class~="dark:bg-[#383838]"], .dark #video-publish-card [class~="dark:bg-[#383838]"] { background-color: #383838; }
        .dark #music-publish-card [class~="dark:bg-[#333]"], .dark #video-publish-card [class~="dark:bg-[#333]"] { background-color: #333; }
        .dark #music-publish-card [class~="dark:border-[#3d3d3d]"], .dark #video-publish-card [class~="dark:border-[#3d3d3d]"] { border-color: #3d3d3d; }
        .dark #music-publish-card [class~="dark:border-[#4d4d4d]"], .dark #video-publish-card [class~="dark:border-[#4d4d4d]"] { border-color: #4d4d4d; }
        .dark #music-publish-card [class~="dark:border-gray-600"], .dark #video-publish-card [class~="dark:border-gray-600"] { border-color: #4b5563; }
        .dark #music-publish-card [class~="dark:border-gray-700"], .dark #video-publish-card [class~="dark:border-gray-700"] { border-color: #374151; }
        .dark #music-publish-card [class~="dark:bg-red-900/30"], .dark #video-publish-card [class~="dark:bg-red-900/30"] { background-color: rgba(127, 29, 29, .3); }
        .dark #music-publish-card [class~="dark:text-gray-200"], .dark #video-publish-card [class~="dark:text-gray-200"] { color: #e5e7eb; }
        .dark #music-publish-card [class~="dark:text-gray-300"], .dark #video-publish-card [class~="dark:text-gray-300"] { color: #d1d5db; }
        .dark #music-publish-card [class~="dark:text-gray-400"], .dark #video-publish-card [class~="dark:text-gray-400"] { color: #9ca3af; }
    </style>
    <!-- 2026-10-21：发布框现代化。两行起步随内容自动增高、细滚动条（不悬停不显色）、
         右下角字数统计、Ctrl/⌘+Enter 快捷发布；聚焦指示改为左侧绿条，替代原来包住输入框的粗绿框。 -->
    <style id="composer-styles">
        #composer-wrap { position: relative; }

        /* 聚焦指示：左侧 2px 绿条（键盘用户同样可见，但不再像"报错框"） */
        #composer-wrap::before {
            content: "";
            position: absolute;
            left: -.25rem;
            top: .35rem;
            bottom: 1.5rem;
            width: 2px;
            border-radius: 2px;
            background: #07c160;
            opacity: 0;
            transition: opacity .18s ease;
        }
        #composer-wrap:focus-within::before { opacity: 1; }

        /* 输入框：min-height 约两行，随内容增长，超过 42vh 才内部滚动 */
        #text {
            display: block;
            min-height: 4.75rem;
            max-height: 42vh;
            line-height: 1.7;
            padding: .5rem .25rem .75rem;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: #d5d8dd transparent;
            field-sizing: content;   /* Chrome 123+ 原生自动增高；老浏览器由 edit.js 兜底 */
            caret-color: #07c160;
        }
        /* 输入框聚焦不再画框，交给左侧绿条 */
        #text:focus,
        #text:focus-visible { outline: none; box-shadow: none; }
        #text::placeholder { color: #b6bcc6; }

        /* 滚动条：平时透明，悬停才显形，避免内容一多就冒出系统灰条 */
        #text::-webkit-scrollbar { width: 6px; }
        #text::-webkit-scrollbar-track { background: transparent; }
        #text::-webkit-scrollbar-thumb { background: transparent; border-radius: 999px; }
        #text:hover::-webkit-scrollbar-thumb { background: #dcdfe4; }
        #text::-webkit-scrollbar-thumb:hover { background: #c3c7ce; }

        /* 底部信息行：左快捷键提示、右字数 */
        .composer-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .5rem;
            margin-top: .125rem;
            min-height: 1.125rem;
            font-size: .6875rem;
            line-height: 1;
            user-select: none;
        }
        .composer-hint { color: #9aa1ab; opacity: 0; transition: opacity .18s ease; }
        #composer-wrap:focus-within .composer-hint { opacity: 1; }
        .composer-count {
            margin-left: auto;
            color: #9ca3af;
            font-variant-numeric: tabular-nums;
            transition: color .18s ease;
        }
        .composer-count.is-warn { color: #d97706; }
        .composer-count.is-over { color: #ef4444; }

        .dark #text::placeholder { color: #6b7280; }
        .dark #text:hover::-webkit-scrollbar-thumb { background: #4a4a4a; }
        .dark .composer-hint { color: #6b7280; }
        .dark .composer-count { color: #8a8f98; }

        /* 手机端没有 Ctrl 键，隐藏快捷键提示；触屏没有 hover，滚动条直接显示 */
        @media (max-width: 800px) {
            .composer-hint { display: none; }
            #text::-webkit-scrollbar-thumb { background: #dcdfe4; }
            .dark #text::-webkit-scrollbar-thumb { background: #4a4a4a; }
        }
    </style>
    <script type="text/javascript" src="<?php $this->options->themeUrl('assets/js/jquery.min.js'); ?>"></script>
    <script type="text/javascript" src="<?php $this->options->themeUrl('assets/js/jqueryui.min.js'); ?>"></script>
    <script type="text/javascript" src="<?php $this->options->themeUrl('assets/js/jquery.ui.touch.punch.min.js'); ?>"></script>
    <script type="text/javascript" src="<?php $this->options->themeUrl('assets/js/axios.min.js'); ?>"></script>
    <script type="text/javascript" src="<?php $this->options->themeUrl('assets/js/viewer.js'); ?>"></script>
    <script type="text/javascript" src="<?php $this->options->themeUrl('assets/js/scrollload.min.js'); ?>"></script>
    <script defer src="<?php $this->options->themeUrl('assets/js/alpine.3.13.3.min.js'); ?>"></script>
    <script type="text/javascript" src="<?php $this->options->themeUrl('assets/js/fancybox.umd.js'); ?>"></script>
    <script type="text/javascript" src="<?php $this->options->themeUrl('assets/js/lazyload.min.js'); ?>"></script>
    <script type="text/javascript" src="<?php $this->options->themeUrl('assets/js/anime.min.js'); ?>"></script>
    <script type="text/javascript" src="<?php $this->options->themeUrl('assets/js/hls.min.js'); ?>"></script>
    <script type="text/javascript" src="<?php $this->options->themeUrl('assets/js/Sortable.min.js'); ?>"></script>
    <script type="text/javascript">
        window.SITE_API_MUSIC_URL = '<?php echo rtrim(getWebsiteHomeUrl(), '/') . '/api/music-info'; ?>';
        window.WEBSITE_HOME_URL = '<?php echo getWebsiteHomeUrl(); ?>';
    </script>
    <!-- 2026-10-19：edit.js 改用独立缓存参数（跟着代码改动手动递增）。
         原先跟 __THEME_VERSION__(2.1.2) 走，主题版本不变时 CDN/浏览器一直回旧脚本，
         导致新加的视频按钮没有任何点击响应。改 edit.js 后请把 EDIT_JS_VER 再 +1。 -->
    <script type="text/javascript" src="<?php $this->options->themeUrl('assets/js/edit.js'); ?>?v=20261026a"></script>
    <script type="text/javascript">
        <?php echo $this->options->script; ?>
    </script> <!-- 通过自有函数输出HTML头部信息 -->
    <?php $this->header(); ?>
</head>

<body :class="{'dark':darkMode}" x-data="{darkMode:<?php echo $this->options->defaultThemeColor == 'yes' ? 'true' : 'false'; ?>}">
    <div class="bg-[#f0f0f0] dark:bg-[#262626]">
        <div style="min-height:100%">
            <form action="<?php $security->index('/action/contents-post-edit'); ?>" method="post" name="write_post">
                <div class="bg-white dark:bg-[#323232] dark:text-[#cccccc] mx-auto main-container" :class="{'dark':darkMode}">
                    <div class="h-14 bg-[#f0f0f0] dark:bg-[#2b2b2b] flex flex-row justify-between items-center px-5 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center gap-2">
                            <a href="<?php $this->options->siteUrl(); ?>" class="flex items-center"><img src="<?php $this->options->themeUrl('assets/svgs/btn-left.svg'); ?>" class="w-[24px] h-[24px]" /></a>
                            <span class="font-bold text-base text-gray-800 dark:text-gray-100">发布</span>
                        </div>
                        <div>
                            <button class="btn-comment bg-[#07c160] hover:bg-[#06ad56] border-0 outline-none text-white cursor-pointer rounded text-sm px-5 py-2 font-medium transition">发布</button>
                        </div>
                    </div>
                    <!-- 2026-10-21：现代发布框 —— 两行起步自动增高、细滚动条、右下角字数、Ctrl/⌘+Enter 发布 -->
                    <div class="mt-3 px-5" id="composer-wrap">
                        <textarea placeholder="说点什么…" rows="2" class="w-full p-2 border-none resize-none outline-none dark:bg-[#323232] dark:text-[#cccccc] text-sm" name="text" id="text"></textarea>
                        <div class="composer-meta">
                            <span class="composer-hint">Ctrl / ⌘ + Enter 发布</span>
                            <span class="composer-count" id="composer-count">0</span>
                        </div>
                    </div>

                    <!-- 工具栏：1. 表情，2. 音乐（前台化编辑），3. 文字发布（默认） -->
                    <div class="mt-3 px-5 flex items-center gap-3">
                        <!-- 按钮 1：表情选择 -->
                        <span class="w-9 h-9 rounded-full bg-[#f0f0f0] dark:bg-[#3a3a3a] flex items-center justify-center cursor-pointer face-btn hover:opacity-80 transition" id="face-toggle-btn" title="选择表情">
                            <img src="<?php $this->options->themeUrl('assets/svgs/smile.svg'); ?>" class="w-[18px] h-[18px]" />
                        </span>

                        <!-- 按钮 2：音乐按钮（实现把文章编辑页面音乐模块前台化） -->
                        <span class="w-9 h-9 rounded-full bg-[#f0f0f0] dark:bg-[#3a3a3a] text-gray-600 dark:text-gray-300 flex items-center justify-center cursor-pointer music-toggle-btn hover:opacity-80 transition" id="music-toggle-btn" title="插入音乐">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 18V5l12-2v13"></path>
                                <circle cx="6" cy="18" r="3"></circle>
                                <circle cx="18" cy="16" r="3"></circle>
                            </svg>
                        </span>

                        <!-- 按钮 2.5：视频按钮（2026-10-19 新增：本地视频上传 / 外部视频链接） -->
                        <span class="w-9 h-9 rounded-full bg-[#f0f0f0] dark:bg-[#3a3a3a] text-gray-600 dark:text-gray-300 flex items-center justify-center cursor-pointer video-toggle-btn hover:opacity-80 transition" id="video-toggle-btn" title="插入视频">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                                <path d="M10 9l5 3-5 3z"></path>
                            </svg>
                        </span>

                        <!-- 按钮 3：文字发布（默认激活高亮） -->
                        <span class="w-9 h-9 rounded-full bg-[#e8f7ee] dark:bg-[#1e3a2b] text-[#07c160] flex items-center justify-center cursor-pointer text-mode-btn hover:opacity-80 transition" id="text-mode-btn" title="文字发布">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                        </span>
                    </div>

                    <!-- 表情面板（2026-10-26：移到这里，保证音乐/视频任一模式下展开都显示在上方） -->
                    <div class="mt-2 px-5 face-container hidden" data-cid="${cid}" data-coid="${coid}">
                        <span class="cursor-pointer face-item">😀</span>
                        <span class="cursor-pointer face-item">😄</span>
                        <span class="cursor-pointer face-item">😁</span>
                        <span class="cursor-pointer face-item">😆</span>
                        <span class="cursor-pointer face-item">😅</span>
                        <span class="cursor-pointer face-item">😂</span>
                        <span class="cursor-pointer face-item">🤣</span>
                        <span class="cursor-pointer face-item">😊</span>
                        <span class="cursor-pointer face-item">😇</span>
                        <span class="cursor-pointer face-item">🙂</span>
                        <span class="cursor-pointer face-item">🙃</span>
                        <span class="cursor-pointer face-item">😉</span>
                        <span class="cursor-pointer face-item">😌</span>
                        <span class="cursor-pointer face-item">😍</span>
                        <span class="cursor-pointer face-item">🥰</span>
                        <span class="cursor-pointer face-item">😘</span>
                        <span class="cursor-pointer face-item">😗</span>
                        <span class="cursor-pointer face-item">😙</span>
                        <span class="cursor-pointer face-item">😚</span>
                        <span class="cursor-pointer face-item">😋</span>
                        <span class="cursor-pointer face-item">😛</span>
                        <span class="cursor-pointer face-item">😝</span>
                        <span class="cursor-pointer face-item">😜</span>
                        <span class="cursor-pointer face-item">🤪</span>
                        <span class="cursor-pointer face-item">🤨</span>
                        <span class="cursor-pointer face-item">🧐</span>
                        <span class="cursor-pointer face-item">🤓</span>
                        <span class="cursor-pointer face-item">😎</span>
                        <span class="cursor-pointer face-item">🤩</span>
                        <span class="cursor-pointer face-item">🥳</span>
                        <span class="cursor-pointer face-item">😏</span>
                        <span class="cursor-pointer face-item">😒</span>
                        <span class="cursor-pointer face-item">😞</span>
                        <span class="cursor-pointer face-item">😔</span>
                        <span class="cursor-pointer face-item">😟</span>
                        <span class="cursor-pointer face-item">😕</span>
                        <span class="cursor-pointer face-item">🙁</span>
                        <span class="cursor-pointer face-item">☹️</span>
                        <span class="cursor-pointer face-item">😣</span>
                        <span class="cursor-pointer face-item">😖</span>
                        <span class="cursor-pointer face-item">😫</span>
                        <span class="cursor-pointer face-item">😩</span>
                        <span class="cursor-pointer face-item">🥺</span>
                        <span class="cursor-pointer face-item">😢</span>
                        <span class="cursor-pointer face-item">😭</span>
                        <span class="cursor-pointer face-item">😤</span>
                        <span class="cursor-pointer face-item">😠</span>
                        <span class="cursor-pointer face-item">😡</span>
                        <span class="cursor-pointer face-item">🤬</span>
                        <span class="cursor-pointer face-item">🤯</span>
                        <span class="cursor-pointer face-item">😳</span>
                        <span class="cursor-pointer face-item">🥵</span>
                        <span class="cursor-pointer face-item">🥶</span>
                        <span class="cursor-pointer face-item">😱</span>
                        <span class="cursor-pointer face-item">😨</span>
                        <span class="cursor-pointer face-item">😰</span>
                        <span class="cursor-pointer face-item">😥</span>
                        <span class="cursor-pointer face-item">😓</span>
                        <span class="cursor-pointer face-item">🤗</span>
                        <span class="cursor-pointer face-item">🤔</span>
                        <span class="cursor-pointer face-item">🤭</span>
                        <span class="cursor-pointer face-item">🤫</span>
                        <span class="cursor-pointer face-item">🤥</span>
                        <span class="cursor-pointer face-item">😶</span>
                        <span class="cursor-pointer face-item">😐</span>
                        <span class="cursor-pointer face-item">😑</span>
                        <span class="cursor-pointer face-item">😬</span>
                        <span class="cursor-pointer face-item">🙄</span>
                        <span class="cursor-pointer face-item">😯</span>
                        <span class="cursor-pointer face-item">😦</span>
                        <span class="cursor-pointer face-item">😧</span>
                        <span class="cursor-pointer face-item">😮</span>
                        <span class="cursor-pointer face-item">😲</span>
                        <span class="cursor-pointer face-item">🥱</span>
                        <span class="cursor-pointer face-item">😴</span>
                        <span class="cursor-pointer face-item">🤤</span>
                        <span class="cursor-pointer face-item">😪</span>
                        <span class="cursor-pointer face-item">😵</span>
                        <span class="cursor-pointer face-item">🤐</span>
                        <span class="cursor-pointer face-item">🥴</span>
                        <span class="cursor-pointer face-item">🤢</span>
                        <span class="cursor-pointer face-item">🤮</span>
                        <span class="cursor-pointer face-item">🤧</span>
                        <span class="cursor-pointer face-item">😷</span>
                        <span class="cursor-pointer face-item">🤒</span>
                        <span class="cursor-pointer face-item">🤕</span>
                    </div>
                    <!-- 音乐发布卡片（把文章编辑页面的音乐自定义字段完整前台化，默认文字模式下隐藏） -->
                    <div id="music-publish-card" class="mx-5 my-3 p-4 bg-[#f8f9fa] dark:bg-[#2c2c2c] rounded-xl border border-[#ebebeb] dark:border-[#3d3d3d] hidden" style="display:none;">
                        <!-- Tab 导航 -->
                        <div class="flex flex-row gap-3">
                            <div id="tab-netease-btn" class="flex-1 py-2 text-center text-sm font-semibold rounded-lg cursor-pointer transition border border-[#07c160] text-[#07c160] bg-white dark:bg-[#353535] shadow-xs">
                                网易云 ID
                            </div>
                            <div id="tab-custom-btn" class="flex-1 py-2 text-center text-sm text-gray-500 dark:text-gray-400 rounded-lg cursor-pointer transition border border-transparent bg-white/70 dark:bg-[#383838]">
                                自定义音乐
                            </div>
                        </div>

                        <!-- Tab 1 内容：网易云 ID 快速生成 -->
                        <div id="panel-netease" class="mt-3.5 space-y-2.5">
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                填写网易云歌曲 ID 或粘贴歌曲链接，自动获取外链并填入下方文本框：
                            </div>
                            <div>
                                <input type="text" id="netease-input" placeholder="例如：447925558 或歌曲链接"
                                    class="w-full py-2 px-3 bg-white dark:bg-[#383838] rounded-lg outline-none border border-[#e5e7eb] dark:border-[#4d4d4d] focus:border-[#07c160] text-sm dark:text-gray-200" />
                            </div>
                            <div class="flex gap-3 pt-1">
                                <button type="button" id="btn-fetch-netease"
                                    class="flex-1 bg-[#07c160] hover:bg-[#06ad56] text-white py-2 rounded-lg text-sm font-medium border-0 cursor-pointer transition">
                                    获取并添加
                                </button>
                                <button type="button" id="btn-clear-netease"
                                    class="flex-1 bg-white dark:bg-[#383838] hover:bg-gray-50 text-gray-600 dark:text-gray-300 py-2 rounded-lg text-sm border border-[#e5e7eb] dark:border-[#4d4d4d] cursor-pointer transition">
                                    清除
                                </button>
                            </div>
                        </div>

                        <!-- Tab 2 内容：自定义音乐快速生成 -->
                        <div id="panel-custom" class="mt-3.5 space-y-2.5 hidden">
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                填写音乐直链、封面、歌名和歌手，自动填入下方文本框：
                            </div>
                            <div>
                                <input type="text" id="custom-url-input" placeholder="音乐链接（mp3 直链）"
                                    class="w-full py-2 px-3 bg-white dark:bg-[#383838] rounded-lg outline-none border border-[#e5e7eb] dark:border-[#4d4d4d] focus:border-[#07c160] text-sm dark:text-gray-200" />
                            </div>
                            <div>
                                <input type="text" id="custom-cover-input" placeholder="封面图片链接（可选）"
                                    class="w-full py-2 px-3 bg-white dark:bg-[#383838] rounded-lg outline-none border border-[#e5e7eb] dark:border-[#4d4d4d] focus:border-[#07c160] text-sm dark:text-gray-200" />
                            </div>
                            <div class="flex gap-2">
                                <input type="text" id="custom-title-input" placeholder="歌名"
                                    class="flex-1 py-2 px-3 bg-white dark:bg-[#383838] rounded-lg outline-none border border-[#e5e7eb] dark:border-[#4d4d4d] focus:border-[#07c160] text-sm dark:text-gray-200" />
                                <input type="text" id="custom-artist-input" placeholder="歌手"
                                    class="flex-1 py-2 px-3 bg-white dark:bg-[#383838] rounded-lg outline-none border border-[#e5e7eb] dark:border-[#4d4d4d] focus:border-[#07c160] text-sm dark:text-gray-200" />
                            </div>
                            <div class="flex gap-3 pt-1">
                                <button type="button" id="btn-add-custom"
                                    class="flex-1 bg-[#07c160] hover:bg-[#06ad56] text-white py-2 rounded-lg text-sm font-medium border-0 cursor-pointer transition">
                                    添加音乐
                                </button>
                                <button type="button" id="btn-clear-custom"
                                    class="flex-1 bg-white dark:bg-[#383838] hover:bg-gray-50 text-gray-600 dark:text-gray-300 py-2 rounded-lg text-sm border border-[#e5e7eb] dark:border-[#4d4d4d] cursor-pointer transition">
                                    清除
                                </button>
                            </div>
                        </div>

                        <!-- 自定义字段的大文本框（完整前台化，填写直接生效） -->
                        <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                            <div class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5 flex justify-between items-center">
                                <span>音乐自定义字段内容（在此框填写即可生效）：</span>
                                <span class="text-xs text-gray-400 font-normal">支持直接输入/粘贴</span>
                            </div>
                            <textarea name="fields[music]" id="music" placeholder="歌曲名称 || 专辑名称 || 播放地址 || 封面图片地址"
                                class="w-full h-16 p-2 bg-white dark:bg-[#383838] rounded-lg outline-none border border-[#e5e7eb] dark:border-[#4d4d4d] focus:border-[#07c160] text-xs font-mono dark:text-gray-200 resize-none"></textarea>

                            <!-- 原版红框里的说明文字 -->
                            <div class="mt-2 text-xs text-gray-500 dark:text-gray-400 leading-relaxed bg-[#f0f0f0] dark:bg-[#333] p-2.5 rounded-lg border border-dashed border-gray-300 dark:border-gray-600">
                                填入完整的音乐信息，格式如下（中间用两个竖线加空格 <code class="text-red-500 font-bold">||</code> 隔开）：<br>
                                <strong class="text-gray-800 dark:text-gray-200">歌曲名称 || 专辑名称 || 播放地址 || 封面图片地址</strong><br>
                                <span class="text-[#07c160] font-medium">注：网易云音乐外链地址格式为：https://music.163.com/song/media/outer/url?id=歌曲ID.mp3</span>
                            </div>
                        </div>

                        <!-- 已添加音乐预览卡片 -->
                        <div id="music-preview-card" class="mt-3 p-3 bg-white dark:bg-[#383838] rounded-lg border border-[#e5e7eb] dark:border-[#4d4d4d] hidden flex items-center justify-between">
                            <div class="flex items-center gap-3 overflow-hidden">
                                <img src="" id="music-preview-img" class="w-12 h-12 rounded object-cover flex-shrink-0 bg-gray-100 hidden" />
                                <div class="overflow-hidden">
                                    <div class="font-medium text-sm text-gray-800 dark:text-gray-200 truncate" id="music-preview-title"></div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 truncate mt-0.5" id="music-preview-artist"></div>
                                </div>
                            </div>
                            <button type="button" id="btn-remove-selected-music" class="text-xs text-red-500 hover:text-red-600 bg-red-50 dark:bg-red-900/30 px-2.5 py-1 rounded cursor-pointer border-0">
                                移除
                            </button>
                        </div>
                    </div>

                    
                    <div class="flex flex-col px-5 gap-3" id="image-upload-area">
                        <hr />
                        <div class="grid grid-cols-3 gap-3" id="sortgrid">
                            <!-- <div class="bg-[#f0f0f0] w-full aspect-square td relative">
                                <img src="http://icefox.com/usr/uploads/2024/06/2368640016.jpg"
                                    class="w-full h-full object-cover td" />
                                <div class="absolute right-3 top-3">
                                    <img src="<?php $this->options->themeUrl('assets/svgs/x.svg'); ?>"
                                        class="w-[18px] h-[18px] cursor-pointer bg-[#f0f0f0] rounded-full btn-img-remove" />
                                </div>
                            </div> -->
                        </div>

                        <div class="grid grid-cols-3 gap-3" id="sortgrid">
                            <input type="file" id="imageUpload" class="hidden" multiple accept="image/*" />
                            <div class="bg-[#f0f0f0] w-full aspect-square flex justify-center items-center cursor-pointer new-plus no-sort">
                                <img src="<?php $this->options->themeUrl('assets/svgs/plus.svg'); ?>" class="w-[18px] h-[18px] cursor-pointer" />
                            </div>
                        </div>

                        <input type="hidden" name="fields[friend_pictures]" id="friend_pictures" />
                    </div>

                    <!-- 视频发布卡片（2026-10-19 新增：本地上传 / 外部链接二选一，最终都写入 friend_video 字段；
                         主题渲染逻辑本身就是视频优先于图片，前后台一致） -->
                    <div id="video-publish-card" class="mx-5 my-3 p-4 bg-[#f8f9fa] dark:bg-[#2c2c2c] rounded-xl border border-[#ebebeb] dark:border-[#3d3d3d] hidden" style="display:none;">
                        <!-- Tab 导航 -->
                        <div class="flex flex-row gap-3">
                            <div id="video-tab-upload-btn" class="flex-1 py-2 text-center text-sm font-semibold rounded-lg cursor-pointer transition border border-[#07c160] text-[#07c160] bg-white dark:bg-[#353535] shadow-xs">
                                本地上传
                            </div>
                            <div id="video-tab-link-btn" class="flex-1 py-2 text-center text-sm text-gray-500 dark:text-gray-400 rounded-lg cursor-pointer transition border border-transparent bg-white/70 dark:bg-[#383838]">
                                视频链接
                            </div>
                        </div>

                        <!-- Tab 1：本地视频上传 -->
                        <div id="video-panel-upload" class="mt-3.5 space-y-2.5">
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                支持 MP4 / MOV / WebM，一条动态一个视频；发布后视频优先于图片展示<br>
                                视频将上传到网站服务器本地目录（走 Typecho 原生上传机制），请勿关闭页面
                            </div>
                            <input type="file" id="videoUpload" class="hidden" accept="video/mp4,video/quicktime,video/webm,.mp4,.mov,.webm" />
                            <div id="video-upload-btn"
                                class="w-full py-3 border-2 border-dashed border-[#c7cbd1] dark:border-[#4d4d4d] rounded-lg text-center text-sm text-gray-500 dark:text-gray-400 cursor-pointer hover:border-[#07c160] hover:text-[#07c160] transition">
                                点击选择本地视频
                            </div>
                            <!-- 上传后的预览 -->
                            <div id="video-preview" class="hidden">
                                <video id="video-preview-player" class="w-full rounded-lg bg-black" controls playsinline preload="metadata"></video>
                                <div class="flex justify-between items-center mt-1.5">
                                    <span id="video-filename" class="text-xs text-gray-500 dark:text-gray-400 truncate mr-3"></span>
                                    <button type="button" id="btn-remove-video"
                                        class="shrink-0 text-xs text-red-500 border border-red-200 rounded px-2.5 py-1 bg-white dark:bg-[#383838] border-[#f3c1c1] cursor-pointer hover:bg-red-50 transition">移除视频</button>
                                </div>
                            </div>
                        </div>

                        <!-- Tab 2：外部视频链接 -->
                        <div id="video-panel-link" class="mt-3.5 space-y-2.5 hidden">
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                粘贴视频直链（支持 MP4 / m3u8），例如 https://domain/xxx.mp4
                            </div>
                            <input type="text" id="videoLinkInput" placeholder="https://domain/xxx.mp4"
                                class="w-full py-2 px-3 bg-white dark:bg-[#383838] rounded-lg outline-none border border-[#e5e7eb] dark:border-[#4d4d4d] focus:border-[#07c160] text-sm dark:text-gray-200" />
                            <div id="video-link-preview" class="hidden">
                                <video id="video-link-player" class="w-full rounded-lg bg-black" controls playsinline preload="metadata"></video>
                            </div>
                        </div>

                        <!-- 最终提交的字段：与后台「视频 → 朋友圈视频」完全同一个字段 -->
                        <input type="hidden" name="fields[friend_video]" id="friend_video" />
                    </div>
                    <div class="px-5">
                        <hr />
                        <div class="flex flex-row items-center gap-3">
                            <img src="<?php $this->options->themeUrl('assets/svgs/position.svg'); ?>" class="w-[18px] h-[18px]" />
                            <input placeholder="所在位置" class="py-2 px-2 w-full outline-none border border-solid border-[#07c160]" name="fields[position]" />
                        </div>
                    </div>
                    <div class="px-5">
                        <hr />
                        <div class="flex flex-row items-center gap-3">
                            <img src="<?php $this->options->themeUrl('assets/svgs/position-link.svg'); ?>" class="w-[18px] h-[18px]" />
                            <input placeholder="定位跳转地址" class="py-2 px-2 w-full outline-none border border-solid border-[#07c160]" name="fields[positionUrl]" />
                        </div>
                    </div>
                    <div class="pb-5 px-5">
                        <hr />
                        <div class="flex flex-row justify-between py-2 ad-container">
                            <div class="flex gap-3">
                                <div class="text-xs border border-solid border-[#999]">AD</div>
                                <div class="">是否广告</div>
                            </div>
                            <div class="cursor-pointer ad-status">
                                否
                            </div>
                            <input type="hidden" name="fields[isAdvertise]" id="isAdvertise" value="0" />
                        </div>
                    </div>
                </div><!-- end #main-->

                <input type="hidden" name="visibility" value="publish" />
                <input type="hidden" name="do" value="publish" />
                <input type="hidden" name="timezone" value="28800" />
                <input type="hidden" name="allowComment" value="1" />
                <input type="hidden" name="allowPing" value="1" />
                <input type="hidden" name="allowFeed" value="1" />
                <input type="hidden" name="markdown" value="1" />
                <input type="hidden" name="title" value="<?php echo date('Y-m-d H:i:s'); ?>" id="post-title" />
                <input type="hidden" value="<?php $security->index('/action/upload'); ?>" id="upload-url" />
                <input type="hidden" class="webSiteHomeUrl" value="<?php echo getWebsiteHomeUrl(); ?>" />
                <input type="hidden" id="siteApiMusicUrl" value="<?php echo rtrim(getWebsiteHomeUrl(), '/') . '/api/music-info'; ?>" />

            </form>
        </div>
    </div>

    <input type="hidden" value="<?php $this->options->themeUrl('assets/svgs/x.svg'); ?>" id="xsvg" />
</body>

</html>