<?php

if (!defined('__TYPECHO_ROOT_DIR__'))
    exit;

// 2026-10-24：HTML 页面禁止缓存。
// 手机浏览器手势返回会整页重载，若无 Cache-Control，浏览器会启发式缓存旧 HTML——
// 导致更新主题后返回时仍执行旧 JS（此前安卓手势返回恢复进度"无效"即此原因）。
// HTML 本就是动态内容，静态资源（css/js/图片）带 ?v= 版本参数，不受影响。
if (!headers_sent()) {
    header('Cache-Control: no-cache, must-revalidate');
}

?>
<!-- 2026-10-19 回滚 DOCTYPE：本主题布局按怪异模式设计（bottom-[-40]、w-90% 等类名依赖之），
     加了 DOCTYPE 会导致头部头像块从右下角跑到右上角并与图标重叠。保留 lang 属性不受影响。 -->
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    // 社交分享数据（2026-10-16）：
    // 微信分享卡片取标题用的是页面 <title> 标签（而非 og:title），所以标题计算必须放在 <title> 之前。
    $ogSiteName = $this->options->title;
    $ogDesc = $this->options->description;
    $ogImage = '';
    $ogUrl = $this->options->siteUrl;

    if ($this->is('single')) {
        // 标题：优先用文章标题，空标题则用正文首句兜底
        $ogTitle = $this->title;
        if (empty(trim($ogTitle))) {
            $tmpText = trim(strip_tags($this::markdown($this->text)));
            $tmpText = preg_replace('/\s+/', ' ', $tmpText);
            $ogTitle = mb_substr($tmpText, 0, 40, 'UTF-8');
        }
        // 描述：正文前 80 字
        $tmpText = trim(strip_tags($this::markdown($this->text)));
        $tmpText = preg_replace('/\s+/', ' ', $tmpText);
        if (!empty($tmpText)) {
            $ogDesc = mb_substr($tmpText, 0, 80, 'UTF-8');
        }
        $ogUrl = $this->permalink;

        // 首图：先取 friend_pictures 字段（逗号分隔的绝对地址），兜底取正文内嵌图
        $fp = getArticleFieldsByCid($this->cid, 'friend_pictures');
        if (count($fp) > 0 && !empty($fp[0]['str_value'])) {
            foreach (explode(',', $fp[0]['str_value']) as $p) {
                $p = trim($p);
                if ($p !== '') { $ogImage = $p; break; }
            }
        }
        if (empty($ogImage)) {
            foreach (getAllImages($this::markdown($this->text)) as $p) {
                $p = trim($p);
                if ($p !== '') { $ogImage = $p; break; }
            }
        }
        // 音乐分享封面（2026-10-23）：纯音乐文章没有 friend_pictures/正文图，
        // 从自定义字段 music（歌名 || 专辑 || 播放地址 || 封面地址）取第 4 段封面做分享图。
        // 后台「网易云 ID 获取并添加」已把封面写进该字段，这里直接复用，无需额外操作。
        if (empty($ogImage)) {
            $musicRows = getArticleFieldsByCid($this->cid, 'music');
            if (count($musicRows) > 0 && !empty($musicRows[0]['str_value'])) {
                $musicParts = array_map('trim', explode('||', $musicRows[0]['str_value']));
                if (count($musicParts) >= 4 && $musicParts[3] !== '') {
                    $ogImage = $musicParts[3];
                }
            }
        }
    } else {
        $ogTitle = $this->options->title;
        $ogImage = $this->options->userAvatarUrl;
    }

    // 规范化图片地址：确保为 https 绝对路径（微信要求）
    $ogImage = trim($ogImage);
    if ($ogImage !== '' && strpos($ogImage, '//') === 0) {
        $ogImage = 'https:' . $ogImage;
    } elseif ($ogImage !== '' && strpos($ogImage, 'http') !== 0) {
        $ogImage = rtrim($this->options->siteUrl, '/') . '/' . ltrim($ogImage, '/');
    }
    $ogImage = str_replace('http://', 'https://', $ogImage);

    // 页面标题（2026-10-19）：文章页补上品牌短名，避免搜索结果里只有半句正文。
    // 站点名形如「站点名｜副标题描述」时，取「｜」前部分作短名。
    $brandParts = preg_split('/[｜|]/u', (string) $this->options->title);
    $shortBrand = trim($brandParts[0]);
    if ($shortBrand === '') {
        $shortBrand = (string) $this->options->title;
    }
    $pageTitle = $this->is('single') ? ($ogTitle . ' - ' . $shortBrand) : $ogTitle;
    ?>
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>

    <?php
    // 规范化 canonical（2026-10-19）：
    //  - 文章页用永久链接
    //  - 首页用站点地址（日历筛选、?page=N 等参数页统一归一到首页，避免重复收录）
    //  - 其它页面暂不输出，避免给出错误的规范化地址
    $canonical = '';
    if ($this->is('single')) {
        $canonical = $this->permalink;
    } elseif ($this->is('index')) {
        $canonical = $this->options->siteUrl;
    }
    if ($canonical !== ''):
    ?>
    <link rel="canonical" href="<?php echo htmlspecialchars($canonical, ENT_QUOTES, 'UTF-8'); ?>" />
    <?php endif; ?>

    <link rel="icon" href="<?php echo htmlspecialchars(rtrim($this->options->siteUrl, '/') . '/favicon.ico', ENT_QUOTES, 'UTF-8'); ?>" sizes="any" />
    <meta name="theme-color" content="#ffffff" media="(prefers-color-scheme: light)" />
    <meta name="theme-color" content="#262626" media="(prefers-color-scheme: dark)" />

    <!-- 性能优化（2026-10-19）：
         1. 字体改为分片加载（2026-11-02，见 assets/css/fonts.css）：preload 最高频汉字片 harmony-001（59KB），
            其余分片由浏览器按 unicode-range 按需下载，新增汉字自动覆盖、免维护；
         2. 同时预加载首屏背景图（LCP 元素，205KB）。 -->
    <link rel="preload" as="font" type="font/woff2" crossorigin
        href="<?php $this->options->themeUrl('assets/fonts/slices/harmony-001.woff2'); ?>?v=1">
    <link rel="stylesheet" href="<?php $this->options->themeUrl('assets/css/fonts.css'); ?>?v=1">
    <?php if (!empty($this->options->backgroundImageUrl)): ?>
    <link rel="preload" as="image" fetchpriority="high"
        href="<?php echo htmlspecialchars($this->options->backgroundImageUrl, ENT_QUOTES, 'UTF-8'); ?>" />
    <?php endif; ?>
    <link rel="dns-prefetch" href="//cravatar.cn" />
    <link rel="stylesheet"
        href="<?php $this->options->themeUrl('assets/css/style.css'); ?>?v=20261105d">
    <!-- style.css 改动频繁且主题版本号不常变，改用独立日期版本号强制刷新缓存；下次改 style.css 后把 20261105a 递增即可 -->
    <link rel="stylesheet" href="<?php $this->options->themeUrl('uno.css'); ?>?v=<?php echo __THEME_VERSION__; ?>">
    <style>
        <?php echo $this->options->css; ?>
    </style>
    <!-- 性能优化说明（2026-10-16）：
         1. 下列脚本原本全部同步加载于 head，合计约 1.5MB，会阻塞首屏渲染，现全部改为 defer；
         2. viewer.js / viewer.common.js / viewer.min.css（约 148KB）在主题代码中从未被调用，已移除；
         3. Sortable.min.js（23KB）同样未被调用，已移除；
         4. fancybox、plyr、hls、jqueryui 等重型库改由 perf-loader.js 按需/空闲加载；
         5. intersection-observer polyfill 现代浏览器已原生支持，不再无条件加载。 -->
    <script defer src="<?php $this->options->themeUrl('assets/js/jquery.min.js'); ?>"></script>
    <script defer src="<?php $this->options->themeUrl('assets/js/axios.min.js'); ?>"></script>
    <script defer src="<?php $this->options->themeUrl('assets/js/scrollload.min.js'); ?>"></script>
    <script defer src="<?php $this->options->themeUrl('assets/js/lazyload.min.js'); ?>"></script>
    <script defer src="<?php $this->options->themeUrl('assets/js/anime.min.js'); ?>"></script>
    <!-- 2026-10-21：icefox.js 改动（音乐封面旋转状态机）后手动提升缓存参数，避免浏览器拿旧脚本 -->
    <!-- 2026-10-23：新增全站搜索（顶栏放大镜 + /api/search），手动提升缓存参数；20261023c 音乐图标跨页同步；d 滚动位置记忆；e/f 安卓手势返回；g HTML no-cache + 调试浮层；h 修复 popstate fetch 304 误判整页重载 + sessionStorage 防抖丢数据（pagehide flush + fetch cache:reload） -->
    <script defer src="<?php $this->options->themeUrl('assets/js/icefox.js'); ?>?v=20261105a"></script>
    <script defer src="<?php $this->options->themeUrl('assets/js/edit.js'); ?>?v=<?php echo __THEME_VERSION__; ?>"></script>
    <script defer src="<?php $this->options->themeUrl('assets/js/alpine.3.13.3.min.js'); ?>"></script>
    <script defer data-icefox-theme-url="<?php echo $this->options->themeUrl; ?>"
        src="<?php $this->options->themeUrl('assets/js/perf-loader.js'); ?>"></script>
    <?php if (!empty($this->options->script)): ?>
    <?php if (stripos($this->options->script, '<script') !== false): ?>
    <!-- 自定义代码已自带 <script> 标签，原样输出，不可再包一层 -->
    <?php echo $this->options->script; ?>
    <?php else: ?>
    <script>
        /* 依赖 jQuery 的自定义脚本需等 defer 脚本执行完毕 */
        document.addEventListener('DOMContentLoaded', function () {
            <?php echo $this->options->script; ?>
        });
    </script>
    <?php endif; ?>
    <?php endif; ?>
    <!-- 通过自有函数输出HTML头部信息 -->
    <?php $this->header(); ?>

    <!-- Open Graph 社交分享标签（2026-10-16）：
         计算逻辑在 <title> 之前（见文件头部注释），此处仅输出 meta。 -->
    <meta property="og:type" content="<?php echo $this->is('single') ? 'article' : 'website'; ?>" />
    <meta property="og:site_name" content="<?php echo htmlspecialchars($ogSiteName, ENT_QUOTES, 'UTF-8'); ?>" />
    <meta property="og:title" content="<?php echo htmlspecialchars($ogTitle, ENT_QUOTES, 'UTF-8'); ?>" />
    <meta property="og:description" content="<?php echo htmlspecialchars($ogDesc, ENT_QUOTES, 'UTF-8'); ?>" />
    <meta property="og:url" content="<?php echo htmlspecialchars($ogUrl, ENT_QUOTES, 'UTF-8'); ?>" />
    <?php if ($ogImage !== ''): ?><meta property="og:image" content="<?php echo htmlspecialchars($ogImage, ENT_QUOTES, 'UTF-8'); ?>" /><?php endif; ?>
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="<?php echo htmlspecialchars($ogTitle, ENT_QUOTES, 'UTF-8'); ?>" />
    <meta name="twitter:description" content="<?php echo htmlspecialchars($ogDesc, ENT_QUOTES, 'UTF-8'); ?>" />
    <?php if ($ogImage !== ''): ?><meta name="twitter:image" content="<?php echo htmlspecialchars($ogImage, ENT_QUOTES, 'UTF-8'); ?>" /><?php endif; ?>

    <script>
        /* 暗色模式：在首屏渲染前就确定主题并给 <html> 加 class，避免刷新时闪白（2026-10-19）
           优先级：localStorage 记忆 > 后台主题设置；用户手动切换后自动记忆。 */
        (function () {
            var KEY = 'icefox-theme';
            var phpDefault = <?php echo $this->options->defaultThemeColor == 'yes' ? 'true' : 'false'; ?>;
            var saved = null;
            try { saved = window.localStorage.getItem(KEY); } catch (e) { }
            var dark = saved ? (saved === 'dark') : phpDefault;
            window.__icefoxDark = dark;
            if (dark) { document.documentElement.classList.add('dark'); }
        })();
    </script>
</head>

<body :class="{'dark':darkMode}"
    x-data="{darkMode: window.__icefoxDark }"
    x-init="$watch('darkMode', function (v) {
        document.documentElement.classList.toggle('dark', v);
        try { localStorage.setItem('icefox-theme', v ? 'dark' : 'light'); } catch (e) { }
    })">

    <?php if ($ogImage !== ''): ?>
    <!-- 微信朋友圈分享缩略图（2026-10-23）：
         微信爬虫不读 og:image，而是抓取页面 body 里的第一张 ≥300px 的图片。
         这里注入一张 300x300 的分享封面，绝对定位移出屏幕外（display:none 的图微信不抓，不能用），
         正常用户看不到、不占布局，微信抓取后即作为分享卡片缩略图。 -->
    <img src="<?php echo htmlspecialchars($ogImage, ENT_QUOTES, 'UTF-8'); ?>"
        style="position:absolute;left:-9999px;top:0;width:300px;height:300px;" alt="" aria-hidden="true" />
    <?php endif; ?>

    <!-- 全局无刷新加载进度指示条 -->
    <div id="pjax-loading-bar" style="position:fixed;top:0;left:0;height:2.5px;background:#07c160;z-index:999999;width:0%;opacity:0;transition:width 0.25s ease, opacity 0.25s ease;pointer-events:none;"></div>

    <!-- 全局浮动音乐播放器（跨页面持久存在，切换页面音乐不停） -->
    <div id="music-modal" class="fixed top-24 right-2 w-20 rounded-md overflow-hidden hidden shadow-lg" style="z-index: 9990;">
        <div class="flex flex-col">
            <img src="" class="w-20 h-20 object-cover" id="fixed-music-cover" />
            <div class="bg-[#989997] flex flex-row justify-between">
                <div>
                    <img width="24" height="24" src="<?php $this->options->themeUrl('assets/svgs/fixed-music-play.svg'); ?>"
                        class="cursor-pointer" id="fixed-music-play" />
                    <img width="24" height="24" src="<?php $this->options->themeUrl('assets/svgs/fixed-music-pause.svg'); ?>"
                        class="cursor-pointer hidden" id="fixed-music-pause" />
                </div>
                <div>
                    <img width="24" height="24"
                        src="<?php $this->options->themeUrl('assets/svgs/fixed-music-close.svg'); ?>" class="cursor-pointer"
                        id="fixed-music-close" />
                </div>
            </div>
        </div>
    </div>

    <div class="bg-[#f0f0f0] dark:bg-[#262626]">
        <div style="min-height:100%" id="pjax-container">