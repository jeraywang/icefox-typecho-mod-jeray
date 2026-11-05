<?php

if (!defined('__TYPECHO_ROOT_DIR__'))
    exit;

?>
<style>
.header-action-group {
    display: flex;
    align-items: center;
    gap: 8px;
    padding-right: 20px;
    height: 100%;
}
.header-action-btn {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    background: rgba(0, 0, 0, 0.16);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, 0.22);
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    color: #ffffff;
    text-decoration: none !important;
    flex-shrink: 0;
    box-sizing: border-box;
    padding: 0;
}
.header-action-btn:hover {
    background: rgba(0, 0, 0, 0.32);
    border-color: rgba(255, 255, 255, 0.45);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}
body.dark .header-action-btn {
    background: rgba(0, 0, 0, 0.32);
    border-color: rgba(255, 255, 255, 0.14);
}
body.dark .header-action-btn:hover {
    background: rgba(0, 0, 0, 0.48);
    border-color: rgba(255, 255, 255, 0.28);
}

/* 页面下拉（浅色实体导航栏）状态下的按钮适配 */
#top-fixed.is-scrolled .header-action-btn {
    background: rgba(0, 0, 0, 0.05);
    border-color: rgba(0, 0, 0, 0.08);
    color: #374151;
    backdrop-filter: none;
    -webkit-backdrop-filter: none;
}
#top-fixed.is-scrolled .header-action-btn:hover {
    background: rgba(0, 0, 0, 0.1);
    border-color: rgba(0, 0, 0, 0.16);
    color: #111827;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

/* 深色模式下的下拉状态适配 */
.dark #top-fixed.is-scrolled .header-action-btn,
body.dark #top-fixed.is-scrolled .header-action-btn {
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.14);
    color: #f3f4f6;
}
.dark #top-fixed.is-scrolled .header-action-btn:hover,
body.dark #top-fixed.is-scrolled .header-action-btn:hover {
    background: rgba(255, 255, 255, 0.18);
    border-color: rgba(255, 255, 255, 0.25);
    color: #ffffff;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
}

/* 滚动下拉时，头像按钮移除多余的深色底盘黑晕 */
#top-fixed.is-scrolled #header-user-btn.is-avatar {
    background: transparent !important;
    border-color: transparent !important;
    box-shadow: none !important;
}

/* ===== 顶部音乐进度条（2026-10-19 重做）=====
   之前用 h-[3px] / w-20 / bg-[#A2A3A1] 这些按需生成的原子类，
   但 uno.css 是预编译的静态文件，里面没有这些规则，
   导致容器高度为 0、轨道透明，进度条一直不可见。
   这里写死样式：容器恒定 80px × 4px，始终显示。 */
#top-music-progress {
    display: block;
    flex-shrink: 0;
    margin-left: 12px;
    width: 80px;
    /* 与 .w-20 / 5rem 保持一致，JS 里按 5rem 计算进度宽度 */
    height: 4px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.28);
    box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.10);
    /* 注意：不要加 overflow:hidden —— 会把播放状态的进度线光晕裁掉 */
    transition: background-color 0.25s ease, opacity 0.25s ease;
}

#top-music-jdt {
    display: block;
    height: 100%;
    width: 0;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.60);
    transition: width 0.18s linear, background-color 0.25s ease, box-shadow 0.25s ease;
}

/* 播放中：轨道更透、进度线纯白 + 发光，一眼能看出在走 */
#top-music-progress.is-playing {
    background: rgba(255, 255, 255, 0.40);
}

#top-music-progress.is-playing #top-music-jdt {
    background: #ffffff;
    box-shadow: 0 0 8px rgba(255, 255, 255, 0.95);
}

/* 顶部栏下拉后变成浅色底(#f0f0f0)，白线会看不见，切换成深色 */
#top-fixed.is-scrolled #top-music-progress {
    background: rgba(0, 0, 0, 0.14);
    box-shadow: inset 0 0 0 1px rgba(0, 0, 0, 0.06);
}

#top-fixed.is-scrolled #top-music-jdt {
    background: rgba(55, 65, 81, 0.85);
    box-shadow: none;
}

#top-fixed.is-scrolled #top-music-progress.is-playing {
    background: rgba(0, 0, 0, 0.20);
}

#top-fixed.is-scrolled #top-music-progress.is-playing #top-music-jdt {
    background: #111827;
    box-shadow: 0 0 6px rgba(0, 0, 0, 0.25);
}
</style>
<header class="h-77 w-full bg-cover relative mb-12"
    style="background-image: url('<?php echo $this->options->backgroundImageUrl ?>');background-position: center;">
    <div class="fixed top-0 left-0 w-full h-14 z-9">
        <div class="main-container mx-auto flex flex-row justify-between h-full transition-all duration-300"
            id="top-fixed">
            <div class="flex items-center">
                <span class="h-full px-5 flex items-center">
                    <?php
                    if ($this->is('single')) {
                        ?>
                        <img width="24" height="24"
                            src="<?php $this->options->themeUrl('assets/svgs/post.top.back.svg'); ?>"
                            class="cursor-pointer <?php echo $this->options->defaultThemeColor == 'yes' ? "hidden" : ""; ?> go-back mr-3"
                            id="back-light" />
                        <img width="24" height="24"
                            src="<?php $this->options->themeUrl('assets/svgs/post.top.back.dark.svg'); ?>"
                            class="cursor-pointer <?php echo $this->options->defaultThemeColor == 'yes' ? "" : "hidden"; ?> go-back mr-3"
                            id="back-dark" />
                        <?php
                    }
                    ?>
                    <?php
                    $enableTopMusic = $this->options->enableTopMusic;
                    if ($enableTopMusic === 'yes'):
                        ?>
                        <div id="top-play">
                            <img width="24" height="24" src="<?php $this->options->themeUrl('assets/svgs/btn-play.svg'); ?>"
                                class="top-play cursor-pointer <?php echo $this->options->defaultThemeColor == 'yes' ? "hidden" : ""; ?>"
                                id="top-play-light" />
                            <img width="24" height="24"
                                src="<?php $this->options->themeUrl('assets/svgs/btn-play.dark.svg'); ?>"
                                class="top-play cursor-pointer <?php echo $this->options->defaultThemeColor == 'yes' ? "" : "hidden"; ?>"
                                id="top-play-dark" />
                        </div>

                        <div id="top-pause" class="hidden">
                            <img width="24" height="24"
                                src="<?php $this->options->themeUrl('assets/svgs/btn-pause.svg'); ?>"
                                class="top-pause cursor-pointer <?php echo $this->options->defaultThemeColor == 'yes' ? "hidden" : ""; ?>"
                                id="top-pause-light" />
                            <img width="24" height="24"
                                src="<?php $this->options->themeUrl('assets/svgs/btn-pause.dark.svg'); ?>"
                                <?php /* 2026-11-02 修复：原来漏了 top-pause 类，事件委托 .top-pause 匹配不到，
                                           滚动后顶栏切到 dark 图标时点暂停无效（音乐停不下来） */ ?>
                                class="top-pause cursor-pointer <?php echo $this->options->defaultThemeColor == 'yes' ? "" : "hidden"; ?>"
                                id="top-pause-dark" />
                        </div>
                        <?php /* 2026-10-19：进度条原先用 h-[3px] / bg-[#A2A3A1] 等按需生成的类，
                                 但 uno.css 里并没有这些规则，容器高度塌成 0，进度条一直看不见。
                                 改为在下方 <style> 里写死样式，保证始终显示。 */ ?>
                        <div id="top-music-progress" title="音乐播放进度">
                            <div id="top-music-jdt"></div>
                        </div>
                    <?php endif; ?>
                </span>
            </div>
            <div class="header-action-group">
                <div class="header-action-btn" title="搜索文章（Ctrl + K）" onclick="showSearchModal();" id="search-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="7"></circle>
                        <line x1="21" y1="21" x2="16.5" y2="16.5"></line>
                    </svg>
                </div>
                <div class="header-action-btn" title="按时间查询朋友圈" onclick="showDateFilterModal();" id="calendar-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                </div>
                <div class="header-action-btn header-user-entry <?php echo $this->user->hasLogin() ? 'is-avatar' : ''; ?>" id="header-user-btn"
                    title="<?php echo $this->user->hasLogin() ? ('已登录：' . $this->user->screenName) : '用户注册 / 登录'; ?>"
                    onclick="<?php echo $this->user->hasLogin() ? 'showUserProfileModal();' : 'showUserAuthModal(\'register\');'; ?>">
                    <?php if ($this->user->hasLogin()): ?>
                        <div class="relative flex items-center justify-center" id="header-user-avatar-wrap">
                            <img width="28" height="28" src="<?php echo ($this->user->uid == 1) ? ($this->options->themeUrl . '/assets/img/avatar.webp') : getUserAvatar($this->user->uid); ?>"
                                class="rounded-full object-cover border border-white/80 dark:border-gray-500 shadow-sm" id="header-user-avatar-img" alt="个人中心" />
                            <span class="absolute bottom-[-1px] right-[-1px] w-2 h-2 bg-[#07c160] rounded-full border border-white dark:border-gray-800"></span>
                        </div>
                    <?php else: ?>
                        <div class="relative flex items-center justify-center" id="header-user-visitor-wrap" title="用户注册 / 登录">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="hover:opacity-80 transition" id="admin-user-icon">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </div>
                    <?php endif; ?>
                </div>
                <?php 
                $canPublish = $this->user->hasLogin() && ($this->user->group == 'administrator' || $this->user->group == 'editor' || $this->user->group == 'contributor');
                ?>
                <?php /* 2026-11-02：发布改为 iframe 弹窗（openPublishModal 返回 false 阻止跳页）；href 保留作为 JS 缺失时的降级入口 */ ?>
                <a href="<?php echo $this->options->publishPageUrl; ?>" id="header-publish-btn" class="header-action-btn <?php echo $canPublish ? '' : 'hidden'; ?>" title="发布朋友圈动态" onclick="return openPublishModal();" no-pjax <?php echo $canPublish ? '' : 'style="display:none;"'; ?>>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 20h9"></path>
                        <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                    </svg>
                </a>
            </div>
        </div>
    </div>
    <div class="absolute right-6 bottom-[-40] flex flex-col items-end w-90%">
        <div class="flex flex-row items-end">
            <span class="text-white mr-5 mb-6"><a href="<?php echo $this->options->about; ?>"
                    class="text-white cursor-pointer no-underline"><?php echo $this->options->avatarTitle; ?></a></span>
            <div class="w-15 h-15 rounded-lg overflow-hidden">
                <?php /* 2026-10-19：头像地址强制 https（原先后台填的是 http://，HTTPS 页面会被判为混合内容）；
                         补 aria-label 让链接有可访问名称，补 width/height 防布局抖动 */ ?>
                <a href="<?php $this->options->siteUrl(); ?>" class="cursor-pointer w-full h-full" aria-label="返回首页">
                    <?php /* 2026-11-05：顶栏大头像恢复后台可配置 —— 优先读取「站点顶部用户头像」(userAvatarUrl)，
                             未填写时回退主题内置 assets/img/avatar.webp；后台填 http:// 会自动转 https 防混合内容 */ ?>
                    <?php $topUserAvatarUrl = trim((string) $this->options->userAvatarUrl); ?>
                    <img src="<?php echo $topUserAvatarUrl !== ''
                        ? preg_replace('#^http://#i', 'https://', $topUserAvatarUrl)
                        : $this->options->themeUrl . '/assets/img/avatar.webp'; ?>"
                        width="60" height="60"
                        alt="<?php echo htmlspecialchars((string) ($this->options->avatarTitle ?: '站点头像'), ENT_QUOTES, 'UTF-8'); ?>"
                        class="w-15 h-15 object-cover cursor-pointer preview-image" /></a>
            </div>
        </div>
        <div class="text-[12px] mt-3 text-gray truncate w-full text-end">
            <?php echo $this->options->description ?>
        </div>
    </div>

</header>