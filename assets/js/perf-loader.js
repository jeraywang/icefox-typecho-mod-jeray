/**
 * Icefox 主题性能优化：重型库按需加载
 *
 * 原本 hls(412KB) / fancybox(257KB) / jqueryui(255KB) / plyr(113KB) 等
 * 全部在 head 中同步加载，合计超过 1MB，直接阻塞首屏。
 * 这里改为：页面确实需要时才加载，否则空闲时再补，用不到的页面完全不下载。
 */
(function () {
    var cur = document.currentScript || document.querySelector('script[data-icefox-theme-url]');
    var base = (cur && cur.getAttribute('data-icefox-theme-url')) || '';
    var loaded = {};

    function loadScript(file, cb) {
        if (loaded[file]) {
            if (cb) cb();
            return;
        }
        loaded[file] = 1;
        var s = document.createElement('script');
        s.src = base + file;
        s.onload = function () {
            if (cb) cb();
        };
        s.onerror = function () {
            if (cb) cb();
        };
        document.head.appendChild(s);
    }

    function loadSeries(files, cb) {
        var i = 0;
        (function next() {
            if (i >= files.length) {
                if (cb) cb();
                return;
            }
            loadScript(files[i++], next);
        })();
    }

    function loadCss(file) {
        var l = document.createElement('link');
        l.rel = 'stylesheet';
        l.href = base + file;
        document.head.appendChild(l);
    }

    function whenIdle(fn) {
        if (typeof window.requestIdleCallback === 'function') {
            window.requestIdleCallback(function () {
                fn();
            }, { timeout: 3000 });
        } else {
            window.setTimeout(fn, 1500);
        }
    }

    function hasVideo() {
        if (document.querySelector('video, .js-player')) {
            return true;
        }
        var els = document.querySelectorAll('[data-src]');
        for (var i = 0; i < els.length; i++) {
            var src = els[i].getAttribute('data-src') || '';
            if (/\.(mp4|m3u8|webm|mov)(\?|$)/i.test(src)) {
                return true;
            }
        }
        return false;
    }

    document.addEventListener('DOMContentLoaded', function () {
        // 老浏览器才需要 IntersectionObserver 补丁
        if (!('IntersectionObserver' in window)) {
            loadScript('/assets/js/intersection-observer.min.js');
        }

        // 视频播放器：plyr + hls + plyr.css，约 557KB，无视频的页面完全不加载
        if (hasVideo()) {
            loadCss('/assets/css/plyr.css');
            loadSeries(['/assets/js/plyr.js', '/assets/js/hls.min.js'], function () {
                if (typeof window.resetPlayerStyle === 'function') {
                    window.resetPlayerStyle();
                }
            });
        }

        // 图片灯箱：fancybox 约 289KB，有图片时才加载
        if (document.querySelector('[data-fancybox]')) {
            loadCss('/assets/css/fancybox.css');
            loadScript('/assets/js/fancybox.umd.js', function () {
                if (typeof window.initFancybox === 'function') {
                    window.initFancybox();
                }
            });
        }

        // 音乐弹窗拖拽：jqueryui 约 264KB，浏览器空闲时再补
        whenIdle(function () {
            if (document.getElementById('music-modal')) {
                loadSeries([
                    '/assets/js/jqueryui.min.js',
                    '/assets/js/jquery.ui.touch.punch.min.js'
                ], function () {
                    if (typeof window.initMusicDraggable === 'function') {
                        window.initMusicDraggable();
                    }
                });
            }
        });
    });
})();
