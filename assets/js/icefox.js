let globalData = {
    webSiteHomeUrl: '',
    loadMorePage: 1,
    totalPage: 0,
    playMusicId: 0,
    audio: new Audio(),
    topMusicList: [],
    playIndex: 0,
    isTopMusic: false,
    // 2026-11-02：音乐因视频开播而被自动暂停时置 true，视频播完据此恢复
    musicPausedByVideo: false
};

let lazyLoadInstance = new LazyLoad({
    elements_selector: '[data-src]',
    threshold: 0,
    data_src: 'src'
});

function printCopyright() {
    console.log('%cIcefox主题 By xiaopanglian v2.0.1 %chttps://0ru.cn', 'color: white;  background-color: #99cc99; padding: 10px;', 'color: white; background-color: #ff6666; padding: 10px;');
}

/**
 * 音乐弹窗拖拽（依赖 jQuery UI）
 * jQuery UI 已改为按需加载，加载完成前直接返回，由 perf-loader.js 加载完成后回调重新绑定。
 */
function initMusicDraggable() {
    if (typeof jQuery === 'undefined' || typeof jQuery.fn.draggable !== 'function') return;
    try {
        jQuery("#music-modal").draggable({
            containment: "body",
            scroll: false
        });
    } catch (e) {}
}

let pjaxLoading = false;
let scrollloadInstance = null;
let currentPjaxUrl = window.location.href.split('#')[0];

/**
 * 滚动位置记忆（2026-10-23）
 * 之前 PJAX 每次导航都强制 scrollTo(0)，从文章页返回首页后浏览进度全丢，
 * 需要从头再滚一遍。这里按 URL 记录滚动位置：离开前保存，返回时恢复；
 * 若目标位置超出当前内容高度（首页只恢复了第一页），先补加载分页再滚过去。
 * 记忆存 sessionStorage，刷新页面也能续上（单标签内）。
 */
const scrollMemory = (function () {
    const STORAGE_KEY = 'icefox-scroll-memory';
    let mem = {};
    let persistTimer = null;

    try {
        const raw = sessionStorage.getItem(STORAGE_KEY);
        if (raw) { mem = JSON.parse(raw) || {}; }
    } catch (e) { }

    function keyOf(url) {
        try {
            const u = new URL(url, window.location.href);
            return u.pathname + u.search;
        } catch (e) {
            return String(url || '');
        }
    }

    function persist() {
        clearTimeout(persistTimer);
        persistTimer = setTimeout(function () {
            try { sessionStorage.setItem(STORAGE_KEY, JSON.stringify(mem)); } catch (e) { }
        }, 300);
    }

    return {
        save: function (url, y, force) {
            const k = keyOf(url);
            const v = Math.max(0, Math.round(y || 0));
            if (window.__dbg) window.__dbg('save ' + k + ' = ' + v + (force ? ' [force]' : ''));
            // 自动保存的 0 不允许覆盖已有进度（popstate 瞬间浏览器钳位滚动会产生假 0）
            if (!force && v === 0 && (mem[k] || 0) > 0) return;
            if (mem[k] === v) return;
            mem[k] = v;
            persist();
        },
        get: function (url) {
            const v = mem[keyOf(url)];
            return typeof v === 'number' ? v : 0;
        },
        // 立即落盘（页面卸载 / PJAX 离开前调用，防抖会丢数据）
        flush: function () {
            clearTimeout(persistTimer);
            try { sessionStorage.setItem(STORAGE_KEY, JSON.stringify(mem)); } catch (e) { }
        }
    };
})();

// 离开页面前强制落盘（bfcache / 整页重载 / 关闭都经过 pagehide）
window.addEventListener('pagehide', function () {
    scrollMemory.flush();
});

// 滚动时持续记录当前位置（写入内存，落盘有 300ms 防抖）。
// 2026-10-24：导航过渡期挂起——popstate/换页瞬间 DOM 与 URL 短暂不匹配，
// 浏览器钳位滚动会产生假位置（如把首页记录清零），必须丢弃。
let scrollSaveSuspended = false;
$(window).on('scroll', function () {
    if (scrollSaveSuspended) return;
    const y = window.scrollY;
    if (y <= 0) return;
    scrollMemory.save(window.location.href, y);
});

/* 调试浮层（2026-10-24）：URL 带 scrolldebug=1 时开启，跨 PJAX / 整页重载保持（window.name）。
   用于在真机上观察：滚动记录、popstate、pageshow、恢复调用等关键事件。 */
(function () {
    try {
        const enabled = window.location.search.indexOf('scrolldebug=1') !== -1
            || (window.name || '').indexOf('scrolldebug') !== -1;
        if (!enabled) return;
        window.name = 'scrolldebug-session';

        const attach = function () {
            if (document.getElementById('scroll-debug-panel')) return;
            const panel = document.createElement('div');
            panel.id = 'scroll-debug-panel';
            panel.style.cssText = 'position:fixed;left:4px;bottom:4px;z-index:2147483000;max-width:94%;max-height:42%;overflow:auto;background:rgba(0,0,0,.85);color:#4ade80;font:11px/1.5 Menlo,Consolas,monospace;padding:6px 8px;border-radius:6px;pointer-events:none;';
            (document.body || document.documentElement).appendChild(panel);
        };
        attach();
        // PJAX 换页不会动 body 直属节点，但保险起见监听一次
        setInterval(attach, 2000);

        window.__dbg = function (msg) {
            const panel = document.getElementById('scroll-debug-panel');
            if (!panel) return;
            const line = document.createElement('div');
            const t = new Date();
            line.textContent = ('0' + t.getHours()).slice(-2) + ':' + ('0' + t.getMinutes()).slice(-2) + ':' + ('0' + t.getSeconds()).slice(-2) + ' ' + msg;
            panel.appendChild(line);
            while (panel.childNodes.length > 30) panel.removeChild(panel.firstChild);
        };
        window.__dbg('ready ' + location.pathname + location.search);

        let lastY = -1;
        window.addEventListener('scroll', function () {
            const y = Math.round(window.scrollY);
            if (Math.abs(y - lastY) > 200) {
                lastY = y;
                window.__dbg('scroll y=' + y);
            }
        });
        window.addEventListener('popstate', function () {
            window.__dbg('POPSTATE → ' + location.pathname);
        });
        window.addEventListener('pagehide', function (e) {
            window.__dbg('pagehide persisted=' + (e && e.persisted));
        });
        window.addEventListener('pageshow', function (e) {
            window.__dbg('pageshow persisted=' + (e && e.persisted) + ' y=' + Math.round(window.scrollY));
        });
        if (typeof window.restoreScrollPosition === 'function') {
            const orig = window.restoreScrollPosition;
            window.restoreScrollPosition = function (t) {
                window.__dbg('restore→' + Math.round(t));
                const p = orig(t);
                if (p && p.then) {
                    p.then(function () { window.__dbg('restore done y=' + Math.round(window.scrollY)); });
                }
                return p;
            };
        }
        setTimeout(function () {
            let saved = {};
            try { saved = JSON.parse(sessionStorage.getItem('icefox-scroll-memory')) || {}; } catch (e) { }
            let navType = '?';
            try { navType = (performance.getEntriesByType('navigation')[0] || {}).type || '?'; } catch (e) { }
            window.__dbg('navType=' + navType + ' saved=' + JSON.stringify(saved));
        }, 1200);
    } catch (e) { }
})();

/**
 * 恢复滚动位置：内容不够高时先按需加载后续分页
 */
async function restoreScrollPosition(targetY) {
    const articleContainer = document.querySelector('.article-container');
    let guard = 0;
    while (
        articleContainer &&
        document.documentElement.scrollHeight - window.innerHeight < targetY &&
        typeof globalData.totalPage !== 'undefined' && globalData.totalPage > 1 &&
        globalData.loadMorePage < globalData.totalPage &&
        guard < 20
    ) {
        guard++;
        const beforeHeight = document.documentElement.scrollHeight;
        globalData.loadMorePage += 1;
        try {
            await pjax(globalData.loadMorePage, '.article-container');
        } catch (e) {
            break;
        }
        // pjax 内部会吞掉请求错误，靠"高度有没有变"判断这一页是否真的加载成功，避免空转到最后一页
        if (document.documentElement.scrollHeight <= beforeHeight) {
            break;
        }
    }
    window.scrollTo({ top: targetY, behavior: 'instant' });
}

// 2026-10-23：接管浏览器自己的滚动恢复。
// 安卓手势返回在部分浏览器/WebView 里会整页重新加载（而不是触发 popstate 让 PJAX 接管），
// 浏览器原生的 scrollRestoration='auto' 此时只会恢复到内容不够高的顶部，还可能和我们的恢复互相覆盖，
// 统一关掉，滚动全部由 scrollMemory 负责。
try {
    if ('scrollRestoration' in history) {
        history.scrollRestoration = 'manual';
    }
} catch (e) { }

/**
 * 整页加载后的浏览进度恢复（2026-10-24 增强）
 * 覆盖安卓手势返回 / 浏览器后退导致的"整页重新加载"场景。
 * 2026-10-24：不再依赖导航类型判断——部分安卓 WebView 对手势返回的报告不准（报成 navigate），
 * 改为直接看 sessionStorage：只要当前 URL 有>0的历史位置就恢复。
 * 能落到这里的只会是"这个标签页之前滚动过这一页"，恢复永远是"接着看"，行为安全。
 */
function restoreScrollAfterFullLoad() {
    const targetY = scrollMemory.get(window.location.href);
    if (targetY > 0) {
        restoreScrollPosition(targetY);
    }
}
window.addEventListener('load', function () {
    setTimeout(restoreScrollAfterFullLoad, 300);
});

/**
 * bfcache（页面缓存）恢复兜底（2026-10-24）
 * 手势返回如果是 bfcache 恢复，scrollRestoration='manual' 可能导致滚动被重置为 0；
 * pageshow(persisted=true) 时按记录重新滚一遍（位置本来就对的话等于无操作）。
 */
window.addEventListener('pageshow', function (event) {
    if (event && event.persisted) {
        setTimeout(function () {
            const targetY = scrollMemory.get(window.location.href);
            if (targetY > 0) {
                window.scrollTo({ top: targetY, behavior: 'instant' });
            }
        }, 60);
    }
});

function showPjaxLoading() {
    let bar = $('#pjax-loading-bar');
    if (bar.length) {
        bar.css({ width: '30%', opacity: '1' });
        setTimeout(() => {
            if (pjaxLoading) bar.css('width', '75%');
        }, 150);
    }
}

function hidePjaxLoading() {
    let bar = $('#pjax-loading-bar');
    if (bar.length) {
        bar.css('width', '100%');
        setTimeout(() => {
            bar.css('opacity', '0');
            setTimeout(() => {
                bar.css('width', '0%');
            }, 300);
        }, 200);
    }
}

/**
 * 2026-11-02：PJAX 换页入场动画
 * 只给 .article-container 挂动画（keyframes 见 style.css）：
 * 新内容淡入 + 从下方明显上浮并轻微放大，回弹"弹"感较强，替代原来的硬切。
 * 不能挂到 #pjax-container / .main-container 上——顶栏 #top-fixed 是 fixed 定位，
 * 祖先生成 transform 会改变它的定位参照，切换瞬间顶栏会跳位。
 */
function playPjaxEnterTransition(container) {
    const target = container.querySelector('.article-container');
    if (!target) return;

    container.classList.remove('pjax-entering');
    // 强制回流，连续两次跳转时也能重新触发同一段动画
    void container.offsetWidth;
    container.classList.add('pjax-entering');

    clearTimeout(playPjaxEnterTransition._timer);
    playPjaxEnterTransition._timer = setTimeout(function () {
        container.classList.remove('pjax-entering');
    }, 900);
}

async function icefoxPjaxNavigate(url, pushState = true, restoreScroll = false) {
    if (pjaxLoading) return;
    pjaxLoading = true;
    showPjaxLoading();

    // 2026-10-24：离开当前页面前记录滚动位置并立即落盘（防抖定时器在快速导航时会丢数据）。
    // 仅在"点链接前进"（pushState=true，此刻 location 仍是来源页）时保存；
    // popstate 返回路径（pushState=false）浏览器已把 URL 换成目标页、滚动被钳位成假位置，
    // 此时保存只会用假位置覆盖真实进度（真机日志铁证：save 目标页 = 0 [force]），必须跳过。
    if (pushState) {
        scrollMemory.save(currentPjaxUrl, window.scrollY, true);
        scrollMemory.flush();
    }
    // 过渡期挂起自动保存：换页瞬间 DOM/URL 不匹配，钳位滚动会产生假位置
    scrollSaveSuspended = true;

    try {
        const response = await fetch(url, {
            headers: {
                'X-PJAX': 'true',
                'X-Requested-With': 'XMLHttpRequest'
            },
            // 2026-10-24：绕过 HTTP 缓存协商。手势返回时 popstate 触发的 fetch 若拿到 304，
            // response.ok 为 false，会被误判为失败而走 location.href 整页重载，导致回到第一屏
            cache: 'reload'
        });

        if (!response.ok) {
            scrollSaveSuspended = false;
            window.location.href = url;
            return;
        }

        const html = await response.text();
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');

        if (doc.title) {
            document.title = doc.title;
        }

        const newPjaxContainer = doc.querySelector('#pjax-container');
        const curPjaxContainer = document.querySelector('#pjax-container');

        if (newPjaxContainer && curPjaxContainer) {
            pauseAllVideos();
            curPjaxContainer.innerHTML = newPjaxContainer.innerHTML;

            // 2026-11-02：换页入场动画 —— 新内容淡入 + 上浮回弹，避免"硬切"的跳页感
            // 动画加在 .article-container 上（样式见 style.css），结束即移除类，不留 transform 残留
            playPjaxEnterTransition(curPjaxContainer);

            if (pushState) {
                window.history.pushState({ pjax: true, url: url }, doc.title || document.title, url);
            }
            currentPjaxUrl = window.location.href.split('#')[0];

            initPageAfterPjax();

            // 2026-10-23：仅"返回"类导航恢复历史位置（必要时补加载分页），普通点链接仍回顶部
            const targetY = restoreScroll ? scrollMemory.get(url) : 0;
            if (window.__dbg) {
                window.__dbg('nav done restore=' + restoreScroll + ' targetY=' + targetY
                    + ' mem=' + JSON.stringify((function () { try { return JSON.parse(sessionStorage.getItem('icefox-scroll-memory')); } catch (e) { return null; } })()));
            }
            if (targetY > 0) {
                restoreScrollPosition(targetY);
            } else {
                window.scrollTo({ top: 0, behavior: 'instant' });
            }
            // 内容替换 + 滚动已定，解除自动保存挂起
            scrollSaveSuspended = false;

            if (window.umami && typeof window.umami.track === 'function') {
                window.umami.track();
            }
        } else {
            scrollSaveSuspended = false;
            window.location.href = url;
            return;
        }
    } catch (err) {
        scrollSaveSuspended = false;
        console.error('PJAX navigation error:', err);
        window.location.href = url;
    } finally {
        // 兜底：无论哪条分支退出，都确保自动保存已解除挂起
        scrollSaveSuspended = false;
        pjaxLoading = false;
        hidePjaxLoading();
    }
}

function initPageAfterPjax() {
    globalData.webSiteHomeUrl = document.querySelector('.webSiteHomeUrl')?.value || globalData.webSiteHomeUrl;
    if (document.querySelector('._currentPage')) {
        globalData.loadMorePage = parseInt(document.querySelector('._currentPage').value);
    } else {
        globalData.loadMorePage = 1;
    }
    if (document.querySelector('._totalPage')) {
        globalData.totalPage = parseInt(document.querySelector('._totalPage').value);
    } else {
        globalData.totalPage = 1;
    }

    loadTopMusicList();
    syncMusicPlayerUI();

    imagePreviewRemoveAllEventListener();
    initFancybox();
    lazyLoadInstance.update();

    loadQW();
    clickQW();
    clickSS();

    // PJAX切换页面后，重新绑定互动悬浮窗、评论、点赞、表情
    clickHudong();
    clickComment();
    clickLike();
    clickEmoji();
    clickEmojiFace();

    resetPlayerStyle();
    intersectionObserver();
    initScrollload();

    // 2026-11-02：弹窗 DOM 随 #pjax-container 一起重建，需重新绑定发布弹窗 iframe
    initPublishModalFrame();
}

function syncMusicPlayerUI() {
    if (globalData.isTopMusic) {
        if (!globalData.audio.paused) {
            showTopMusicPauseUI();
            let currentTime = globalData.audio.currentTime;
            let duration = globalData.audio.duration;
            if (duration > 0) {
                let jdtWidth = (currentTime / duration) * 5;
                $("#top-music-jdt").css('width', jdtWidth + "rem");
            }
        } else {
            showTopMusicPlayUI();
        }
    } else if (globalData.playMusicId && !globalData.audio.paused) {
        showTopMusicPlayUI();
        setMusicImgRotate(globalData.playMusicId, true);
    } else {
        showTopMusicPlayUI();
        $("#top-music-jdt").css('width', "0rem");
        // 2026-10-21：未在播放状态时，确保障面没有封面在旋转
        setMusicImgRotate(0, false);
    }
    // 2026-10-23：卡片图标统一交给 syncMusicIconState，避免各处重复且遗漏
    syncMusicIconState();
}

/**
 * 同步列表卡片的播放 / 暂停图标（幂等，可重复调用）
 * 2026-10-23 修复：从文章页返回主页时 PJAX 只恢复第一页，
 * 滚动加载出来的卡片仍是服务端默认的"播放"图标，
 * 导致音乐还在响但卡片显示三角。这里按全局播放状态重新点亮当前播放的那张。
 */
function syncMusicIconState() {
    $(".music-play").removeClass("hidden");
    $(".music-pause").addClass("hidden");
    if (globalData.playMusicId && !globalData.audio.paused) {
        $("#music-play-" + globalData.playMusicId).addClass("hidden");
        $("#music-pause-" + globalData.playMusicId).removeClass("hidden");
    }
}

function initScrollload() {
    let container = document.querySelector('.main-container');
    let content = document.querySelector('.article-container');
    if (!container || !content) return;

    scrollloadInstance = new Scrollload({
        container: container,
        content: content,
        loadingHtml: generateHtml('-- 加载中 --'),
        noMoreDataHtml: generateHtml('-- 已经到底了 --'),
        exceptionHtml: generateHtml('-- 出现异常 --'),
        loadMore: async function (sl) {
            if (globalData.loadMorePage < globalData.totalPage) {
                globalData.loadMorePage += 1;
                await pjax(globalData.loadMorePage, '.article-container');
                resetPlayerStyle();
                intersectionObserver();
            }
            if (globalData.loadMorePage >= globalData.totalPage) {
                sl.noMoreData();
                return;
            }
            sl.unLock();
        },
        pullRefresh: function (sl) {
            sl.refreshComplete();
        }
    });
}

window.onload = async () => {
    // 网站接口请求地址前缀
    globalData.webSiteHomeUrl = document.querySelector('.webSiteHomeUrl')?.value;
    if (document.querySelector('._currentPage')) {
        globalData.loadMorePage = parseInt(document.querySelector('._currentPage').value);
    }
    if (document.querySelector('._totalPage')) {
        globalData.totalPage = parseInt(document.querySelector('._totalPage').value);
    }

    // 歌曲播放完毕
    globalData.audio.addEventListener('ended', function () {
        refreshAudioUI();

        // 如果是列表播放，则继续播放下一首歌
        if (globalData.isTopMusic === true) {
            if (globalData.playIndex + 1 < globalData.topMusicList.length) {
                globalData.playIndex = globalData.playIndex + 1;
            } else {
                globalData.playIndex = 0;
            }

            let src = globalData.topMusicList[globalData.playIndex];
            loadAudio(src.url);
            globalData.audio.play();
            showFixedMusicPlayer(src.cover);
        } else {
            // 2026-10-21：单曲播完停止后，封面停止旋转
            setMusicImgRotate(globalData.playMusicId, false);
        }
    });

    // 歌曲播放进度
    globalData.audio.addEventListener('timeupdate', function () {
        if (globalData.isTopMusic === true) {
            // 进度
            let currentTime = globalData.audio.currentTime;
            let duration = globalData.audio.duration;
            let jdtWidth = currentTime / duration * 5;//这里的5是w-20的宽度，单位是rem
            $("#top-music-jdt").css('width', jdtWidth + "rem");
        }
    });

    printCopyright();
    loadQW();
    clickQW();
    clickSS();

    // 点击打开互动悬浮框
    clickHudong();

    // 点击评论
    clickComment();

    // 点击点赞
    clickLike();

    // 点击emoji
    clickEmoji();
    // 2026-11-01：补上表情面板开合绑定（此前文章页整页加载时漏调，评论框表情按钮点了没反应）
    clickEmojiFace();

    // 加载顶部音乐
    loadTopMusicList();

    // 大图预览
    initFancybox();

    // 下拉加载更多
    initScrollload();

    // 绑定返回上一页 / 首页
    $(document).on('click', '.go-back', function (e) {
        e.preventDefault();
        let homeUrl = (typeof globalData !== 'undefined' && globalData.webSiteHomeUrl) ? globalData.webSiteHomeUrl : "/";
        // 2026-10-23：点顶部返回箭头属于"返回"，恢复首页此前的滚动位置
        icefoxPjaxNavigate(homeUrl, true, true);
    });

    // 全局页面无刷新 PJAX 链接拦截
    $(document).on('click', 'a', function (e) {
        let a = this;
        let href = a.getAttribute('href');

        // 2026-11-03：内联 onclick 已 return false（如发布弹窗按钮）时不再 PJAX 接管，
        // 否则会把顶层页面切走、连带清掉弹窗 DOM（弹窗在 #pjax-container 内）
        if (e.originalEvent && e.originalEvent.defaultPrevented) {
            return;
        }

        if (!href || href === '' || href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('mailto:') || href.startsWith('tel:')) {
            return;
        }
        if (a.getAttribute('target') === '_blank') {
            return;
        }
        if (a.hasAttribute('no-pjax') || a.hasAttribute('download')) {
            return;
        }

        try {
            let linkUrl = new URL(href, window.location.href);
            if (linkUrl.origin === window.location.origin) {
                // 排除后台管理和操作路径
                if (linkUrl.pathname.indexOf('/admin') !== -1 || linkUrl.pathname.indexOf('/action') !== -1) {
                    return;
                }
                // 排除静态媒体和文件下载
                if (/\.(jpg|jpeg|png|gif|webp|svg|mp3|mp4|m3u8|zip|rar|7z|pdf|txt)$/i.test(linkUrl.pathname)) {
                    return;
                }

                e.preventDefault();
                closeDateFilterModal();
                closeSearchModal();
                closeFriendModal();
                closeUserAuthModal();
                closeUserProfileModal();

                icefoxPjaxNavigate(linkUrl.href);
            }
        } catch (err) {
            // ignore
        }
    });

    // 监听浏览器前进后退历史记录
    window.addEventListener('popstate', function (e) {
        if (window.__dbg) window.__dbg('popstate-handler cur=' + currentPjaxUrl);
        closeDateFilterModal();
        closeSearchModal();
        closeFriendModal();
        closeUserAuthModal();
        closeUserProfileModal();
        let newUrl = window.location.href.split('#')[0];
        if (newUrl !== currentPjaxUrl) {
            currentPjaxUrl = newUrl;
            // 2026-10-23：浏览器前进/后退，恢复目标页面的历史滚动位置
            icefoxPjaxNavigate(window.location.href, false, true);
        } else if (window.__dbg) {
            window.__dbg('popstate SKIP newUrl===currentPjaxUrl');
        }
    });

    var lastSideScrollTop = -1;
    $(window).scroll(function () {
        // 防遮挡：下滑时隐藏右下角浮动按钮组，上滑时恢复，避免盖住动态的点赞/评论按钮
        var nowScrollTop = $(this).scrollTop();
        var sideFloat = $('.side-area').not('.side-area-left');
        if (lastSideScrollTop < 0) {
            lastSideScrollTop = nowScrollTop;
        } else if (Math.abs(nowScrollTop - lastSideScrollTop) > 8) {
            if (nowScrollTop > lastSideScrollTop) {
                sideFloat.addClass('side-area-hidden');
            } else {
                sideFloat.removeClass('side-area-hidden');
            }
            lastSideScrollTop = nowScrollTop;
        }
        if (nowScrollTop <= 8) {
            // 回到页面顶部时始终显示（夜间模式开关需要可达）
            sideFloat.removeClass('side-area-hidden');
        }

        let headerHeight = $("header").height();
        let topFixedHeight = $("#top-fixed").height();
        if ($(this).scrollTop() + topFixedHeight > headerHeight) {
            // 顶部滑动下来
            $('#top-fixed').addClass('bg-[#f0f0f0] is-scrolled');
            $('#top-fixed').addClass('dark:bg-black/30');
            $('#top-fixed').addClass('backdrop-blur-md');
            $("#friend-light").addClass('hidden');
            $("#friend-dark").removeClass('hidden');
            $("#back-light").addClass('hidden');
            $("#back-dark").removeClass('hidden');
            $("#top-play-light").addClass('hidden');
            $("#top-play-dark").removeClass('hidden');
            $("#top-pause-light").addClass('hidden');
            $("#top-pause-dark").removeClass('hidden');
            // 右侧悬浮工具
            $("#go-top").show();
        } else {
            // 顶部未滑动下来
            $('#top-fixed').removeClass('bg-[#f0f0f0] is-scrolled');
            $('#top-fixed').removeClass('dark:bg-black/30');
            $('#top-fixed').removeClass('backdrop-blur-md');
            $("#friend-light").removeClass('hidden');
            $("#friend-dark").addClass('hidden');
            $("#back-light").removeClass('hidden');
            $("#back-dark").addClass('hidden');
            $("#top-play-light").removeClass('hidden');
            $("#top-play-dark").addClass('hidden');
            $("#top-pause-light").removeClass('hidden');
            $("#top-pause-dark").addClass('hidden');
            // 右侧悬浮工具
            $("#go-top").hide();
        }
    });

    initMusicDraggable();

    $(document).on("click", "#fixed-music-close", function () {
        $("#music-modal").hide();

        globalData.playIndex = 0;
        globalData.isTopMusic = false;

        // 顶部音乐进度归0
        $("#top-music-jdt").css('width', "0rem");

        showTopMusicPlayUI();

        closeAudio();
    });

    /**
     * 暂停播放音乐
     */
    $(document).on("click", "#fixed-music-pause", function () {
        pauseAudioOne();

        // 文章列表播放器按钮暂停
        $("#music-play-" + globalData.playMusicId).removeClass("hidden");
        $("#music-pause-" + globalData.playMusicId).addClass("hidden");

        fixedMusicPlayerPauseUI();

        // 顶部播放器按钮暂停
        showTopMusicPlayUI();
    });

    $(document).on("click", "#fixed-music-play", function () {
        playAudioOne();

        // 文章列表播放器按钮继续播放
        $("#music-play-" + globalData.playMusicId).addClass("hidden");
        $("#music-pause-" + globalData.playMusicId).removeClass("hidden");
        fixedMusicPlayerPlayUI();
        if (globalData.isTopMusic) {
            showTopMusicPauseUI();
        }
    });

    /**
     * 顶部音乐播放
     */
    $(document).on("click", ".top-play", function () {
        if (globalData.topMusicList.length === 0) {
            loadTopMusicList();
        }

        if (globalData.isTopMusic) { // 如果本来就是顶部音乐播放
            globalData.audio.play();
        } else {
            // 原本不是顶部音乐播放，现在是顶部音乐播放
            // 文章列表播放器按钮暂停
            $("#music-play-" + globalData.playMusicId).removeClass("hidden");
            $("#music-pause-" + globalData.playMusicId).addClass("hidden");

            // 顶部音乐开始播放
            globalData.playMusicId = 0;
            globalData.playIndex = 0;
            // 2026-10-21：切到顶部歌单时，文章列表里的封面停止旋转
            setMusicImgRotate(0, false);
            if (globalData.topMusicList.length > 0) {
                let src = globalData.topMusicList[globalData.playIndex];
                loadAudio(src.url);
                globalData.audio.play();
                showFixedMusicPlayer(src.cover);
                globalData.isTopMusic = true;
            }
        }

        showTopMusicPauseUI();
    });

    /**
     * 顶部音乐暂停
     */
    $(document).on("click", ".top-pause", function () {
        globalData.audio.pause();
        showTopMusicPlayUI();
    });

    lazyLoadInstance.update();

    resetPlayerStyle();

    intersectionObserver();

    // 2026-11-02：首次整页加载时绑定发布弹窗 iframe
    initPublishModalFrame();
};

var videoTimeOut;
/**
 * 2026-11-05：恢复后台「是否开启可视范围内视频自动播放」(observAutoPlayVideo) 控制权 ——
 * 选「是」时滚到视口 50% 自动播放、滚出自动暂停（原版逻辑）；
 * 选「否」时保持 2026-11-01 的手动点击播放。与 video-shell 海报态/音乐互斥兼容：
 * 程序化 play() 同样会触发 play 事件，自动补齐 is-live 尺寸并暂停音乐。
 */
function intersectionObserver() {
    let observAutoPlayVideo = $("#observAutoPlayVideo").val();
    if (observAutoPlayVideo === 'yes') {
        videoTimeOut = null;
        videoTimeOut = setTimeout(() => {
            $("video").each((index, video) => {
                // 创建 Intersection Observer 实例
                const observer = new IntersectionObserver(
                    (entries) => {
                        entries.forEach((entry) => {
                            if (entry.isIntersecting) {
                                // 懒加载尚未把 data-src 落到 src 时先补上，保证自动播放真正生效
                                if (!video.getAttribute('src') && video.getAttribute('data-src')) {
                                    video.setAttribute('src', video.getAttribute('data-src'));
                                }
                                const p = video.play();
                                if (p && typeof p.catch === 'function') p.catch(function () { });
                            } else {
                                video.pause();
                            }
                        });
                    },
                    {
                        root: null,
                        rootMargin: '0px',
                        threshold: 0.5, // 当视频元素至少有 50% 进入视窗时触发
                    }
                );

                // 开始观察视频元素
                observer.observe(video);
            });
        }, 1000);
    }
}

// 暂停所有页面上的 video 播放
function pauseAllVideos() {
    $('video').each(function () {
        this.pause();
    });
}

function resetPlayerStyle() {
    // 2026-11-01：视频窗口尺寸切换（海报态 16:9 / 播放态真实比例），不依赖 Plyr
    initVideoStage();
    // 2026-10-26：视频卡片环境光模糊背景（不依赖 Plyr，提前初始化）
    initVideoAmbient();
    // Plyr / Hls 已改为按需加载，未就绪时直接返回，由 perf-loader.js 加载完成后回调重新初始化
    if (typeof Plyr === 'undefined') return;
    const players = Array.from(document.querySelectorAll('.js-player')).map((p) =>
        new Plyr(p, {
            // 2026-10-26：补进度条 + 当前/总时长；点击画面切换播放/暂停（暂停时画面中央出大按钮）
            controls: ['play-large', 'play', 'progress', 'current-time', 'duration', 'mute', 'fullscreen'],
            clickToPlay: true
        })
    );
    setTimeout(() => {
        $(".js-player").each((index, item) => {
            var src = $(item).data('src');
            if (isM3U8Url(src)) {
                if (typeof Hls !== 'undefined' && Hls.isSupported()) {
                    var hls = new Hls();
                    hls.loadSource(src);
                    hls.attachMedia(item);
                }
            }
        });
    }, 1000);

}
function isM3U8Url(url) {
    try {
        const parsedUrl = new URL(url);
        return parsedUrl.pathname.endsWith('.m3u8');
    } catch (error) {
        return false;
    }
}

/**
 * 视频环境光模糊背景（2026-10-26 新增）
 * 视频因限高/比例差异出现 CSS 黑边时，用当前帧的模糊放大画面填充，
 * 替代突兀的纯黑边（小红书/抖音同款处理）。跨域视频无法取帧则自动保持黑底。
 */
function initVideoAmbient() {
    document.querySelectorAll('.video-shell > video').forEach(function (v) {
        const shell = v.parentElement;
        if (!shell || shell.dataset.ambient) return;
        shell.dataset.ambient = '1';
        const apply = function () {
            try {
                if (!v.videoWidth || !v.videoHeight) return;
                const c = document.createElement('canvas');
                c.width = 48;
                c.height = 48;
                c.getContext('2d').drawImage(v, 0, 0, 48, 48);
                shell.style.setProperty('--ambient-img', 'url(' + c.toDataURL() + ')');
                shell.classList.add('has-ambient');
            } catch (e) { /* 画布被跨域污染：静默保持黑底 */ }
        };
        if (v.readyState >= 2) {
            apply();
        } else {
            v.addEventListener('loadeddata', apply, { once: true });
        }
    });
}

/**
 * 视频窗口尺寸切换（2026-11-01）
 * 未播放：CSS 固定 16:9 横版海报，首帧 object-fit:cover 裁切（等同横版截图）
 * 点击/播放：给 .video-shell 加 .is-live，按 videoWidth/videoHeight 真实比例重排，最高 75vh
 * 暂停不还原；刷新、重新进入、PJAX 换页后 DOM 重建，自动回到海报态
 */
function initVideoStage() {
    document.querySelectorAll('.video-shell').forEach(function (shell) {
        if (shell.dataset.stageBound) return;
        shell.dataset.stageBound = '1';
        const video = shell.querySelector('video');
        if (!video) return;

        const activate = function () {
            // 元数据未就绪：等真实宽高出来再进 live 态，避免先闪成满宽
            if (!video.videoWidth || !video.videoHeight) {
                video.addEventListener('loadedmetadata', activate, { once: true });
                return;
            }
            syncVideoStage(shell, video);
            shell.classList.add('is-live');
        };

        video.addEventListener('play', activate);
        video.addEventListener('playing', activate);
        // 2026-11-02：视频与音乐互斥——开播自动暂停音乐，视频停下后自动恢复。
        // 站点视频带 loop 循环属性不会触发 ended，因此"暂停"即视为结束；
        // ended 保留兜底（非循环视频/未来去掉 loop 也能恢复），标志位保证不重复触发。
        video.addEventListener('play', pauseMusicForVideo);
        video.addEventListener('pause', resumeMusicAfterVideo);
        video.addEventListener('ended', resumeMusicAfterVideo);

        // 点击画面即播放；点到控制栏/按钮时交给 Plyr 自己处理
        shell.addEventListener('click', function (e) {
            if (shell.classList.contains('is-live')) return;
            const t = e.target;
            if (t && t.closest && t.closest('.plyr__controls, .plyr__control, .plyr__menu, button, input')) return;
            // 懒加载插件有时还没把 data-src 落到 src，点击时补一次再播
            if (!video.getAttribute('src') && video.getAttribute('data-src')) {
                video.setAttribute('src', video.getAttribute('data-src'));
            }
            const p = video.play();
            if (p && typeof p.catch === 'function') p.catch(function () { });
        });
    });

    if (!window.__stageResizeBound) {
        window.__stageResizeBound = true;
        window.addEventListener('resize', function () {
            document.querySelectorAll('.video-shell.is-live').forEach(function (shell) {
                const v = shell.querySelector('video');
                if (v) syncVideoStage(shell, v);
            });
        });
        // 从往返缓存（bfcache）恢复页面时，DOM 连同 .is-live 一起被还原，这里复位成横版海报
        window.addEventListener('pageshow', function (event) {
            if (event && event.persisted) resetVideoStage();
        });
    }
}

/**
 * 视频窗口复位为横版海报态（不清除视频源，只还原尺寸与播放状态）
 */
function resetVideoStage() {
    document.querySelectorAll('.video-shell.is-live').forEach(function (shell) {
        shell.classList.remove('is-live');
        shell.style.removeProperty('--stage-w');
        shell.style.removeProperty('--stage-h');
        const v = shell.querySelector('video');
        if (v) {
            try { v.pause(); } catch (e) { }
        }
    });
}

/**
 * 按视频真实宽高比算出播放窗口尺寸（宽度基准沿用卡片内原始宽度占比）
 */
function syncVideoStage(shell, video) {
    if (!video.videoWidth || !video.videoHeight) return;
    const ratio = video.videoWidth / video.videoHeight;
    const parent = shell.parentElement || shell;
    const parentW = parent.clientWidth || shell.clientWidth;
    if (!parentW) return;

    // 首次记录海报态宽度占父容器的比例，之后窗口缩放按这个比例还原基准宽度
    if (!shell.dataset.stageFrac) {
        shell.dataset.stageFrac = (shell.clientWidth / parentW).toFixed(4);
    }
    const baseW = parentW * (parseFloat(shell.dataset.stageFrac) || 1);
    const maxH = window.innerHeight * 0.75;

    let w = baseW;
    let h = w / ratio;
    if (h > maxH) {
        h = maxH;
        w = h * ratio;
    }
    shell.style.setProperty('--stage-w', Math.round(w) + 'px');
    shell.style.setProperty('--stage-h', Math.round(h) + 'px');
}
/**
 * 顶部音乐显示播放按钮
 */
function showTopMusicPlayUI() {
    $("#top-play").show();
    $("#top-pause").hide();
    // 暂停时进度线回到半透明白（2026-10-19）
    $("#top-music-progress").removeClass("is-playing");
}

/**
 * 顶部音乐显示暂停按钮
 */
function showTopMusicPauseUI() {
    $("#top-play").hide();
    $("#top-pause").show();
    // 播放中进度线加亮为纯白 + 发光（2026-10-19）
    $("#top-music-progress").addClass("is-playing");
}

/**
 * 加载顶部音乐列表
 */
function loadTopMusicList() {
    globalData.topMusicList = [];
    $("#top-music-container > div").each(function (e, elem) {
        let id = $.trim($(elem).data('id'));
        let cover = $.trim($(elem).data('cover'));
        if (id) {
            globalData.topMusicList.push({
                url: `https://music.163.com/song/media/outer/url?id=${id}.mp3`,
                cover: cover
            });
        }
    });
}

/**
 * 加载文章是否需要全文按钮
 */
function loadQW() {
    $.each($(".article-content"), function (index, element) {
        if (element.scrollHeight > element.offsetHeight) {
            let cid = $(element).data('cid');

            //添加全文按钮
            $(".qw-" + cid).removeClass('hidden');
        }
    })
}

// 点击全文按钮
function clickQW() {
    $(document).off('click', '.qw').on('click', '.qw', function (e) {
        let btn = $(this);
        btn.addClass('hidden');
        let cid = btn.data('cid');
        $(".content-" + cid).removeClass("line-clamp-4");
        $(".ss-" + cid).removeClass('hidden');
    });
}

// 点击收起按钮
function clickSS() {
    $(document).off('click', '.ss').on('click', '.ss', function (e) {
        let btn = $(this);
        btn.addClass('hidden');
        let cid = btn.data('cid');
        $(".content-" + cid).addClass("line-clamp-4");
        $(".qw-" + cid).removeClass('hidden');
    });
}

// 全局窗口点击事件：点击外部时自动隐藏悬浮框
$(document).off('click.hudongGlobal').on('click.hudongGlobal', function (event) {
    // 判断点击的是否是悬浮框或互动元素内部，不是就隐藏
    if ($(event.target).closest('.hudong, .hudong-modal, .comment-to, .comment-form, .face, .face-container, .face-item').length) {
        return;
    }
    if ($(event.target).is('input, button, textarea, a')) {
        return;
    }
    // 隐藏所有互动悬浮框
    hiddenHudongModal();
});

/**
 * 点击emoji
 */
function clickEmoji() {
    $(document).off('click', '.face-item').on('click', '.face-item', function (e) {
        let cid = $(this).data('cid');
        var input = $('input[data-cid=' + cid + '].input-text');
        var textToAppend = $(this).text();
        var currentVal = input.val();
        input.val(currentVal + textToAppend);
    });
}

/**
 * 点击emoji表情显示/隐藏emoji
 */
function clickEmojiFace() {
    $(document).off('click', '.face').on('click', '.face', function (e) {
        let cid = $(this).data('cid');
        var faceContainer = $('.face-container[data-cid=' + cid + ']');
        if (faceContainer.hasClass('hidden')) {
            faceContainer.removeClass('hidden');
        } else {
            faceContainer.addClass('hidden');
        }
    });
}

/**
 * 点击互动 (·· 悬浮条)
 */
function clickHudong() {
    $(document).off('click', '.hudong').on('click', '.hudong', function (e) {
        e.stopPropagation();
        let hudongElement = $(this);
        let modal = hudongElement.siblings('.hudong-modal');
        if (!modal.length) {
            modal = hudongElement.next('.hudong-modal');
        }
        if (!modal.length) {
            modal = hudongElement.next();
        }
        let isVisible = !modal.hasClass('hidden');
        hiddenHudongModal();
        if (!isVisible) {
            modal.removeClass('hidden');
        }
    });
}

/**
 * 点击评论
 */
function clickComment() {
    $(document).off('click', '.comment-to').on('click', '.comment-to', function (e) {
        e.stopPropagation();
        let target = $(this);
        let cid = target.data('cid');
        let coid = target.data('coid');

        hiddenHudongModal();

        // 找到已有的评论框
        var existsCommentFormCoid = $(".comment-form").data("coid");
        var existsCommentFormCid = $(".comment-form").data("cid");
        if (existsCommentFormCoid === 'undefined') existsCommentFormCoid = undefined;
        if (existsCommentFormCid === 'undefined') existsCommentFormCid = undefined;

        var hasCommentForm = $(".comment-form").length > 0;

        removeAllCommentForm();

        if (hasCommentForm && existsCommentFormCoid === coid && existsCommentFormCid === cid) {
            return;
        }

        let name = target.data('name');

        if (coid == undefined) {
            $('.comment-ul-cid-' + cid).prepend(getCommentFormHtml(cid));
        } else {
            let targetLi = document.querySelector('.comment-li-coid-' + coid);
            if (targetLi) {
                targetLi.insertAdjacentHTML('afterend', getCommentFormHtml(cid, coid, name));
            }
        }

        $('input[data-cid=' + cid + '].input-text').focus();

        bindCommentSubmit(name);
    });
}

function bindCommentSubmit(replyToName) {
    $(".btn-comment").off('click').on('click', function (e) {
        e.preventDefault();
        let cid = $(e.target).data('cid');
        let coid = $(e.target).data('coid');

        let requiredMail = $("#commentsRequireMail").val();
        let requiredURL = $("#commentsRequireURL").val();

        let author = document.querySelector('.input-author') ? document.querySelector('.input-author').value : '';
        let url = document.querySelector('.input-url') ? document.querySelector('.input-url').value : '';
        let mail = document.querySelector('.input-mail') ? document.querySelector('.input-mail').value : '';
        let text = document.querySelector('.input-text') ? document.querySelector('.input-text').value : '';

        let loginIs = $.trim($("#login-is").text());
        if (loginIs === '1') {
            if (!author) author = $.trim($("#login-screenName").text());
            if (!mail) mail = $.trim($("#login-mail").text());
            if (!url) url = $.trim($("#login-url").text());
        }

        let param = {
            cid: cid,
            parent: coid,
            author: author,
            mail: mail,
            url: url,
            text: text,
        };
        if (param.author === '') {
            alert('昵称不能为空');
            return;
        }
        if (loginIs !== '1' && requiredMail == 1 && param.mail === '') {
            alert('邮件不能为空');
            return;
        }
        if (loginIs !== '1' && requiredURL == 1 && param.url === '') {
            alert('网址不能为空');
            return;
        }
        if (param.text === '') {
            alert('评论内容不能为空');
            return;
        }

        // 记录信息到localStorage
        window.localStorage.setItem('author', author);
        window.localStorage.setItem('mail', mail);
        window.localStorage.setItem('url', url);

        axios.post(globalData.webSiteHomeUrl + '/api/comment', param,
            { headers: { 'content-type': 'application/x-www-form-urlencoded' } })
            .then(function (response) {
                if (response.data.status == 1) {
                    // 2026-11-02：评论提交成功的触觉反馈（手机端"嗒嗒"两下确认感）
                    hapticFeedback([12, 40, 12]);
                    removeAllCommentForm();

                    let waiting = '';
                    if (response.data.comment.status == 'waiting') {
                        waiting = '<span class="comment-waiting">待审核</span>';
                    }

                    if (param.parent > 0) {
                        let toName = replyToName || '';
                        let targetLi = document.querySelector('.comment-li-coid-' + param.parent);
                        if (targetLi) {
                            targetLi.insertAdjacentHTML('afterend', `
                                <li class="pos-rlt comment-li-coid-${response.data.comment.coid} pb-1 px-2 first-of-type:pt-2">
                                    <div class="comment-body">
                                        <span class="text-[14px] text-color-link">
                                            <a href="${response.data.comment.url}" target="_blank" class="cursor-pointer text-color-link no-underline">${response.data.comment.author}</a>
                                        </span>
                                        <span class="text-[14px]">回复</span>
                                        <span class="text-[14px] text-color-link">${toName}</span>
                                        <span data-separator=":" class="before:content-[attr(data-separator)] text-[14px] cursor-help comment-to" data-coid="${response.data.comment.coid}" data-cid="${response.data.comment.cid}" data-name="${response.data.comment.author}">${param.text}</span>
                                        ${waiting}
                                    </div>
                                </li>`);
                        }
                    } else {
                        let targetUl = document.querySelector('.comment-ul-cid-' + param.cid);
                        if (targetUl) {
                            targetUl.insertAdjacentHTML('beforeend', `
                                <li class="pos-rlt comment-li-coid-${response.data.comment.coid}">
                                    <div class="comment-body">
                                        <span class="text-[14px] text-color-link">
                                            <a href="${response.data.comment.url}" target="_blank" class="cursor-pointer text-color-link no-underline">${response.data.comment.author}</a>
                                        </span>
                                        <span data-separator=":" class="before:content-[attr(data-separator)] text-[14px] cursor-help comment-to" data-coid="${response.data.comment.coid}" data-cid="${response.data.comment.cid}" data-name="${response.data.comment.author}">${param.text}</span>
                                        ${waiting}
                                    </div>
                                </li>`);
                        }
                    }
                } else {
                    alert(response.data.msg);
                }
            })
            .catch(function (error) {
                alert('系统异常，请稍候重试');
            });
    });
}

/**
/**
 * 获取当前点赞者信息（已登录用户 / 评论框中填写的用户 / localStorage 缓存）
 */
function getCurrentLikerInfo(cid) {
    let loginIs = $.trim($("#login-is").text());
    if (loginIs === '1') {
        let name = $.trim($("#login-screenName").text());
        let mail = $.trim($("#login-mail").text());
        if (name) {
            return { author: name, mail: mail };
        }
    }

    // 优先检查当前帖子评论框输入框
    let author = '';
    let mail = '';
    let form = $('.comment-form[data-cid="' + cid + '"]');
    if (form.length) {
        author = $.trim(form.find('.input-author').val() || '');
        mail = $.trim(form.find('.input-mail').val() || '');
    }
    // 检查页面上任何已打开评论框
    if (!author || !mail) {
        $('.comment-form').each(function () {
            let a = $.trim($(this).find('.input-author').val() || '');
            let m = $.trim($(this).find('.input-mail').val() || '');
            if (a && m) {
                author = a;
                mail = m;
                return false;
            }
        });
    }
    // 检查本地持久化存储
    if (!author || !mail) {
        author = $.trim(window.localStorage.getItem('author') || '');
        mail = $.trim(window.localStorage.getItem('mail') || '');
    }

    return { author: author, mail: mail };
}

/**
 * 2026-11-02：移动端触觉反馈（点赞等互动时的轻微振动）
 * - navigator.vibrate 仅移动端浏览器支持，桌面端/不支持的浏览器自动跳过
 * - 需要 HTTPS（本站满足）；iOS Safari 对该 API 支持有限，不支持时静默忽略
 * - pattern：毫秒数（单次振动）或 [振动,停顿,振动...] 数组
 */
function hapticFeedback(pattern) {
    try {
        if (navigator.vibrate) {
            navigator.vibrate(pattern);
        }
    } catch (e) { /* 忽略 */ }
}

/**
 * 点击点赞
 */
function clickLike() {
    $(document).off('click', '.like-to').on('click', '.like-to', function (e) {
        e.stopPropagation();
        // 2026-11-02：点赞/取消点赞的触觉反馈——点赞轻振一下，取消双短振
        hapticFeedback($(this).data('agree') === 1 ? 15 : [10, 40, 10]);
        let target = $(this);
        let cid = target.data('cid');
        let agree = target.data('agree');

        if (!cid) {
            return alert('点赞失败');
        }

        let liker = getCurrentLikerInfo(cid);
        let param = {
            cid: cid,
            agree: agree,
            author: liker.author || '',
            mail: liker.mail || ''
        };

        axios.post(globalData.webSiteHomeUrl + '/api/like', param, { headers: { 'content-type': 'application/x-www-form-urlencoded' } })
            .then(function (response) {
                if (response.data && response.data.status == 1) {
                    if (agree === 1) {
                        $(".like-to-cancel-" + cid).removeClass('hidden').addClass('flex');
                        $(".like-to-show-" + cid).addClass('hidden').removeClass('flex');
                        $(".like-agree-" + cid).removeClass('hidden').addClass('flex');
                    } else {
                        $(".like-to-cancel-" + cid).addClass('hidden').removeClass('flex');
                        $(".like-to-show-" + cid).removeClass('hidden').addClass('flex');

                        if (response.data.agree == 0) {
                            $(".like-agree-" + cid).addClass('hidden').removeClass('flex');
                        }
                    }

                    if (response.data.agree_content_html) {
                        $(".like-agree-content-" + cid).html(response.data.agree_content_html);
                    } else {
                        $(".fk-cid-" + cid).text(response.data.agree);
                    }
                }
            })
            .catch(function (error) {
                alert('系统异常，请稍候重试');
            });
    });
}

/**
 * 隐藏所有互动悬浮框
 */
function hiddenHudongModal() {
    let hudongModalList = document.querySelectorAll('.hudong-modal');

    hudongModalList.forEach(item => {
        if (!item.classList.contains('hidden')) {
            item.classList.add('hidden');
        }
    });
}

/**
 * 获取评论框Html
 */
function getCommentFormHtml(cid, coid, name) {

    let author = window.localStorage.getItem('author');
    let mail = window.localStorage.getItem('mail');
    let url = window.localStorage.getItem('url');
    if (author == null) {
        author = '';
    }
    if (mail == null) {
        mail = '';
    }
    if (url == null) {
        url = '';
    }

    // 判断是否登录
    let loginClass = "";
    let loginIs = $("#login-is").text();
    if (loginIs === '1') {
        // 已登录
        author = $.trim($("#login-screenName").text());
        mail = $.trim($("#login-mail").text());
        url = $.trim($("#login-url").text());
        loginClass = "hidden";
    }

    let placeholder = '回复内容';
    if (coid) {
        placeholder = '回复@' + name;
    }
    return `
    <li class="comment-form px-2 py-2" data-cid="${cid}" data-coid="${coid}">
    <div class="bg-white dark:bg-[#262626] p-2 rounded-sm border-1 border-solid border-[#07c160]">
        <div class="grid grid-cols-3 gap-2 ${loginClass}">
            <input placeholder="昵称" aria-label="昵称" autocomplete="nickname" class="border-0 outline-none bg-color-primary dark:bg-[#262626] p-1 rounded-sm input-author dark:text-[#cccccc]" data-cid="${cid}" data-coid="${coid}" value="${author}" />
            <input placeholder="网址" aria-label="网址" autocomplete="url" class="border-0 outline-none bg-color-primary dark:bg-[#262626] p-1 rounded-sm input-url dark:text-[#cccccc]" data-cid="${cid}" data-coid="${coid}" value="${url}" />
            <input placeholder="邮箱" aria-label="邮箱" type="email" autocomplete="email" class="border-0 outline-none bg-color-primary dark:bg-[#262626] p-1 rounded-sm input-mail dark:text-[#cccccc]" data-cid="${cid}" data-coid="${coid}" value="${mail}" />
        </div>
        <div class="mt-2">
            <input placeholder="${placeholder}" class="border-0 outline-none w-full rounded-sm p-1 input-text dark:bg-[#262626] dark:text-[#cccccc]" data-cid="${cid}" data-coid="${coid}" />
        </div>
        <div class="face-container hidden" data-cid="${cid}" data-coid="${coid}">
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">😀</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">😄</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">😁</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">😆</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">😅</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">😂</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">🤣</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">😊</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">😇</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">🙂</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">🙃</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">😉</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">😌</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">😍</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">🥰</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">😘</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">😗</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">😙</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">😚</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">😋</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">😛</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">😝</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">😜</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">🤪</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">🤨</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">🧐</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">🤓</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">😎</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">🤩</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">🥳</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">😏</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">😒</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">😞</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">😔</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">😟</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">😕</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">🙁</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">☹️</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">😣</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">😖</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">😫</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">😩</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">🥺</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">😢</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">😭</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">😤</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">😠</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">😡</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">🤬</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">🤯</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">😳</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">🥵</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">🥶</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">😱</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">😨</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">😰</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">😥</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">😓</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">🤗</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">🤔</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">🤭</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">🤫</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">🤥</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">😶</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">😐</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">😑</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">😬</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">🙄</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">😯</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">😦</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">😧</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">😮</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">😲</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">🥱</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">😴</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">🤤</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">😪</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">😵</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">🤐</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">🥴</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">🤢</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">🤮</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">🤧</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">😷</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">🤒</span>
<span class="cursor-pointer face-item" data-cid="${cid}" data-coid="${coid}">🤕</span>
        </div>
        <div class="flex justify-end mt-2">
            <div class="face dark:face-dark mr-2 cursor-pointer" data-cid="${cid}" data-coid="${coid}"></div>
            <button class="btn-comment bg-[#07c160] border-0 outline-none text-white cursor-pointer rounded-sm" data-cid="${cid}" data-coid="${coid}">回复</button>
        </div>
    </div>
</li>
    `;
}

/**
 * 移除其他评论框
 */
function removeAllCommentForm() {
    $(".comment-form").remove();
}

/**
 * 下拉加载底部显示文字生成html
 */
function generateHtml(html) {
    return html;
}

let imgElementArray = [];
let gallery;
let savedScrollY = 0;

/**
 * 全局初始化 Fancybox
 */
function initFancybox() {
    if (typeof Fancybox === "undefined") return;
    try {
        Fancybox.unbind(document.body, "[data-fancybox]");
    } catch (e) {}

    Fancybox.bind(document.body, "[data-fancybox]", {
        Hash: false,            // 关键：彻底禁用 Hash 路由插件，避免退出大图调用 history.back() 触发全局 PJAX 重载跳顶
        Thumbs: false,          // 不显示底部图片组
        hideScrollbar: false,   // 关键：禁止修改 body 的 overflow，防止滚动条破坏重置
        autoFocus: false,       // 禁止焦点强切导致滚动跳动
        trapFocus: false,       // 禁用焦点陷阱
        placeFocusBack: false,  // 退出大图后不自动恢复焦点，防止焦点跳回触发浏览器定位
        animated: true,
        on: {
            init: () => {
                savedScrollY = window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;
            },
            destroy: () => {
                // 安全兜底：如果退出预览后发生滚动位置偏差，立即原位恢复
                let currentY = window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;
                if (Math.abs(currentY - savedScrollY) > 5) {
                    window.scrollTo({ top: savedScrollY, behavior: 'instant' });
                }
            }
        }
    });
}

/**
 * 大图预览兼容保留函数
 */
function imagePreviewAddEventListener(element) {
    // Fancybox.bind 已在 document.body 上全局委托，自动响应所有新旧 [data-fancybox] 元素
}

function preview(event) {
    initFancybox();
}

/**
 * 移除所有图片大图预览绑定事件（兼容保留）
 */
function imagePreviewRemoveAllEventListener() {
    imgElementArray = [];
}

/**
 * pjax请求，追加列表分页
 */
async function pjax(pageIndex, container) {
    let currentUrl = new URL(window.location.href);
    let filterYear = document.querySelector('#filter_year')?.value || currentUrl.searchParams.get('filter_year');
    let filterMonth = document.querySelector('#filter_month')?.value || currentUrl.searchParams.get('filter_month');

    let url;
    if (filterYear && parseInt(filterYear) > 0) {
        currentUrl.searchParams.set('page', pageIndex);
        currentUrl.searchParams.set('filter_year', filterYear);
        if (filterMonth && parseInt(filterMonth) > 0) {
            currentUrl.searchParams.set('filter_month', filterMonth);
        } else {
            currentUrl.searchParams.delete('filter_month');
        }
        url = currentUrl.pathname + currentUrl.search;
    } else {
        url = globalData.webSiteHomeUrl + '/page/' + pageIndex;
    }

    await axios.get(url).then(async (e) => {
        // 获取新内容
        var domParser = new DOMParser();
        var doc = domParser.parseFromString(e.data, 'text/html');
        var newContainer = doc.querySelector(container);
        if (!newContainer) return;

        // 追加到当前列表的最下方
        imagePreviewRemoveAllEventListener();

        var articleContainer = document.querySelector(container);
        var newArticles = newContainer.querySelectorAll('article');
        if (newArticles.length > 0) {
            newArticles.forEach((art) => {
                articleContainer.appendChild(art);
            });
        } else {
            articleContainer.appendChild(newContainer);
        }

        // 重新绑定图片预览
        let previewImages = document.querySelectorAll('.preview-image');
        previewImages.forEach((element) => {
            imagePreviewAddEventListener(element);
        });
        // 重新绑定互动
        clickHudong();
        // 重新绑定评论
        clickComment();
        // 重新绑定点赞
        clickLike();

        loadQW();
        clickQW();
        clickSS();

        // 异步加载
        lazyLoadInstance.update();

        // 2026-10-23：新追加的卡片按当前播放状态点亮（修复"音乐在响但图标是三角"）
        syncMusicIconState();
    }).catch(e => {

    });
}

/**
 * 回到顶部
 */
var timeOut;

function scrollToTop() {
    // if (document.body.scrollTop != 0 || document.documentElement.scrollTop != 0) {
    //     window.scrollBy(0, -50);
    //     timeOut = setTimeout('scrollToTop()', 10);
    // } else clearTimeout(timeOut);
    // 使用Anime.js进行平滑滚动
    anime({
        targets: 'html, body',
        scrollTop: 0,
        duration: 300,
        easing: 'linear'
    });
}

/**
 * 加载音乐
 */
/**
 * 2026-10-21：音乐封面旋转效果已按用户要求取消（列表页/文章页统一为方形静止封面）。
 * 本函数保留为"只清理"：负责在暂停/切歌/切页/关闭时移除可能残留的旋转类，
 * 不再主动添加旋转；将来若要恢复黑胶转动效果，把这里的 addClass 分支恢复即可。
 */
function setMusicImgRotate(cid, rotating) {
    $(".music-img").removeClass("rotate-animation");
}

function loadAudio(src) {
    globalData.audio.src = src;
    globalData.audio.load();
}

function closeAudio() {
    globalData.audio.pause();
    globalData.audio.src = '';
    globalData.playMusicId = 0;
    // 2026-11-02：悬浮播放器被关闭，视频结束后不再"恢复"一个已关掉的音乐
    globalData.musicPausedByVideo = false;

    // 2026-10-21：关闭悬浮播放器时一并停止封面旋转
    setMusicImgRotate(0, false);

    refreshAudioUI();
}

/**
 * 2026-11-02：视频开播时自动暂停背景音乐（避免两种声音混在一起）
 * 由每个视频的 play 事件触发（点击画面 / Plyr 控制条两条路径都会触发原生 play）。
 * syncMusicPlayerUI 负责顶栏与卡片图标；悬浮框按钮单独用 fixedMusicPlayerPauseUI 同步
 * （2026-11-02 补：之前漏了悬浮框，音乐停了但悬浮框还显示"暂停中"的图标）。
 */
function pauseMusicForVideo() {
    if (!globalData.audio || globalData.audio.paused) return;
    globalData.musicPausedByVideo = true;
    globalData.audio.pause();
    syncMusicPlayerUI();
    fixedMusicPlayerPauseUI();
}

/**
 * 2026-11-02：视频停下（用户暂停 / 播放结束）后恢复之前被视频暂停的音乐
 * - 视频是 loop 循环播放，用户按暂停即视为"播放结束"
 * - 用户在视频播放期间已手动恢复音乐（悬浮框/卡片），则不再重复播
 * - 页面上还有其他视频在播（连播场景），等最后一个停下再恢复
 * - 用户已关闭悬浮播放器（closeAudio），不再恢复
 */
function resumeMusicAfterVideo() {
    if (!globalData.musicPausedByVideo) return;
    const otherPlaying = Array.from(document.querySelectorAll('video'))
        .some(function (v) { return !v.paused && !v.ended; });
    if (otherPlaying) return; // 还有视频在播，保持暂停，等它结束
    globalData.musicPausedByVideo = false;
    if (!globalData.audio || !globalData.audio.paused) return;
    const p = globalData.audio.play();
    if (p && typeof p.catch === 'function') p.catch(function () { });
    syncMusicPlayerUI();
    fixedMusicPlayerPlayUI();
}

/**
 * 播放音乐
 */
function playAudio(cid, src, cover) {
    if (globalData.playMusicId != cid) {
        loadAudio(src);
        globalData.playMusicId = cid;
    }
    globalData.audio.play();

    // 2026-10-21：封面旋转效果已取消，此处仅负责清理可能残留的旋转类
    setMusicImgRotate(cid, true);

    refreshAudioUI();

    // 隐藏播放按钮，显示暂停按钮
    $("#music-play-" + cid).addClass("hidden");
    $("#music-pause-" + cid).removeClass("hidden");

    // 显示悬浮播放器
    showFixedMusicPlayer(cover);

    showTopMusicPlayUI();

    globalData.isTopMusic = false;

    // 顶部音乐进度归0
    $("#top-music-jdt").css('width', "0rem");
}

/**
 * 显示悬浮播放器
 */
function showFixedMusicPlayer(cover) {
    if ($("#music-modal").is(":hidden")) {
        $("#music-modal").show();
    }

    $("#fixed-music-cover").attr("src", cover);

    fixedMusicPlayerPlayUI();
}

function playAudioOne() {
    globalData.audio.play();
}

/**
 * 暂停音乐
 */
function pauseAudio(cid) {
    globalData.audio.pause();
    // 隐藏暂停按钮，显示播放按钮
    $("#music-play-" + cid).removeClass("hidden");
    $("#music-pause-" + cid).addClass("hidden");

    setMusicImgRotate(cid, false);

    fixedMusicPlayerPauseUI();
}

/**
 * 仅播放音乐。
 */
function pauseAudioOne() {
    globalData.audio.pause();
}

/**
 * 悬浮播放器暂停UI
 */
function fixedMusicPlayerPauseUI() {
    $("#fixed-music-play").show();
    $("#fixed-music-pause").hide();
}
/**
 * 悬浮播放器播放UI
 */
function fixedMusicPlayerPlayUI() {
    $("#fixed-music-play").hide();
    $("#fixed-music-pause").show();
}
/**
 * 刷新播放器UI
 */
function refreshAudioUI() {

    // 隐藏其他文章的播放器播放按钮
    $.each($(".music-play"), function (index, item) {
        $(item).removeClass("hidden");
    });
    $.each($(".music-pause"), function (index, item) {
        $(item).addClass("hidden");
    });

    fixedMusicPlayerPauseUI();
}

/**
 * 打开朋友圈弹框
 */
function showFriendModal() {
    $("#friend-modal").show();
    $("body").addClass("overflow-hidden");
}

/**
 * 关闭朋友圈弹框
 */
function closeFriendModal() {
    $("#friend-modal").hide();
    $("body").removeClass("overflow-hidden");
}

/**
 * 打开时间快速查询弹框
 */
function showDateFilterModal() {
    $("#date-filter-modal").removeClass("hidden").show();
    $("body").addClass("overflow-hidden");
}

/**
 * 关闭时间快速查询弹框
 */
function closeDateFilterModal() {
    $("#date-filter-modal").addClass("hidden").hide();
    $("body").removeClass("overflow-hidden");
}

/* ============================== 全站搜索（2026-10-23） ============================== */

let searchTimer = null;
let searchRequestSeq = 0;

/**
 * 打开全站搜索弹框，输入框自动聚焦
 */
function showSearchModal() {
    $("#search-modal").removeClass("hidden").show();
    $("body").addClass("overflow-hidden");
    setTimeout(function () {
        let input = document.getElementById("search-input");
        if (input) { input.focus(); }
    }, 60);
}

/**
 * 关闭搜索弹框并复位
 */
function closeSearchModal() {
    if ($("#search-modal").is(":hidden")) return;
    $("#search-modal").addClass("hidden").hide();
    $("body").removeClass("overflow-hidden");
    $("#search-input").val("");
    searchRequestSeq++;
    $("#search-results").html('<div class="search-empty">输入关键词，搜索全部朋友圈动态</div>');
}

/**
 * 安全渲染"文本 + 关键词高亮"：全部走 textContent / <mark> 元素，不拼 HTML 字符串
 */
function renderSearchText(container, text, keyword) {
    container.textContent = "";
    if (!keyword) {
        container.textContent = text;
        return;
    }
    let lowerText = text.toLowerCase();
    let lowerKw = keyword.toLowerCase();
    let pos = 0, idx;
    while ((idx = lowerText.indexOf(lowerKw, pos)) > -1) {
        if (idx > pos) {
            container.appendChild(document.createTextNode(text.slice(pos, idx)));
        }
        let mark = document.createElement("mark");
        mark.textContent = text.substr(idx, keyword.length);
        container.appendChild(mark);
        pos = idx + keyword.length;
    }
    if (pos < text.length) {
        container.appendChild(document.createTextNode(text.slice(pos)));
    }
}

/**
 * 渲染搜索结果列表
 */
function renderSearchResults(list, keyword) {
    let wrap = $("#search-results");
    wrap.empty();
    if (!list || !list.length) {
        wrap.html('<div class="search-empty">没有找到相关动态，换个关键词试试</div>');
        return;
    }
    list.forEach(function (item) {
        let a = document.createElement("a");
        a.className = "search-result-item";
        a.href = item.url;

        let title = document.createElement("div");
        title.className = "search-result-title";
        renderSearchText(title, item.title, keyword);

        let snippet = document.createElement("div");
        snippet.className = "search-result-snippet";
        renderSearchText(snippet, item.snippet, keyword);

        let date = document.createElement("div");
        date.className = "search-result-date";
        date.textContent = item.date;

        a.appendChild(title);
        a.appendChild(snippet);
        a.appendChild(date);
        wrap[0].appendChild(a);
    });
}

/**
 * 执行搜索：GET /api/search?q=xxx，防抖 300ms 后由调用方触发；带序号防竞态
 */
function doSiteSearch(keyword) {
    keyword = (keyword || "").trim();
    let wrap = $("#search-results");
    if (!keyword) {
        searchRequestSeq++;
        wrap.html('<div class="search-empty">输入关键词，搜索全部朋友圈动态</div>');
        return;
    }
    let seq = ++searchRequestSeq;
    wrap.html('<div class="search-empty">搜索中…</div>');

    let homeUrl = (typeof globalData !== "undefined" && globalData.webSiteHomeUrl) ? globalData.webSiteHomeUrl : "";
    if (!homeUrl) {
        let el = document.querySelector(".webSiteHomeUrl");
        homeUrl = el ? el.value : "/";
    }
    // webSiteHomeUrl 末尾可能带 index.php 或为纯路径，统一补分隔再拼参数式 API，规避伪静态差异
    let apiUrl = homeUrl.replace(/\/+$/, "") + "/?api=search&q=" + encodeURIComponent(keyword);

    $.ajax({
        url: apiUrl,
        type: "GET",
        dataType: "json",
        timeout: 8000
    }).done(function (res) {
        if (seq !== searchRequestSeq) return;
        if (res && res.status === 1) {
            renderSearchResults(res.list, keyword);
        } else {
            wrap.html('<div class="search-empty">' + ((res && res.msg) || "搜索失败，请稍后再试") + "</div>");
        }
    }).fail(function () {
        if (seq !== searchRequestSeq) return;
        wrap.html('<div class="search-empty">网络异常，请稍后再试</div>');
    });
}

// 搜索输入：防抖 300ms；回车立即搜
$(document).on("input", "#search-input", function () {
    let val = this.value;
    clearTimeout(searchTimer);
    searchTimer = setTimeout(function () { doSiteSearch(val); }, 300);
});
$(document).on("keydown", "#search-input", function (e) {
    if (e.key === "Enter") {
        clearTimeout(searchTimer);
        doSiteSearch(this.value);
    }
});

// 全局快捷键：Esc 关闭；Ctrl/⌘+K 或 "/" 唤起（焦点不在输入类控件时）
$(document).on("keydown", function (e) {
    let searchModalVisible = $("#search-modal").length && !$("#search-modal").hasClass("hidden");
    if (e.key === "Escape" && searchModalVisible) {
        closeSearchModal();
        return;
    }
    let tag = (e.target && e.target.tagName || "").toLowerCase();
    let inEditable = tag === "input" || tag === "textarea" || tag === "select" || (e.target && e.target.isContentEditable);
    if (inEditable) return;
    if ((e.ctrlKey || e.metaKey) && (e.key === "k" || e.key === "K")) {
        e.preventDefault();
        showSearchModal();
    } else if (e.key === "/") {
        e.preventDefault();
        showSearchModal();
    }
});
/* ============================ 全站搜索结束 ============================ */

/**
 * 打开用户注册 / 登录弹窗
 */
function showUserAuthModal(tab = 'register') {
    switchAuthTab(tab);
    $("#auth-alert-msg").addClass("hidden").text("");
    $("#user-auth-modal").removeClass("hidden").show();
    $("body").addClass("overflow-hidden");
}

/**
 * 关闭用户注册 / 登录弹窗
 */
function closeUserAuthModal() {
    $("#user-auth-modal").addClass("hidden").hide();
    $("body").removeClass("overflow-hidden");
}

/**
 * 切换注册 / 登录 Tab
 * 2026-11-02：Tab 改为胶囊分段控件，绿色滑块（#auth-seg-slider）弹性滑动
 */
function switchAuthTab(tab) {
    if (tab === 'register') {
        $("#tab-auth-register-btn").addClass("active");
        $("#tab-auth-login-btn").removeClass("active");
        $("#panel-auth-register").removeClass("hidden").show();
        $("#panel-auth-login").addClass("hidden").hide();
        $("#auth-seg-slider").css("transform", "translateX(0)");
    } else {
        $("#tab-auth-login-btn").addClass("active");
        $("#tab-auth-register-btn").removeClass("active");
        $("#panel-auth-login").removeClass("hidden").show();
        $("#panel-auth-register").addClass("hidden").hide();
        $("#auth-seg-slider").css("transform", "translateX(100%)");
    }
    $("#auth-alert-msg").addClass("hidden").text("");
}

/**
 * 密码输入框显隐小眼睛（2026-11-02）
 * 切换 input.type 并同步睁眼/闭眼图标，保持焦点与光标位置
 */
function toggleAuthPwd(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    const eyeOpen = btn.querySelector('.eye-open');
    const eyeClosed = btn.querySelector('.eye-closed');
    if (eyeOpen) eyeOpen.style.display = show ? 'none' : '';
    if (eyeClosed) eyeClosed.style.display = show ? '' : 'none';
    // 保持焦点在输入框，光标回到末尾
    const len = input.value.length;
    input.focus();
    try { input.setSelectionRange(len, len); } catch (e) { }
}

/**
 * 打开已登录用户详情弹窗
 */
function showUserProfileModal() {
    $("#user-profile-modal").removeClass("hidden").show();
    $("body").addClass("overflow-hidden");
}

/**
 * 关闭已登录用户详情弹窗
 */
function closeUserProfileModal() {
    $("#user-profile-modal").addClass("hidden").hide();
    $("body").removeClass("overflow-hidden");
}

/* ============================================================
 * 2026-11-02：发布朋友圈改为 iframe 弹窗
 * - 发布页逻辑太重（图片九宫格/音乐/视频直传/表情/草稿），不搬家，弹窗内用 iframe 原样加载
 * - 发布成功后 Typecho 会把 iframe 重定向到后台文章列表，据此判定"已提交"：
 *   关闭弹窗 → PJAX 局部刷新首页动态列表（只替换 #pjax-container 的 innerHTML，
 *   globalData.audio 是页面级全局实例、悬浮播放器 DOM 在刷新范围之外，音乐不会中断）
 * - 任何环节异常都退回整页跳转，功能不会丢
 * ============================================================ */
let publishModalOpen = false;

/**
 * 打开发布弹窗（返回 false，供 onclick="return openPublishModal();" 阻止默认跳转）
 */
function openPublishModal() {
    // 一并收起可能开着的其他弹窗
    if (typeof closeUserProfileModal === 'function') closeUserProfileModal();
    if (typeof closeUserAuthModal === 'function') closeUserAuthModal();

    const modal = document.getElementById('publish-modal');
    const frame = document.getElementById('publish-modal-frame');

    // 兜底：弹窗结构缺失（老缓存/未包含 modal.php 的页面）时按原方式整页跳转
    if (!modal || !frame) {
        const link = document.getElementById('header-publish-btn');
        if (link && link.getAttribute('href')) {
            window.location.href = link.getAttribute('href');
        }
        return false;
    }

    publishModalOpen = true;

    const loading = document.getElementById('publish-modal-loading');
    if (loading) loading.classList.remove('hidden');

    const url = frame.getAttribute('data-publish-url');
    if (url && frame.getAttribute('src') !== url) {
        frame.setAttribute('src', url);
    }

    initPublishModalFrame();

    // 兜底：极端情况下 iframe load 事件丢失（如被拦截/缓存）时，6 秒后强制收起 loading
    clearTimeout(openPublishModal._loadingFallback);
    openPublishModal._loadingFallback = setTimeout(function () {
        const loading = document.getElementById('publish-modal-loading');
        if (loading) loading.classList.add('hidden');
    }, 6000);

    modal.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
    return false;
}

/**
 * 关闭发布弹窗
 * @param {boolean} stopFrame 是否把 iframe 重置为 about:blank（停止弹窗内媒体、清空已填内容）
 */
function closePublishModal(stopFrame = true) {
    const modal = document.getElementById('publish-modal');
    if (!modal) return;

    modal.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
    publishModalOpen = false;

    if (stopFrame) {
        const frame = document.getElementById('publish-modal-frame');
        if (frame) frame.setAttribute('src', 'about:blank');
    }
}

/**
 * 绑定 iframe 的 load 监听（幂等；PJAX 换页后弹窗 DOM 会重建，需要重新绑定）
 */
function initPublishModalFrame() {
    const frame = document.getElementById('publish-modal-frame');
    if (!frame || frame.dataset.publishBound === '1') return;
    frame.dataset.publishBound = '1';

    frame.addEventListener('load', function () {
        let href = '';
        try {
            href = frame.contentWindow.location.href || '';
        } catch (e) {
            href = '';
        }
        if (!href || href === 'about:blank') return;
        if (!publishModalOpen) return;

        if (isPublishPageUrl(href)) {
            // 首次进入发布页：隐藏页内自带的返回箭头（弹窗自己有关闭按钮），并收起 loading
            hidePublishFrameBackArrow(frame);
            const loading = document.getElementById('publish-modal-loading');
            if (loading) loading.classList.add('hidden');
            return;
        }

        // 已离开发布页 → 判定为提交完成（成功 / 需登录 / 其他）
        onPublishFrameFinished(href);
    });
}

/**
 * 当前 iframe 地址是否仍是发布页
 */
function isPublishPageUrl(href) {
    const frame = document.getElementById('publish-modal-frame');
    const target = frame ? (frame.getAttribute('data-publish-url') || '') : '';
    if (!target) return false;
    try {
        const a = new URL(href, window.location.origin);
        const b = new URL(target, window.location.origin);
        return a.pathname.replace(/\/+$/, '') === b.pathname.replace(/\/+$/, '');
    } catch (e) {
        return href.indexOf(target) === 0;
    }
}

/**
 * 注入样式：隐藏发布页顶栏里的返回箭头（同域可直接改 contentDocument）
 */
function hidePublishFrameBackArrow(frame) {
    try {
        const doc = frame.contentDocument;
        if (!doc || !doc.head) return;
        if (doc.getElementById('publish-frame-embed-style')) return;
        const style = doc.createElement('style');
        style.id = 'publish-frame-embed-style';
        style.textContent = '.main-container .h-14 > div:first-child > a{display:none !important}';
        doc.head.appendChild(style);
    } catch (e) {
        // 未就绪或跨域时忽略，不影响发布
    }
}

/**
 * iframe 提交完成后的收尾
 */
function onPublishFrameFinished(href) {
    publishModalOpen = false;

    const needLogin = /login|signin/i.test(href);
    const succeeded = /manage-posts|write-post|manage/i.test(href);

    closePublishModal();

    if (needLogin) {
        showPublishToast('登录状态已失效，请刷新页面后重试', true);
        return;
    }

    showPublishToast(succeeded ? '发布成功，正在刷新动态…' : '已提交，正在刷新动态…');
    // 2026-11-02：发布完成的三连轻振，比评论/点赞更有"完成感"
    if (succeeded) {
        hapticFeedback([15, 50, 15, 50, 15]);
    } else {
        hapticFeedback([12, 40, 12]);
    }
    refreshFeedAfterPublish();
}

/**
 * 发布后刷新首页动态列表
 * 走 PJAX 局部替换（只换 #pjax-container）：音乐播放、悬浮框状态都不会被重置
 */
function refreshFeedAfterPublish() {
    let homeUrl = (typeof globalData !== 'undefined' && globalData.webSiteHomeUrl) ? globalData.webSiteHomeUrl : '';
    if (!homeUrl) {
        try {
            homeUrl = new URL('/', window.location.origin + '/').href;
        } catch (e) {
            homeUrl = '/';
        }
    }

    if (!document.querySelector('#pjax-container') || typeof icefoxPjaxNavigate !== 'function') {
        window.location.href = homeUrl;
        return;
    }

    let sameAsHome = false;
    try {
        const cur = new URL(window.location.href);
        const home = new URL(homeUrl, window.location.origin);
        sameAsHome = cur.pathname.replace(/\/+$/, '') === home.pathname.replace(/\/+$/, '');
    } catch (e) {
        sameAsHome = false;
    }

    // 已在首页：pushState=false 不新增历史记录；在其它页（文章页/第N页）：正常压一条记录
    icefoxPjaxNavigate(homeUrl, !sameAsHome, false);
}

/**
 * 轻量提示条（不复用后台 Notice，独立一个浮层）
 */
function showPublishToast(msg, isError) {
    let el = document.getElementById('publish-toast');
    if (!el) {
        el = document.createElement('div');
        el.id = 'publish-toast';
        el.style.cssText = 'position:fixed;left:50%;top:24px;z-index:10001;padding:10px 18px;'
            + 'border-radius:999px;font-size:13px;font-weight:600;color:#fff;line-height:1.4;'
            + 'box-shadow:0 10px 24px rgba(0,0,0,.18);opacity:0;pointer-events:none;'
            + 'transform:translateX(-50%) translateY(-12px);transition:opacity .25s,transform .25s;'
            + 'max-width:82vw;text-align:center;';
        document.body.appendChild(el);
    }
    el.style.background = isError ? '#ef4444' : '#07c160';
    el.textContent = msg;

    requestAnimationFrame(function () {
        el.style.opacity = '1';
        el.style.transform = 'translateX(-50%) translateY(0)';
    });

    clearTimeout(el._hideTimer);
    el._hideTimer = setTimeout(function () {
        el.style.opacity = '0';
        el.style.transform = 'translateX(-50%) translateY(-12px)';
    }, 2200);
}

/**
 * 弹窗提示信息展示
 */
function showAuthAlert(msg, type = 'error') {
    let box = $("#auth-alert-msg");
    box.removeClass("hidden bg-red-50 text-red-600 border border-red-200 bg-green-50 text-green-600 border-green-200");
    if (type === 'success') {
        box.addClass("bg-green-50 text-green-600 border border-green-200").text(msg).show();
    } else {
        box.addClass("bg-red-50 text-red-600 border border-red-200").text(msg).show();
    }
}

/**
 * 前端 AJAX 注册
 */
function submitAjaxRegister() {
    let name = $.trim($("#reg-username").val());
    let screenName = $.trim($("#reg-screenname").val());
    let mail = $.trim($("#reg-email").val());
    let password = $("#reg-password").val();
    let confirm = $("#reg-confirm").val();

    if (!name) {
        showAuthAlert('请输入用户名');
        return;
    }
    if (!mail) {
        showAuthAlert('请输入电子邮箱');
        return;
    }
    if (!password || password.length < 6) {
        showAuthAlert('密码长度至少6位');
        return;
    }
    if (password !== confirm) {
        showAuthAlert('两次输入的密码不一致');
        return;
    }

    let btn = $("#btn-submit-register");
    btn.prop("disabled", true).text("正在注册...");

    let homeUrl = (typeof globalData !== "undefined" && globalData.webSiteHomeUrl) ? globalData.webSiteHomeUrl : "";

    axios.post(homeUrl + '/api/register', {
        name: name,
        screenName: screenName,
        mail: mail,
        password: password,
        confirm: confirm
    }, {
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    }).then(function (res) {
        btn.prop("disabled", false).text("立即注册并直接登录");
        if (res.data && res.data.status === 1) {
            applyLoginState(res.data.user);
            showAuthAlert('注册成功，已自动登录！无需进后台，可直接评论。', 'success');
            setTimeout(() => {
                closeUserAuthModal();
            }, 1000);
        } else {
            showAuthAlert((res.data && res.data.msg) || '注册失败，请稍后重试');
        }
    }).catch(function (err) {
        btn.prop("disabled", false).text("立即注册并直接登录");
        showAuthAlert('网络请求失败，请稍后重试');
    });
}

/**
 * 前端 AJAX 登录
 */
function submitAjaxLogin() {
    let name = $.trim($("#login-username").val());
    let password = $("#login-password").val();

    if (!name) {
        showAuthAlert('请输入用户名或注册邮箱');
        return;
    }
    if (!password) {
        showAuthAlert('请输入登录密码');
        return;
    }

    let btn = $("#btn-submit-login");
    btn.prop("disabled", true).text("正在登录...");

    let homeUrl = (typeof globalData !== "undefined" && globalData.webSiteHomeUrl) ? globalData.webSiteHomeUrl : "";

    axios.post(homeUrl + '/api/login', {
        name: name,
        password: password
    }, {
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    }).then(function (res) {
        btn.prop("disabled", false).text("立即登录");
        if (res.data && res.data.status === 1) {
            applyLoginState(res.data.user);
            showAuthAlert('登录成功！', 'success');
            setTimeout(() => {
                closeUserAuthModal();
            }, 800);
        } else {
            showAuthAlert((res.data && res.data.msg) || '登录失败，用户名或密码错误');
        }
    }).catch(function (err) {
        btn.prop("disabled", false).text("立即登录");
        showAuthAlert('网络请求失败，请稍后重试');
    });
}

/**
 * 前端 AJAX 退出登录
 */
function submitAjaxLogout() {
    let homeUrl = (typeof globalData !== "undefined" && globalData.webSiteHomeUrl) ? globalData.webSiteHomeUrl : "";
    axios.post(homeUrl + '/api/logout', {}, {
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    }).then(function () {
        applyLogoutState();
        closeUserProfileModal();
        alert('已成功退出登录');
    }).catch(function () {
        applyLogoutState();
        closeUserProfileModal();
    });
}

/**
 * 应用登录状态至页面 DOM
 */
function applyLoginState(user) {
    if (!user) return;
    $("#login-is").text("1");
    $("#login-screenName").text(user.screenName || user.name);
    $("#login-mail").text(user.mail || "");
    $("#login-url").text(user.url || "");

    window.localStorage.setItem('author', user.screenName || user.name);
    window.localStorage.setItem('mail', user.mail || "");
    window.localStorage.setItem('url', user.url || "");

    let avatarUrl = user.avatar || ("https://cravatar.cn/avatar/" + "?s=64&d=identicon");
    let userBtn = $("#header-user-btn");
    userBtn.addClass("is-avatar");
    userBtn.attr("onclick", "showUserProfileModal();")
           .attr("title", "已登录：" + (user.screenName || user.name));

    userBtn.html(`
        <div class="relative flex items-center justify-center" id="header-user-avatar-wrap">
            <img width="28" height="28" src="${avatarUrl}"
                class="rounded-full object-cover border border-white/80 dark:border-gray-500 shadow-sm" id="header-user-avatar-img" alt="个人中心" />
            <span class="absolute bottom-[-1px] right-[-1px] w-2 h-2 bg-[#07c160] rounded-full border border-white dark:border-gray-800"></span>
        </div>
    `);

    // 动态显示发布动态按钮（若具备权限）
    if (user.group === 'administrator' || user.group === 'editor' || user.group === 'contributor') {
        $("#header-publish-btn").removeClass("hidden").show();
    } else {
        $("#header-publish-btn").addClass("hidden").hide();
    }

    $("#profile-modal-avatar").attr("src", avatarUrl);
    $("#profile-modal-name").text(user.screenName || user.name);
    $("#profile-modal-mail").text(user.mail || "");
    if (user.group === 'administrator') {
        $("#profile-modal-group").text('网站管理员');
        $("#profile-modal-admin-actions").removeClass("hidden").show();
    } else {
        $("#profile-modal-group").text('已登录用户');
        $("#profile-modal-admin-actions").addClass("hidden").hide();
    }
}

/**
 * 应用退出登录状态至页面 DOM
 */
function applyLogoutState() {
    $("#login-is").text("0");
    $("#login-screenName").text("");
    $("#login-mail").text("");
    $("#login-url").text("");
    $("#profile-modal-admin-actions").addClass("hidden").hide();

    window.localStorage.removeItem('author');
    window.localStorage.removeItem('mail');
    window.localStorage.removeItem('url');

    let userBtn = $("#header-user-btn");
    userBtn.removeClass("is-avatar");
    userBtn.attr("onclick", "showUserAuthModal('register');")
           .attr("title", "用户注册 / 登录");

    userBtn.html(`
        <div class="relative flex items-center justify-center" id="header-user-visitor-wrap" title="用户注册 / 登录">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="hover:opacity-80 transition" id="admin-user-icon">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
            </svg>
        </div>
    `);

    // 退出登录时隐藏发布按钮
    $("#header-publish-btn").addClass("hidden").hide();
}
