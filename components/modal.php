<?php
if (!defined('__TYPECHO_ROOT_DIR__'))
    exit;
?>

<!--友链弹出层-->
<div id="friend-modal" class="fixed top-0 left-0 w-full h-full backdrop-blur-md bg-black/50 z-90 hidden">
    <div
        class="fixed top-1/2 left-1/2 friend-container bg-white transform -translate-x-1/2 -translate-y-1/2 overflow-hidden">
        <div class="w-full flex flex-row justify-end">
            <div class="p-2">
                <img width="24" height="24" src="<?php $this->options->themeUrl('assets/svgs/btn-close.svg'); ?>"
                    class="cursor-pointer" @click="closeFriendModal()" />
            </div>
        </div>
        <div class="px-5 py-3">
            <h2>友情链接</h2>
        </div>
        <div class="px-5 py-5">
            <?php
            $friendLinks = $this->options->friendLinks;
            if ($friendLinks) {
                $friendLinks_arr = preg_split('/\r\n|\n/', $friendLinks);
                foreach ($friendLinks_arr as $row) {
                    $fl = explode('||', $row);
                    ?>
                    <div class="mb-3 flex flex-row">
                        <img src="<?php echo $fl[0]; ?>" class="w-10 h-10 rounded-md mr-3" />
                        <a href="<?php echo $fl[2]; ?>"
                            class="text-sm font-semibold leading-6 text-gray-900 flex flex-row items-center cursor-pointer">
                            <?php echo $fl[1]; ?>
                        </a>
                    </div>
                    <?php
                }
            }
            ?>
        </div>
    </div>
</div>

<!-- 用户注册 / 登录 弹出层 -->
<style>
.auth-modal-overlay {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    background: rgba(0, 0, 0, 0.6) !important;
    backdrop-filter: blur(8px) !important;
    -webkit-backdrop-filter: blur(8px) !important;
    z-index: 9999 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
}
.auth-modal-overlay.hidden {
    display: none !important;
}
/* 2026-11-02：弹窗改为品牌头部 + 胶囊分段Tab + 弹跳出场动画（authModalPop） */
.auth-modal-card {
    width: 92% !important;
    max-width: 400px !important;
    background: #ffffff !important;
    border-radius: 18px !important;
    box-shadow: 0 18px 40px rgba(0, 0, 0, 0.28) !important;
    display: flex !important;
    flex-direction: column !important;
    overflow: hidden !important;
    animation: authModalPop 0.55s cubic-bezier(0.34, 1.4, 0.5, 1) both !important;
}
body.dark .auth-modal-card {
    background: #2d2d2d !important;
    color: #e5e7eb !important;
}
@keyframes authModalPop {
    0% { opacity: 0; transform: scale(0.55) translateY(26px); }
    55% { opacity: 1; transform: scale(1.05) translateY(-4px); }
    75% { transform: scale(0.97) translateY(1px); }
    100% { opacity: 1; transform: scale(1) translateY(0); }
}
@media (prefers-reduced-motion: reduce) {
    @keyframes authModalFade { from { opacity: 0; } to { opacity: 1; } }
    .auth-modal-card { animation: authModalFade 0.25s ease both !important; }
    .date-modal-card { animation: authModalFade 0.25s ease both !important; }
    .profile-modal-card { animation: authModalFade 0.25s ease both !important; }
    .auth-seg-slider { transition: none !important; }
    .auth-btn-primary { transition: none !important; }
}

/* 品牌头部色带 */
.auth-header {
    background: #e8f8ef;
    padding: 18px 20px 16px;
    position: relative;
}
body.dark .auth-header {
    background: rgba(7, 193, 96, 0.12);
}
.auth-header-badge {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    background: #07c160;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.auth-header-title {
    margin: 0;
    font-size: 15px;
    font-weight: 600;
    color: #1a1a1a;
    line-height: 1.3;
}
body.dark .auth-header-title {
    color: #f3f4f6;
}
.auth-header-sub {
    margin: 2px 0 0;
    font-size: 12px;
    color: #67a583;
}
body.dark .auth-header-sub {
    color: #7fcba4;
}
.auth-close-btn {
    position: absolute;
    top: 10px;
    right: 10px;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.85);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background 0.2s;
}
.auth-close-btn:hover {
    background: #ffffff;
}
body.dark .auth-close-btn {
    background: rgba(255, 255, 255, 0.12);
}
body.dark .auth-close-btn:hover {
    background: rgba(255, 255, 255, 0.22);
}

/* 胶囊分段 Tab（绿色滑块弹性滑动） */
.auth-seg {
    margin-top: 14px;
    background: #ffffff;
    border-radius: 10px;
    padding: 3px;
    display: flex;
    position: relative;
    box-shadow: inset 0 0 0 1px rgba(7, 193, 96, 0.18);
}
body.dark .auth-seg {
    background: #383838;
    box-shadow: inset 0 0 0 1px rgba(7, 193, 96, 0.3);
}
.auth-seg-slider {
    position: absolute;
    top: 3px;
    left: 3px;
    width: calc(50% - 3px);
    height: calc(100% - 6px);
    background: #07c160;
    border-radius: 8px;
    transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.auth-tab-btn {
    flex: 1;
    text-align: center;
    padding: 7px 0;
    font-size: 13px;
    font-weight: 600;
    color: #5f5e5a;
    position: relative;
    z-index: 1;
    cursor: pointer;
    transition: color 0.25s;
}
.auth-tab-btn.active {
    color: #ffffff;
}
body.dark .auth-tab-btn {
    color: #9ca3af;
}
body.dark .auth-tab-btn.active {
    color: #ffffff;
}

.auth-input {
    width: 100%;
    box-sizing: border-box;
    padding: 10px 14px;
    font-size: 14px;
    border-radius: 9px;
    border: 1px solid #e5e7eb;
    outline: none;
    background: #f9fafb;
    color: #333;
    transition: border 0.2s, box-shadow 0.2s, background 0.2s;
}
.auth-input:focus {
    border-color: #07c160;
    background: #ffffff;
    box-shadow: 0 0 0 3px rgba(7, 193, 96, 0.13);
}
body.dark .auth-input {
    background: #383838;
    border-color: #4a4a4a;
    color: #f3f4f6;
}
body.dark .auth-input:focus {
    border-color: #07c160;
    background: #404040;
    box-shadow: 0 0 0 3px rgba(7, 193, 96, 0.2);
}

/* 密码显隐小眼睛（2026-11-02） */
.auth-pwd-wrap {
    position: relative;
}
.auth-pwd-wrap .auth-input {
    padding-right: 42px;
}
.auth-pwd-eye {
    position: absolute;
    right: 7px;
    top: 50%;
    transform: translateY(-50%);
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    background: transparent;
    border: none;
    padding: 0;
    color: #9ca3af;
}
.auth-pwd-eye:hover {
    color: #07c160;
}

.auth-btn-primary {
    width: 100%;
    padding: 11px;
    background: #07c160;
    color: #ffffff;
    font-size: 15px;
    font-weight: 600;
    border-radius: 11px;
    border: none;
    cursor: pointer;
    transition: all 0.18s ease;
}
.auth-btn-primary:hover {
    background: #06ad56;
    box-shadow: 0 6px 16px rgba(7, 193, 96, 0.35);
    transform: translateY(-1px);
}
.auth-btn-primary:active {
    transform: scale(0.97);
}
.auth-btn-primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

/* ========================================================
   用户个人资料弹窗 - 高级审美与精致设计
   ======================================================== */
#user-profile-modal.auth-modal-overlay {
    backdrop-filter: blur(12px) !important;
    -webkit-backdrop-filter: blur(12px) !important;
    background: rgba(15, 23, 42, 0.45) !important;
}
.profile-modal-card {
    width: 90% !important;
    max-width: 360px !important;
    background: #ffffff !important;
    border-radius: 24px !important;
    border: 1px solid rgba(255, 255, 255, 0.8) !important;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.22), 0 0 1px 1px rgba(0, 0, 0, 0.04) !important;
    display: flex !important;
    flex-direction: column !important;
    overflow: hidden !important;
    animation: authModalPop 0.45s cubic-bezier(0.34, 1.56, 0.64, 1) both !important;
    position: relative !important;
}
body.dark .profile-modal-card {
    background: #1e2025 !important;
    border: 1px solid rgba(255, 255, 255, 0.08) !important;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5), 0 0 1px 1px rgba(255, 255, 255, 0.05) !important;
    color: #f3f4f6 !important;
}
/* 顶部艺术背景条 */
.profile-modal-banner {
    position: relative;
    height: 96px;
    background: linear-gradient(135deg, #07c160 0%, #10b981 40%, #059669 100%);
    overflow: hidden;
}
body.dark .profile-modal-banner {
    background: linear-gradient(135deg, #064e3b 0%, #065f46 45%, #0f172a 100%);
}
.profile-modal-banner::before {
    content: "";
    position: absolute;
    top: -30px;
    right: -20px;
    width: 130px;
    height: 130px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(255, 255, 255, 0.25) 0%, rgba(255, 255, 255, 0) 70%);
    pointer-events: none;
}
.profile-modal-banner::after {
    content: "";
    position: absolute;
    bottom: -40px;
    left: -20px;
    width: 110px;
    height: 110px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(255, 255, 255, 0.18) 0%, rgba(255, 255, 255, 0) 70%);
    pointer-events: none;
}

/* 顶部操作行 (标签 + 关闭按钮) */
.profile-modal-header-bar {
    position: relative;
    z-index: 2;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 14px;
}
.profile-banner-tag {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 10px;
    border-radius: 9999px;
    background: rgba(255, 255, 255, 0.22);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    font-size: 11px;
    font-weight: 500;
    color: #ffffff;
    letter-spacing: 0.3px;
    border: 1px solid rgba(255, 255, 255, 0.3);
}
.profile-banner-close-btn {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: rgba(0, 0, 0, 0.16);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: #ffffff;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    border: 1px solid rgba(255, 255, 255, 0.15);
}
.profile-banner-close-btn:hover {
    background: rgba(0, 0, 0, 0.32);
    transform: scale(1.08);
}

/* 主内容主体区 */
.profile-modal-body {
    position: relative;
    padding: 0 20px 22px 20px;
    text-align: center;
    z-index: 2;
}

/* 悬浮头像容器 */
.profile-avatar-wrapper {
    position: relative;
    display: inline-block;
    margin-top: -46px;
    margin-bottom: 10px;
}
.profile-avatar-frame {
    width: 82px;
    height: 82px;
    border-radius: 50%;
    background: #ffffff;
    padding: 3.5px;
    box-shadow: 0 10px 24px -4px rgba(0, 0, 0, 0.16);
    display: inline-block;
    transition: transform 0.25s ease;
}
body.dark .profile-avatar-frame {
    background: #1e2025;
    box-shadow: 0 10px 24px -4px rgba(0, 0, 0, 0.45);
}
.profile-avatar-frame:hover {
    transform: scale(1.04);
}
.profile-avatar-img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
    display: block;
}
.profile-status-dot {
    position: absolute;
    bottom: 4px;
    right: 4px;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: #07c160;
    border: 2.5px solid #ffffff;
    box-shadow: 0 2px 6px rgba(7, 193, 96, 0.45);
    display: flex;
    align-items: center;
    justify-content: center;
}
body.dark .profile-status-dot {
    border-color: #1e2025;
}

/* 用户昵称与邮箱 */
.profile-modal-name {
    font-size: 18px;
    font-weight: 700;
    color: #111827;
    margin: 0;
    line-height: 1.3;
    letter-spacing: -0.2px;
}
body.dark .profile-modal-name {
    color: #f9fafb;
}
.profile-modal-email-row {
    margin-top: 4px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    font-size: 12px;
    color: #6b7280;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}
body.dark .profile-modal-email-row {
    color: #9ca3af;
}

/* 身份标识胶囊 */
.profile-modal-badge-wrap {
    margin-top: 8px;
    margin-bottom: 18px;
}
.profile-badge-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 12px;
    border-radius: 9999px;
    font-size: 11px;
    font-weight: 600;
    background: rgba(7, 193, 96, 0.09);
    color: #07c160;
    border: 1px solid rgba(7, 193, 96, 0.22);
    letter-spacing: 0.2px;
}
body.dark .profile-badge-pill {
    background: rgba(7, 193, 96, 0.14);
    color: #34d399;
    border-color: rgba(7, 193, 96, 0.3);
}

/* 快捷操作项卡片组 */
.profile-action-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
    text-align: left;
}
.profile-action-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 14px;
    border-radius: 14px;
    background: #f8fafc;
    border: 1px solid #edf2f7;
    text-decoration: none !important;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    cursor: pointer;
}
body.dark .profile-action-card {
    background: rgba(255, 255, 255, 0.03);
    border-color: rgba(255, 255, 255, 0.06);
}
.profile-action-card:hover {
    background: #f1f5f9;
    border-color: #e2e8f0;
    transform: translateY(-1.5px);
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.05);
}
body.dark .profile-action-card:hover {
    background: rgba(255, 255, 255, 0.07);
    border-color: rgba(255, 255, 255, 0.12);
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.25);
}
.profile-action-left {
    display: flex;
    align-items: center;
    gap: 12px;
}
.profile-action-icon-box {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: transform 0.2s ease;
}
.profile-action-card:hover .profile-action-icon-box {
    transform: scale(1.06);
}
.profile-action-icon-admin {
    background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%);
    color: #4f46e5;
    border: 1px solid rgba(99, 102, 241, 0.2);
}
body.dark .profile-action-icon-admin {
    background: rgba(99, 102, 241, 0.18);
    color: #818cf8;
    border-color: rgba(99, 102, 241, 0.3);
}
.profile-action-icon-publish {
    background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
    color: #059669;
    border: 1px solid rgba(16, 185, 129, 0.2);
}
body.dark .profile-action-icon-publish {
    background: rgba(16, 185, 129, 0.18);
    color: #34d399;
    border-color: rgba(16, 185, 129, 0.3);
}
.profile-action-meta {
    display: flex;
    flex-direction: column;
}
.profile-action-title {
    font-size: 13px;
    font-weight: 600;
    color: #1f2937;
    line-height: 1.35;
}
body.dark .profile-action-title {
    color: #f3f4f6;
}
.profile-action-desc {
    font-size: 11px;
    color: #94a3b8;
    line-height: 1.25;
}
body.dark .profile-action-desc {
    color: #64748b;
}
.profile-action-arrow {
    color: #cbd5e1;
    transition: all 0.2s;
    display: flex;
    align-items: center;
}
body.dark .profile-action-arrow {
    color: #475569;
}
.profile-action-card:hover .profile-action-arrow {
    color: #64748b;
    transform: translateX(3px);
}
body.dark .profile-action-card:hover .profile-action-arrow {
    color: #94a3b8;
}

/* 分隔与退出按钮 */
.profile-modal-divider {
    height: 1px;
    background: #f1f5f9;
    margin: 14px 0 10px 0;
}
body.dark .profile-modal-divider {
    background: rgba(255, 255, 255, 0.06);
}
.profile-modal-logout-btn {
    width: 100%;
    padding: 10px 14px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 600;
    color: #f43f5e;
    background: rgba(244, 63, 94, 0.06);
    border: 1px solid rgba(244, 63, 94, 0.16);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}
.profile-modal-logout-btn:hover {
    background: rgba(244, 63, 94, 0.12);
    border-color: rgba(244, 63, 94, 0.28);
    color: #e11d48;
    transform: translateY(-0.5px);
}
body.dark .profile-modal-logout-btn {
    background: rgba(244, 63, 94, 0.1);
    border-color: rgba(244, 63, 94, 0.22);
    color: #fb7185;
}
body.dark .profile-modal-logout-btn:hover {
    background: rgba(244, 63, 94, 0.18);
    border-color: rgba(244, 63, 94, 0.38);
    color: #fda4af;
}
</style>

<div id="user-auth-modal" class="auth-modal-overlay hidden" onclick="if(event.target === this) closeUserAuthModal();">
    <div class="auth-modal-card">
        <?php /* 2026-11-02：UI 改版——品牌头部色带 + 胶囊分段Tab（绿色滑块）+ 压缩表单 + 密码小眼睛；
                   出场动画改为弹跳（authModalPop）。表单字段 id 与提交逻辑均未变动 */ ?>
        <!-- 品牌头部色带 -->
        <div class="auth-header">
            <div class="auth-close-btn" onclick="closeUserAuthModal()" title="关闭">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2.6" stroke-linecap="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </div>
            <div style="display:flex;align-items:center;gap:10px;">
                <div class="auth-header-badge">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </div>
                <div>
                    <p class="auth-header-title">欢迎来到 <?php echo htmlspecialchars((string) ($this->options->avatarTitle ?: $this->options->title), ENT_QUOTES, 'UTF-8'); ?></p>
                    <p class="auth-header-sub">注册后可直接评论互动，无需进后台</p>
                </div>
            </div>
            <!-- 胶囊分段 Tab：滑块由 switchAuthTab 控制 transform -->
            <div class="auth-seg">
                <div class="auth-seg-slider" id="auth-seg-slider"></div>
                <div id="tab-auth-register-btn" class="auth-tab-btn active" onclick="switchAuthTab('register')">用户注册</div>
                <div id="tab-auth-login-btn" class="auth-tab-btn" onclick="switchAuthTab('login')">账号登录</div>
            </div>
        </div>

        <!-- 提示栏 -->
        <div id="auth-alert-msg" class="mx-5 mt-3 p-2.5 rounded-lg text-xs hidden"></div>

        <!-- 注册表单面板 -->
        <div id="panel-auth-register" class="p-5 space-y-3">
            <div style="display:flex;gap:10px;">
                <div style="flex:1;min-width:0;">
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">用户名 <span class="text-red-500">*</span></label>
                    <input type="text" id="reg-username" class="auth-input" placeholder="用于账号登录" maxlength="32" autocomplete="username" />
                </div>
                <div style="flex:1;min-width:0;">
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">昵称 <span class="text-[11px] text-gray-400 font-normal">（选填）</span></label>
                    <input type="text" id="reg-screenname" class="auth-input" placeholder="前台展示昵称" maxlength="32" autocomplete="nickname" />
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">电子邮箱 <span class="text-red-500">*</span></label>
                <input type="email" id="reg-email" class="auth-input" placeholder="name@example.com" maxlength="64" autocomplete="email" />
            </div>
            <div style="display:flex;gap:10px;">
                <div style="flex:1;min-width:0;">
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">密码 <span class="text-red-500">*</span></label>
                    <div class="auth-pwd-wrap">
                        <input type="password" id="reg-password" class="auth-input" placeholder="至少6位" maxlength="32" autocomplete="new-password" />
                        <button type="button" class="auth-pwd-eye" onclick="toggleAuthPwd('reg-password', this)" aria-label="显示或隐藏密码">
                            <svg class="eye-open" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <svg class="eye-closed" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                <line x1="1" y1="1" x2="23" y2="23"></line>
                            </svg>
                        </button>
                    </div>
                </div>
                <div style="flex:1;min-width:0;">
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">确认密码 <span class="text-red-500">*</span></label>
                    <div class="auth-pwd-wrap">
                        <input type="password" id="reg-confirm" class="auth-input" placeholder="再次输入密码" maxlength="32" autocomplete="new-password" />
                        <button type="button" class="auth-pwd-eye" onclick="toggleAuthPwd('reg-confirm', this)" aria-label="显示或隐藏密码">
                            <svg class="eye-open" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <svg class="eye-closed" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                <line x1="1" y1="1" x2="23" y2="23"></line>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <div style="padding-top:14px;">
                <button type="button" id="btn-submit-register" class="auth-btn-primary" onclick="submitAjaxRegister()">
                    立即注册并直接登录
                </button>
            </div>

            <div class="text-center text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
                注册成功后直接登录，昵称留空默认同用户名；评论时免输入昵称和邮箱。
            </div>

            <div class="text-center pt-0.5 text-xs text-gray-500">
                已有账号？<a href="javascript:;" class="text-[#07c160] hover:underline" onclick="switchAuthTab('login')">直接登录</a>
            </div>
        </div>

        <!-- 登录表单面板 -->
        <div id="panel-auth-login" class="p-5 space-y-3.5 hidden">
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">用户名或邮箱 <span class="text-red-500">*</span></label>
                <input type="text" id="login-username" class="auth-input" placeholder="请输入用户名或注册邮箱" maxlength="64" autocomplete="username" />
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">登录密码 <span class="text-red-500">*</span></label>
                <div class="auth-pwd-wrap">
                    <input type="password" id="login-password" class="auth-input" placeholder="请输入登录密码" maxlength="32" autocomplete="current-password" />
                    <button type="button" class="auth-pwd-eye" onclick="toggleAuthPwd('login-password', this)" aria-label="显示或隐藏密码">
                        <svg class="eye-open" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        <svg class="eye-closed" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;">
                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                            <line x1="1" y1="1" x2="23" y2="23"></line>
                        </svg>
                    </button>
                </div>
            </div>

            <div style="padding-top:14px;">
                <button type="button" id="btn-submit-login" class="auth-btn-primary" onclick="submitAjaxLogin()">
                    立即登录
                </button>
            </div>

            <div class="text-center pt-1.5 text-xs text-gray-500">
                还没有账号？<a href="javascript:;" class="text-[#07c160] hover:underline" onclick="switchAuthTab('register')">免费注册</a>
            </div>
        </div>

        <?php /* 2026-11-05：版权与备案移入弹窗底部（原页面左下角浮动块移除）；
                   备案号/公安备案仍读取后台「备案号(beian)」「公安网备号(policeBeian)」设置 */ ?>
        <div class="auth-modal-footer">
            <span>Powered By <a href="https://github.com/xiaopanglian/icefox" target="_blank" rel="noopener">Icefox</a>&amp;<a href="https://hi.jeray.wang/" target="_blank" rel="noopener">Jeray</a></span>
            <?php if (!empty($this->options->beian)): ?>
            <a href="https://beian.miit.gov.cn/" target="_blank" rel="noopener"><?php echo $this->options->beian; ?></a>
            <?php endif; ?>
            <?php if (!empty($this->options->policeBeian)):
                $wbh = explode('||', $this->options->policeBeian); ?>
            <a href="<?php echo $wbh[1]; ?>" target="_blank" rel="noopener"><?php echo $wbh[0]; ?></a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- 已登录用户信息面板 弹出层 -->
<div id="user-profile-modal" class="auth-modal-overlay hidden" onclick="if(event.target === this) closeUserProfileModal();">
    <div class="profile-modal-card">
        <!-- 顶部微渐变装饰横幅 -->
        <div class="profile-modal-banner">
            <div class="profile-modal-header-bar">
                <span class="profile-banner-tag">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    个人中心
                </span>
                <div class="profile-banner-close-btn" onclick="closeUserProfileModal()" title="关闭">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </div>
            </div>
        </div>

        <!-- 卡片主要内容 -->
        <div class="profile-modal-body">
            <!-- 悬浮头像 -->
            <div class="profile-avatar-wrapper">
                <div class="profile-avatar-frame">
                    <img id="profile-modal-avatar" src="<?php echo $this->user->hasLogin() ? getUserAvatar($this->user->uid) : ''; ?>" class="profile-avatar-img" alt="用户头像" />
                </div>
                <div class="profile-status-dot" title="在线">
                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                </div>
            </div>

            <!-- 用户名 -->
            <div class="profile-modal-name" id="profile-modal-name">
                <?php echo $this->user->hasLogin() ? $this->user->screenName : ''; ?>
            </div>

            <!-- 邮箱 -->
            <div class="profile-modal-email-row">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="opacity:0.7;">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                    <polyline points="22,6 12,13 2,6"></polyline>
                </svg>
                <span id="profile-modal-mail"><?php echo $this->user->hasLogin() ? $this->user->mail : ''; ?></span>
            </div>

            <!-- 用户角色徽章 -->
            <div class="profile-modal-badge-wrap">
                <span class="profile-badge-pill">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                    </svg>
                    <span id="profile-modal-group">
                        <?php
                        if ($this->user->hasLogin()) {
                            echo $this->user->group == 'administrator' ? '网站管理员' : '已登录用户';
                        }
                        ?>
                    </span>
                </span>
            </div>

            <!-- 快捷操作项卡片组 -->
            <div class="profile-action-group">
                <!-- 管理员特权通道 -->
                <div id="profile-modal-admin-actions" class="<?php echo ($this->user->hasLogin() && $this->user->group == 'administrator') ? '' : 'hidden'; ?>">
                    <a href="<?php $this->options->adminUrl(); ?>" class="profile-action-card">
                        <div class="profile-action-left">
                            <div class="profile-action-icon-box profile-action-icon-admin">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="3" width="7" height="7"></rect>
                                    <rect x="14" y="3" width="7" height="7"></rect>
                                    <rect x="14" y="14" width="7" height="7"></rect>
                                    <rect x="3" y="14" width="7" height="7"></rect>
                                </svg>
                            </div>
                            <div class="profile-action-meta">
                                <span class="profile-action-title">管理后台</span>
                                <span class="profile-action-desc">管理文章、设置与系统配置</span>
                            </div>
                        </div>
                        <div class="profile-action-arrow">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </div>
                    </a>

                    <a href="<?php echo $this->options->publishPageUrl; ?>" class="profile-action-card" style="margin-top: 8px;" onclick="return openPublishModal();" no-pjax>
                        <div class="profile-action-left">
                            <div class="profile-action-icon-box profile-action-icon-publish">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 20h9"></path>
                                    <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                                </svg>
                            </div>
                            <div class="profile-action-meta">
                                <span class="profile-action-title">发布朋友圈</span>
                                <span class="profile-action-desc">记录即时想法、图文与音乐</span>
                            </div>
                        </div>
                        <div class="profile-action-arrow">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </div>
                    </a>
                </div>
            </div>

            <!-- 分割线 -->
            <div class="profile-modal-divider"></div>

            <!-- 退出登录按钮 -->
            <button type="button" class="profile-modal-logout-btn" onclick="submitAjaxLogout()">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
                <span>退出当前账号</span>
            </button>
        </div>
    </div>
</div>

<!-- 发布朋友圈 iframe 弹窗（2026-11-02）
     发布页逻辑复杂（图片/音乐/视频上传、表情、草稿），不搬家，用 iframe 原样加载；
     发布成功后 Typecho 会重定向到后台文章列表，icefox.js 据此判定完成 →
     关闭弹窗 + PJAX 局部刷新首页列表（音乐播放不受影响）。 -->
<style>
#publish-modal.publish-modal-overlay {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    background: rgba(15, 23, 42, 0.55) !important;
    backdrop-filter: blur(10px) !important;
    -webkit-backdrop-filter: blur(10px) !important;
    z-index: 10000 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    padding: 24px !important;
    box-sizing: border-box !important;
}
#publish-modal.publish-modal-overlay.hidden {
    display: none !important;
}
.publish-modal-card {
    width: 100% !important;
    max-width: 520px !important;
    height: min(88vh, 900px) !important;
    background: #ffffff !important;
    border-radius: 18px !important;
    box-shadow: 0 24px 60px rgba(0, 0, 0, 0.32) !important;
    display: flex !important;
    flex-direction: column !important;
    overflow: hidden !important;
    animation: authModalPop 0.45s cubic-bezier(0.34, 1.4, 0.5, 1) both !important;
}
body.dark .publish-modal-card {
    background: #26282c !important;
    color: #e5e7eb !important;
}
.publish-modal-head {
    flex: 0 0 auto !important;
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    padding: 12px 16px !important;
    background: linear-gradient(135deg, #effaf3, #f8fdfa) !important;
    border-bottom: 1px solid rgba(7, 193, 96, 0.14) !important;
}
body.dark .publish-modal-head {
    background: linear-gradient(135deg, #1e3a2b, #223026) !important;
    border-bottom-color: rgba(255, 255, 255, 0.08) !important;
}
.publish-modal-title {
    display: flex !important;
    align-items: center !important;
    gap: 8px !important;
    font-size: 15px !important;
    font-weight: 700 !important;
    color: #0f172a !important;
}
body.dark .publish-modal-title {
    color: #e5e7eb !important;
}
.publish-modal-badge {
    width: 26px !important;
    height: 26px !important;
    border-radius: 9px !important;
    background: #07c160 !important;
    color: #ffffff !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    box-shadow: 0 4px 10px rgba(7, 193, 96, 0.32) !important;
}
.publish-modal-close {
    width: 30px !important;
    height: 30px !important;
    border-radius: 50% !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    cursor: pointer !important;
    color: #6b7280 !important;
    background: rgba(255, 255, 255, 0.8) !important;
    transition: background 0.2s, color 0.2s, transform 0.25s !important;
}
.publish-modal-close:hover {
    background: #ffffff !important;
    color: #111827 !important;
    transform: rotate(90deg) !important;
}
body.dark .publish-modal-close {
    background: rgba(255, 255, 255, 0.1) !important;
    color: #d1d5db !important;
}
.publish-modal-frame-wrap {
    flex: 1 1 auto !important;
    position: relative !important;
    background: #f0f0f0 !important;
}
body.dark .publish-modal-frame-wrap {
    background: #1e2025 !important;
}
#publish-modal-frame {
    display: block !important;
    width: 100% !important;
    height: 100% !important;
    border: 0 !important;
    background: #ffffff !important;
}
.publish-modal-loading {
    position: absolute !important;
    inset: 0 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 8px !important;
    font-size: 13px !important;
    color: #6b7280 !important;
    background: #f7f8f9 !important;
}
.publish-modal-loading.hidden {
    display: none !important;
}
body.dark .publish-modal-loading {
    background: #1e2025 !important;
    color: #9ca3af !important;
}
.publish-modal-spinner {
    width: 15px !important;
    height: 15px !important;
    border-radius: 50% !important;
    border: 2px solid rgba(7, 193, 96, 0.25) !important;
    border-top-color: #07c160 !important;
    animation: publishSpin 0.75s linear infinite !important;
}
@keyframes publishSpin {
    to { transform: rotate(360deg); }
}
@media (max-width: 640px) {
    #publish-modal.publish-modal-overlay {
        padding: 0 !important;
        align-items: stretch !important;
    }
    .publish-modal-card {
        max-width: 100% !important;
        height: 100vh !important;
        height: 100dvh !important;
        border-radius: 0 !important;
    }
}
@media (prefers-reduced-motion: reduce) {
    .publish-modal-card { animation: authModalFade 0.25s ease both !important; }
    .publish-modal-spinner { animation: none !important; }
}
</style>
<div id="publish-modal" class="publish-modal-overlay hidden" onclick="if(event.target === this) closePublishModal();">
    <div class="publish-modal-card">
        <div class="publish-modal-head">
            <div class="publish-modal-title">
                <span class="publish-modal-badge">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 20h9"></path>
                        <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                    </svg>
                </span>
                发布朋友圈
            </div>
            <div class="publish-modal-close" onclick="closePublishModal()" title="关闭">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </div>
        </div>
        <div class="publish-modal-frame-wrap">
            <iframe id="publish-modal-frame" title="发布朋友圈"
                data-publish-url="<?php echo htmlspecialchars($this->options->publishPageUrl, ENT_QUOTES, 'UTF-8'); ?>"
                src="about:blank"></iframe>
            <div class="publish-modal-loading" id="publish-modal-loading">
                <span class="publish-modal-spinner"></span>
                正在打开发布页…
            </div>
        </div>
    </div>
</div>


<!--时间快速查询弹出层-->
<style>
#date-filter-modal.date-modal-overlay {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    background: rgba(0, 0, 0, 0.6) !important;
    backdrop-filter: blur(8px) !important;
    -webkit-backdrop-filter: blur(8px) !important;
    z-index: 9999 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
}
#date-filter-modal.hidden {
    display: none !important;
}
.date-modal-card {
    width: 92% !important;
    max-width: 440px !important;
    max-height: 82vh !important;
    background: #ffffff !important;
    border-radius: 16px !important;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25) !important;
    display: flex !important;
    flex-direction: column !important;
    overflow: hidden !important;
    /* 2026-11-02：搜索/时间查询弹窗与注册弹窗统一使用弹跳出场动画 */
    animation: authModalPop 0.45s cubic-bezier(0.34, 1.4, 0.5, 1) both !important;
}
body.dark .date-modal-card {
    background: #2d2d2d !important;
    color: #e5e7eb !important;
}
@keyframes dateModalIn {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}
.date-modal-hd {
    padding: 16px 20px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    border-bottom: 1px solid #f0f0f0 !important;
}
body.dark .date-modal-hd {
    border-bottom-color: #3d3d3d !important;
}
.date-modal-tt {
    font-size: 16px !important;
    font-weight: 700 !important;
    color: #1f2329 !important;
    display: flex !important;
    align-items: center !important;
    gap: 8px !important;
    margin: 0 !important;
}
body.dark .date-modal-tt {
    color: #f3f4f6 !important;
}
.date-modal-bd {
    padding: 16px 20px !important;
    overflow-y: auto !important;
    -webkit-overflow-scrolling: touch !important;
}
.date-btn-all {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    padding: 11px 16px !important;
    border-radius: 12px !important;
    text-decoration: none !important;
    font-size: 14px !important;
    margin-bottom: 16px !important;
    transition: all 0.2s !important;
    background: #f7f8f9 !important;
    color: #333 !important;
    border: 1px solid #e5e7eb !important;
}
body.dark .date-btn-all {
    background: #363636 !important;
    color: #ddd !important;
    border-color: #4a4a4a !important;
}
.date-btn-all.active {
    background: #07c160 !important;
    color: #ffffff !important;
    border-color: #07c160 !important;
    font-weight: 600 !important;
    box-shadow: 0 4px 12px rgba(7, 193, 96, 0.25) !important;
}
.date-year-card {
    background: #f9fafb !important;
    border: 1px solid #edf0f2 !important;
    border-radius: 12px !important;
    padding: 12px 14px !important;
    margin-bottom: 14px !important;
}
body.dark .date-year-card {
    background: #343434 !important;
    border-color: #424242 !important;
}
.date-year-hd {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    margin-bottom: 12px !important;
    padding-bottom: 8px !important;
    border-bottom: 1px solid #ebecee !important;
}
body.dark .date-year-hd {
    border-bottom-color: #404040 !important;
}
.date-year-tt {
    font-size: 15px !important;
    font-weight: 700 !important;
    color: #111 !important;
}
body.dark .date-year-tt {
    color: #f9fafb !important;
}
.date-year-badge {
    font-size: 12px !important;
    font-weight: normal !important;
    color: #888 !important;
    margin-left: 4px !important;
}
body.dark .date-year-badge {
    color: #9ca3af !important;
}
.date-year-link {
    font-size: 12px !important;
    padding: 3px 10px !important;
    border-radius: 6px !important;
    text-decoration: none !important;
    color: #07c160 !important;
    border: 1px solid rgba(7, 193, 96, 0.35) !important;
    background: transparent !important;
    transition: all 0.2s !important;
}
.date-year-link:hover, .date-year-link.active {
    background: #07c160 !important;
    color: #ffffff !important;
    border-color: #07c160 !important;
}
.date-grid-months {
    display: grid !important;
    grid-template-columns: repeat(4, 1fr) !important;
    gap: 8px !important;
}
.date-month-item {
    display: flex !important;
    flex-direction: column !important;
    align-items: center !important;
    justify-content: center !important;
    padding: 8px 4px !important;
    border-radius: 8px !important;
    text-decoration: none !important;
    line-height: 1.2 !important;
    transition: all 0.15s !important;
    box-sizing: border-box !important;
}
.date-month-item.has-data {
    background: #ffffff !important;
    border: 1px solid #e2e5e8 !important;
    color: #222 !important;
    cursor: pointer !important;
}
body.dark .date-month-item.has-data {
    background: #2a2a2a !important;
    border-color: #444 !important;
    color: #eee !important;
}
.date-month-item.has-data:hover {
    background: #07c160 !important;
    border-color: #07c160 !important;
    color: #ffffff !important;
}
.date-month-item.has-data:hover .date-month-cnt {
    color: rgba(255, 255, 255, 0.9) !important;
}
.date-month-item.active {
    background: #07c160 !important;
    border-color: #07c160 !important;
    color: #ffffff !important;
    font-weight: 700 !important;
    box-shadow: 0 2px 6px rgba(7, 193, 96, 0.3) !important;
}
.date-month-item.active .date-month-cnt {
    color: rgba(255, 255, 255, 0.9) !important;
}
.date-month-item.no-data {
    background: #f2f3f5 !important;
    border: 1px solid transparent !important;
    color: #bbb !important;
    cursor: not-allowed !important;
    user-select: none !important;
}
body.dark .date-month-item.no-data {
    background: #2d2d2d !important;
    color: #666 !important;
}
.date-month-name {
    font-size: 13px !important;
    font-weight: 600 !important;
}
.date-month-cnt {
    font-size: 10px !important;
    margin-top: 3px !important;
    color: #07c160 !important;
}
body.dark .date-month-cnt {
    color: #8da4cc !important;
}
.date-month-item.no-data .date-month-cnt {
    color: #ccc !important;
}
body.dark .date-month-item.no-data .date-month-cnt {
    color: #555 !important;
}
</style>

<div id="date-filter-modal" class="date-modal-overlay hidden" onclick="if(event.target === this) closeDateFilterModal();">
    <div class="date-modal-card">
        <!-- 弹窗头部 -->
        <div class="date-modal-hd">
            <h3 class="date-modal-tt">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#07c160" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                时间快速查询
            </h3>
            <div style="cursor:pointer;padding:4px;" onclick="closeDateFilterModal()">
                <img width="20" height="20" src="<?php $this->options->themeUrl('assets/svgs/btn-close.svg'); ?>" />
            </div>
        </div>

        <!-- 弹窗主体内容滚动区 -->
        <div class="date-modal-bd">
            <?php
            $archiveYears = getArticleArchiveDates();
            $curFilterYear = isset($_GET['filter_year']) ? intval($_GET['filter_year']) : 0;
            $curFilterMonth = isset($_GET['filter_month']) ? intval($_GET['filter_month']) : 0;
            if ($curFilterYear <= 0 && $this->is('archive') && !empty($this->year)) {
                $curFilterYear = intval($this->year);
                if (!empty($this->month)) {
                    $curFilterMonth = intval($this->month);
                }
            }
            $isAllActive = ($curFilterYear <= 0);
            ?>

            <!-- 全部动态快捷入口 -->
            <div>
                <a href="<?php $this->options->siteUrl(); ?>" class="date-btn-all <?php echo $isAllActive ? 'active' : ''; ?>">
                    <span style="display:flex;align-items:center;gap:6px;">
                        <span>🌟 全部朋友圈动态</span>
                        <span style="font-size:12px;opacity:0.8;">(最新时间流)</span>
                    </span>
                    <?php if ($isAllActive): ?>
                        <span style="font-size:12px;">✓ 当前正在查看</span>
                    <?php endif; ?>
                </a>
            </div>

            <!-- 年份与月份列表 -->
            <?php if (empty($archiveYears)): ?>
                <div style="text-align:center;padding:30px 0;color:#999;font-size:14px;">
                    暂无已发布的历史朋友圈动态
                </div>
            <?php else: ?>
                <div>
                    <?php foreach ($archiveYears as $yearStr => $yearData): ?>
                        <?php
                        $yNum = intval($yearStr);
                        $isThisYearActive = ($curFilterYear === $yNum && $curFilterMonth <= 0);
                        ?>
                        <div class="date-year-card">
                            <!-- 年份行标题 -->
                            <div class="date-year-hd">
                                <div class="date-year-tt">
                                    <?php echo $yearStr; ?> 年
                                    <span class="date-year-badge">(共 <?php echo $yearData['total']; ?> 条)</span>
                                </div>
                                <a href="<?php $this->options->siteUrl('?filter_year=' . $yearStr); ?>" class="date-year-link <?php echo $isThisYearActive ? 'active' : ''; ?>">
                                    <?php echo $isThisYearActive ? '✓ 查看该年' : '查看该年'; ?>
                                </a>
                            </div>

                            <!-- 月份4列工整网格 (12-1月倒序展示) -->
                            <div class="date-grid-months">
                                <?php
                                for ($m = 12; $m >= 1; $m--):
                                    $mKey = sprintf('%02d', $m);
                                    $hasCount = isset($yearData['months'][$mKey]) ? $yearData['months'][$mKey] : 0;
                                    $isThisMonthActive = ($curFilterYear === $yNum && $curFilterMonth === $m);
                                ?>
                                    <?php if ($hasCount > 0): ?>
                                        <a href="<?php $this->options->siteUrl('?filter_year=' . $yearStr . '&filter_month=' . $mKey); ?>" class="date-month-item has-data <?php echo $isThisMonthActive ? 'active' : ''; ?>">
                                            <span class="date-month-name"><?php echo $m; ?>月</span>
                                            <span class="date-month-cnt"><?php echo $hasCount; ?>条</span>
                                        </a>
                                    <?php else: ?>
                                        <div class="date-month-item no-data">
                                            <span class="date-month-name"><?php echo $m; ?>月</span>
                                            <span class="date-month-cnt">-</span>
                                        </div>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!--全站搜索弹出层（2026-10-23）-->
<style>
#search-modal.date-modal-overlay {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    background: rgba(0, 0, 0, 0.6) !important;
    backdrop-filter: blur(8px) !important;
    -webkit-backdrop-filter: blur(8px) !important;
    z-index: 9999 !important;
    display: flex !important;
    align-items: flex-start !important;
    justify-content: center !important;
    padding-top: 12vh !important;
}
#search-modal.hidden {
    display: none !important;
}
#search-modal .date-modal-card {
    max-width: 560px !important;
}
.search-input-wrap {
    display: flex !important;
    align-items: center !important;
    gap: 8px !important;
    margin: 14px 16px 4px !important;
    padding: 0 12px !important;
    background: #f7f8f9 !important;
    border: 1px solid #e5e7eb !important;
    border-radius: 10px !important;
}
body.dark .search-input-wrap {
    background: #363636 !important;
    border-color: #4a4a4a !important;
}
.search-input-wrap:focus-within {
    border-color: #07c160 !important;
}
#search-input {
    flex: 1 !important;
    border: none !important;
    outline: none !important;
    background: transparent !important;
    padding: 10px 0 !important;
    font-size: 14px !important;
    color: #1f2329 !important;
}
body.dark #search-input {
    color: #e5e7eb !important;
}
#search-input::placeholder {
    color: #b0b4bb !important;
}
.search-esc-kbd {
    font-size: 11px !important;
    color: #b0b4bb !important;
    border: 1px solid #e0e2e6 !important;
    border-radius: 4px !important;
    padding: 1px 5px !important;
    background: #fff !important;
}
body.dark .search-esc-kbd {
    background: #2d2d2d !important;
    border-color: #4a4a4a !important;
}
#search-results {
    max-height: 54vh !important;
    min-height: 96px !important;
}
.search-empty {
    text-align: center !important;
    color: #b0b4bb !important;
    font-size: 13px !important;
    padding: 34px 0 !important;
}
.search-result-item {
    display: block !important;
    padding: 10px 12px !important;
    border: 1px solid #f0f0f0 !important;
    border-radius: 10px !important;
    margin-bottom: 8px !important;
    text-decoration: none !important;
    color: inherit !important;
    transition: background 0.15s !important;
    cursor: pointer !important;
}
.search-result-item:hover {
    background: #f7f8f9 !important;
}
body.dark .search-result-item {
    border-color: #404040 !important;
}
body.dark .search-result-item:hover {
    background: #363636 !important;
}
.search-result-title {
    font-size: 14px !important;
    font-weight: 600 !important;
    color: #1f2329 !important;
    margin-bottom: 4px !important;
    overflow: hidden !important;
    text-overflow: ellipsis !important;
    white-space: nowrap !important;
}
body.dark .search-result-title {
    color: #f3f4f6 !important;
}
.search-result-snippet {
    font-size: 12.5px !important;
    color: #7a7f87 !important;
    line-height: 1.65 !important;
    word-break: break-all !important;
}
.search-result-date {
    font-size: 11.5px !important;
    color: #b0b4bb !important;
    margin-top: 4px !important;
}
#search-results mark {
    background: rgba(7, 193, 96, 0.16) !important;
    color: inherit !important;
    padding: 0 1px !important;
    border-radius: 3px !important;
}
</style>

<div id="search-modal" class="date-modal-overlay hidden" onclick="if(event.target === this) closeSearchModal();">
    <div class="date-modal-card">
        <div class="date-modal-hd">
            <h3 class="date-modal-tt">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#07c160" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="7"></circle>
                    <line x1="21" y1="21" x2="16.5" y2="16.5"></line>
                </svg>
                搜索朋友圈动态
            </h3>
            <div style="cursor:pointer;padding:4px;" onclick="closeSearchModal()">
                <img width="20" height="20" src="<?php $this->options->themeUrl('assets/svgs/btn-close.svg'); ?>" />
            </div>
        </div>
        <div class="search-input-wrap">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#b0b4bb" stroke-width="2.2" stroke-linecap="round">
                <circle cx="11" cy="11" r="7"></circle>
                <line x1="21" y1="21" x2="16.5" y2="16.5"></line>
            </svg>
            <input type="text" id="search-input" placeholder="输入关键词，搜索标题与正文…" autocomplete="off" />
            <span class="search-esc-kbd">Esc</span>
        </div>
        <div class="date-modal-bd" id="search-results">
            <div class="search-empty">输入关键词，搜索全部朋友圈动态</div>
        </div>
    </div>
</div>