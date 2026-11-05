<?php

if (!defined('__TYPECHO_ROOT_DIR__'))
    exit;

?>

<?php /* 2026-11-05：原左下角「Powered By / 备案号」浮动块已移入登录/注册弹窗底部（modal.php .auth-modal-footer），
       备案数据仍来自后台 beian / policeBeian 设置 */ ?>

<div class="fixed right-10 bottom-10 side-area z-40">
    <!-- 日历按钮已移除：顶部导航(#calendar-icon)提供同功能，避免浮动组过高遮挡动态操作按钮 -->
    <?php /* 2026-10-19：切换按钮由 div+img 改为 <button>，可用键盘 Tab 聚焦、回车触发；
             点击后由 Alpine 的 $watch 自动写入 localStorage，刷新后记忆主题 */ ?>
    <button type="button" aria-label="切换深色 / 浅色模式"
        class="w-[36px] h-[36px] cursor-pointer rounded-3xl border-0 bg-[#E8E9EC] dark:bg-[#3d3d3d] hover:bg-[#DDDDDD] dark:hover:bg-[#4d4d4d] flex justify-center items-center mb-2 shadow-sm transition"
        @click="darkMode = !darkMode">
        <img src="<?php $this->options->themeUrl('assets/svgs/btn-moon.svg'); ?>" width="20" height="20" alt=""
            x-show="darkMode==false" />
        <img src="<?php $this->options->themeUrl('assets/svgs/btn-sun.svg'); ?>" width="20" height="20" alt=""
            x-show="darkMode==true" />

    </button>
    <button type="button" aria-label="回到顶部"
        class="w-[36px] h-[36px] cursor-pointer border-0 rounded-3xl bg-[#E8E9EC] dark:bg-[#3d3d3d] hover:bg-[#DDDDDD] dark:hover:bg-[#4d4d4d] hidden shadow-sm transition"
        onclick="scrollToTop(); return false;" id="go-top">
        <div class="w-full h-full flex justify-center items-center">
            <img src="<?php $this->options->themeUrl('assets/svgs/btn-rocket.svg'); ?>" width="20" height="20" alt="" />
        </div>
    </button>
</div>
<div class="hidden" id="top-music-container">
    <?php
    $topMusics = $this->options->topMusicList;
    if ($topMusics) {
        $topMusic_arr = preg_split('/\r\n|\n/', $topMusics);
        foreach ($topMusic_arr as $row) {
            $fl = explode('||', $row);
            ?>
            <div data-id="<?php echo $fl[0]; ?>" data-cover="<?php echo $fl[1]; ?>"></div>
            <?php
        }
    }
    ?>
</div>

<div class="hidden px-2 py-2 first-of-type:pt-2">
    <div class="bg-white dark:bg-black/30 backdrop-blur-md"></div>
    <input class="webSiteHomeUrl" value="<?php echo getWebsiteHomeUrl(); ?>" />
    <?php
    $footerCurPage = $this->_currentPage > 1 ? (int)$this->_currentPage : 1;
    $footerPageSize = (isset($this->parameter->pageSize) && $this->parameter->pageSize > 0) ? (int)$this->parameter->pageSize : 10;
    $footerTotalPage = (int)ceil($this->getTotal() / $footerPageSize);

    $footerFilterYear = isset($_GET['filter_year']) ? intval($_GET['filter_year']) : 0;
    $footerFilterMonth = isset($_GET['filter_month']) ? intval($_GET['filter_month']) : 0;
    if ($footerFilterYear <= 0 && $this->is('archive') && !empty($this->year)) {
        $footerFilterYear = intval($this->year);
        if (!empty($this->month)) {
            $footerFilterMonth = intval($this->month);
        }
    }
    if ($footerFilterYear > 0) {
        $pageReq = isset($_GET['page']) ? max(1, intval($_GET['page'])) : $footerCurPage;
        $filteredPageData = getFilteredPostsData($footerFilterYear, $footerFilterMonth, $pageReq, $footerPageSize);
        $footerCurPage = $filteredPageData['currentPage'];
        $footerTotalPage = $filteredPageData['totalPage'];
    }
    ?>
    <input class="_currentPage" value="<?php echo $footerCurPage; ?>" />
    <input class="_totalPage" value="<?php echo $footerTotalPage; ?>" />
    <input id="filter_year" type="hidden" value="<?php echo $footerFilterYear; ?>" />
    <input id="filter_month" type="hidden" value="<?php echo $footerFilterMonth; ?>" />
    <input id="commentsRequireMail" value="<?php echo $this->options->commentsRequireMail; ?>" />
    <input id="commentsRequireURL" value="<?php echo $this->options->commentsRequireURL; ?>" />
    <li>
        <div class="bg-white dark:bg-[#262626] p-2 rounded-sm border-1 border-solid border-[#07c160]">
            <div class="grid grid-cols-3 gap-2">
                <input name="author" aria-label="昵称" placeholder="昵称" autocomplete="nickname"
                    class="border-0 outline-none bg-color-primary p-1 rounded-sm dark:bg-[#323232] dark:text-[#cccccc]" />
                <input name="url" aria-label="网址" placeholder="网址" autocomplete="url" inputmode="url"
                    class="border-0 outline-none bg-color-primary p-1 rounded-sm dark:bg-[#323232] dark:text-[#cccccc]" />
                <input name="mail" aria-label="邮箱" placeholder="邮箱" type="email" autocomplete="email" inputmode="email"
                    class="border-0 outline-none bg-color-primary p-1 rounded-sm dark:bg-[#323232] dark:text-[#cccccc]" />
            </div>
            <div class="mt-2">
                <input name="text" aria-label="回复内容" placeholder="回复内容"
                    class="border-0 outline-none w-full rounded-sm p-1 dark:text-[#cccccc]" />
            </div>
            <div class="flex justify-end mt-2">
                <div class="face mr-2 cursor-pointer"></div>
                <button
                    class="btn-comment bg-[#07c160] border-0 outline-none text-white cursor-pointer rounded-sm">回复</button>
            </div>
        </div>
    </li>
    <input id="observAutoPlayVideo" value="<?php echo $this->options->observAutoPlayVideo; ?>" />
    <div class="animate-spin"></div>
    <?php
    // 检查用户是否登录
    if ($this->user->hasLogin()) {
        // 用户已登录，获取用户信息
        $user = $this->user;
        $screenName = $user->screenName; // 用户昵称
        $mail = $user->mail; // 用户邮箱
        $url = $user->url; // 用户网址
    
        ?>
        <div id="login-is">1</div>
        <div id="login-screenName">
            <?php echo $screenName; ?>
        </div>
        <div id="login-mail">
            <?php echo $mail; ?>
        </div>
        <div id="login-url">
            <?php echo $url; ?>
        </div>
        <?php
    }
    ?>
</div>
</div>
</div>
</body>

</html>