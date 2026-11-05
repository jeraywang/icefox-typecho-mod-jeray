<?php

use Typecho\Common;

if (!defined('__TYPECHO_ROOT_DIR__'))
    exit;

// 设置版本号（公共分发版 1.0.0：上传走 Typecho 原生机制，已清除个人隐私配置）
if (!defined("__THEME_VERSION__")) {
    define("__THEME_VERSION__", "1.0.0");
}

// 设置默认头像源为https://cravatar.cn/avatar/
// define('__TYPECHO_GRAVATAR_PREFIX__', 'https://cravatar.cn/avatar/');

//icefox 核心包
include_once 'core/core.php';

function themeConfig($form)
{
    ?>
    <?php /* 2026-11-05 公共版：去掉写死的主题目录名 'icefox'，改用当前主题目录，避免用户重命名文件夹后后台样式 404 */ ?>
    <link rel="stylesheet" href="<?php Helper::options()->themeUrl('assets/css/admin.css'); ?>">
    <div>
        <div class="admin-title">
            Icefox主题后台配置（v<?php echo __THEME_VERSION__; ?>）
            <div>
                <?php
                backupThemeData();
                ?>
            </div>
        </div>
        <div>
            <div>
                <?php

                $backgroundImageUrl = new Typecho_Widget_Helper_Form_Element_Text(
                    'backgroundImageUrl',
                    null,
                    null,
                    _t('站点顶部背景图'),
                    _t('在这里填入一个图片 URL 地址, 以在站点顶部显示该图片')
                );

                $form->addInput($backgroundImageUrl);

                $userAvatarUrl = new Typecho_Widget_Helper_Form_Element_Text(
                    'userAvatarUrl',
                    null,
                    null,
                    _t('站点顶部用户头像'),
                    _t('在这里填入一个图片 URL 地址')
                );

                $form->addInput($userAvatarUrl);

                $archiveUserAvatarUrl = new Typecho_Widget_Helper_Form_Element_Text(
                    'archiveUserAvatarUrl',
                    null,
                    null,
                    _t('文章侧用户头像<span style="color:red;">(如果不设置，默认使用头像源地址的头像)</span>'),
                    _t('在这里填入一个图片 URL 地址')
                );

                $form->addInput($archiveUserAvatarUrl);

                $avatarTitle = new Typecho_Widget_Helper_Form_Element_Text(
                    'avatarTitle',
                    null,
                    null,
                    _t('顶部用户头像旁名称'),
                    _t('在这里填入顶部用户头像旁展示的名称')
                );

                $form->addInput($avatarTitle);

                $about = new Typecho_Widget_Helper_Form_Element_Text(
                    'about',
                    null,
                    null,
                    _t('顶部头像旁名称跳转地址'),
                    _t('顶部头像旁名称跳转地址')
                );
                $form->addInput($about);

                $topPost = new Typecho_Widget_Helper_Form_Element_Text(
                    "topPost",
                    null,
                    null,
                    "置顶文章",
                    "格式：文章的ID || 文章的ID || 文章的ID （中间使用 || 分隔）"
                );
                $form->addInput($topPost);

                $beian = new Typecho_Widget_Helper_Form_Element_Text(
                    "beian",
                    null,
                    null,
                    "备案号",
                    "例如：京ICP备00000001号"
                );
                $form->addInput($beian);

                $policeBeian = new Typecho_Widget_Helper_Form_Element_Text(
                    "policeBeian",
                    null,
                    null,
                    "公安网备号",
                    "网备号和跳转地址通过||分隔开<br>例如：川公安网备000000001号 || http://www.beian.gov.cn/portal/registerSystemInfo?recordcode=00000001"
                );
                $form->addInput($policeBeian);

                $observAutoPlayVideo = new Typecho_Widget_Helper_Form_Element_Radio(
                    'observAutoPlayVideo',
                    [
                        'yes' => _t("是"),
                        'no' => _t("否")
                    ],
                    'yes',
                    _t('是否开启可视范围内视频自动播放'),
                    _t('默认开启')
                );
                $form->addInput($observAutoPlayVideo);

                $autoPlayVideo = new Typecho_Widget_Helper_Form_Element_Radio(
                    'autoPlayVideo',
                    [
                        'yes' => _t("是"),
                        'no' => _t("否")
                    ],
                    'yes',
                    _t('是否默认播放视频<span style="color:red;">（开启可视范围内自动播放功能后，此功能失效）</span>')
                );
                $form->addInput($autoPlayVideo);

                $autoMutedPlayVideo = new Typecho_Widget_Helper_Form_Element_Radio(
                    'autoMutedPlayVideo',
                    [
                        'yes' => _t("是"),
                        'no' => _t("否")
                    ],
                    'yes',
                    _t('是否默认静音播放视频')
                );
                $form->addInput($autoMutedPlayVideo);

                $avatarSource = new Typecho_Widget_Helper_Form_Element_Text(
                    "avatarSource",
                    null,
                    null,
                    "主题左侧头像源",
                    "不填则默认为https://cravatar.cn/avatar/"
                );
                $form->addInput($avatarSource);

                $defaultThemeColor = new Typecho_Widget_Helper_Form_Element_Radio(
                    'defaultThemeColor',
                    [
                        'yes' => _t("暗色"),
                        'no' => _t("亮色")
                    ],
                    'yes',
                    _t('默认主题配色')
                );
                $form->addInput($defaultThemeColor);

                $enableTopMusic = new Typecho_Widget_Helper_Form_Element_Radio(
                    'enableTopMusic',
                    [
                        'yes' => _t("是"),
                        'no' => _t("否")
                    ],
                    'yes',
                    _t('是否启用顶部网易云音乐')
                );
                $form->addInput($enableTopMusic);

                $topMusicList = new Typecho_Widget_Helper_Form_Element_Textarea(
                    'topMusicList',
                    null,
                    null,
                    _t('顶部网易云音乐歌曲列表'),
                    _t('网页顶部播放器播放，每一行一首歌，格式如下<br>网易云音乐id || 音乐封面图')
                );
                $form->addInput($topMusicList);

                $friendLinks = new Typecho_Widget_Helper_Form_Element_Textarea(
                    "friendLinks",
                    null,
                    null,
                    "友情链接",
                    "使用||分隔，每一行一个友情链接。格式如下<br>logo || 名称 || 链接"
                );
                $form->addInput($friendLinks);

                $publishPageUrl = new Typecho_Widget_Helper_Form_Element_Text(
                    "publishPageUrl",
                    null,
                    null,
                    "前端发布页地址",
                    "后台新建独立页面地址对应页面"
                );
                $form->addInput($publishPageUrl);

                $script = new Typecho_Widget_Helper_Form_Element_Textarea(
                    "script",
                    null,
                    null,
                    "全局自定义JavaScript",
                    "不用添加script标签"
                );
                $form->addInput($script);

                $css = new Typecho_Widget_Helper_Form_Element_Textarea(
                    "css",
                    null,
                    null,
                    "全局自定义CSS",
                    "不用添加style标签"
                );
                $form->addInput($css);

                ?>
            </div>
        </div>
    </div>

    <?php


}

function themeFields($layout)
{
    ?>
    <style>
        textarea {
            width: 100%;
            height: 8rem;
        }

        input[type=text] {
            width: 100%;
        }
    </style>
    <?php
    $friendVideo = new Typecho_Widget_Helper_Form_Element_Textarea(
        'friend_video',
        null,
        null,
        _t('朋友圈视频'),
        _t('<span>在这里填入朋友圈视频地址（例如：https://domain/xxx.mp4，支持MP4/m3u8直链；填入后将优先展示视频播放器）</span>')
    );
    $friendVideo->input->setAttribute('class', 't-video-find friend_video_input');
    $layout->addItem($friendVideo);

    $friendPicture = new Typecho_Widget_Helper_Form_Element_Textarea(
        'friend_pictures',
        null,
        null,
        _t('朋友圈图片'),
        _t('<span style="color:#576b95;">（前端发布朋友圈时会自动同步填充；若在后台编写文章，图片直接在上方正文中插入即可，系统会自动识别并排成九宫格）</span>')
    );
    $friendPicture->input->setAttribute('class', 't-default-find');
    $layout->addItem($friendPicture);

    $position = new Typecho_Widget_Helper_Form_Element_Text(
        'position',
        null,
        null,
        _t('发布定位'),
        _t('<span>在这里填定位名称（例：成都市·天府广场）</span>')
    );
    $position->input->setAttribute('class', 't-default-find');
    $layout->addItem($position);

    $positionUrl = new Typecho_Widget_Helper_Form_Element_Text(
        'positionUrl',
        null,
        null,
        _t('定位跳转地址')
    );
    $positionUrl->input->setAttribute('class', 't-default-find');
    $layout->addItem($positionUrl);

    $isAdvertise = new Typecho_Widget_Helper_Form_Element_Radio(
        "isAdvertise",
        [
            "1" => _t("是"),
            "0" => _t("不是"),
        ],
        "0",
        _t("是否是广告"),
        _t('<span>默认不是</span>')
    );
    $isAdvertise->input->setAttribute('class', 't-default-find');
    $layout->addItem($isAdvertise);

    $music = new Typecho_Widget_Helper_Form_Element_Textarea(
        'music',
        null,
        null,
        _t('插入音乐'),
        _t('填入完整的音乐信息，格式如下（中间用两个竖线加空格 <strong> || </strong> 隔开）：<br><strong>歌曲名称 || 专辑名称 || 播放地址 || 封面图片地址</strong><br>注：网易云音乐外链地址格式为：https://music.163.com/song/media/outer/url?id=歌曲ID.mp3')
    );
    $music->input->setAttribute('class', 't-music-find');
    $layout->addItem($music);

    // $canComment = new Typecho_Widget_Helper_Form_Element_Radio(
    //     "canComment",
    //     [
    //         "1" => _t("允许"),
    //         "0" => _t("不允许"),
    //     ],
    //     "1",
    //     _t("是否允许评论"),
    //     _t('<span style="color:red;">默认允许评论</span>')
    // );
    // $layout->addItem($canComment);
}

//自定义字段扩展
Typecho_Plugin::factory('admin/write-post.php')->bottom = array('tabField', 'tabs');
Typecho_Plugin::factory('admin/write-page.php')->bottom = array('tabField', 'tabs');
class tabField
{
    public static function tabs()
    {
        ?>
        <style>
            .tabss {
                margin: 10px;
                clear: both;
                display: block;
                height: 30px;
                padding: 0
            }

            ;

            .tabss a {
                outline: none !important
            }

            ;
        </style>

        <script>
            $(function () {
                var tabsHtml = `
                    <ul class="typecho-option-tabs tabss">
                        <li class="current" id="t-default"><a href="javascript:;">默认</a></li>
                        <li class="" id="t-video"><a href="javascript:;">视频</a></li>
                        <li class="" id="t-music"><a href="javascript:;">音乐</a></li>
                    </ul>`;
                $("#custom-field-expand").after(tabsHtml);

                // 在音乐输入框上方注入可视化音乐添加面板
                var musicHelperHtml = `
                    <div id="admin-music-helper" style="margin-bottom: 14px; padding: 14px; background: #f8f9fa; border: 1px solid #e2e8f0; border-radius: 8px;">
                        <div style="display: flex; gap: 8px; margin-bottom: 12px;">
                            <button type="button" id="adm-tab-netease" class="btn btn-s btn-primary" style="background:#07c160;border-color:#07c160;color:#fff;">网易云 ID</button>
                            <button type="button" id="adm-tab-custom" class="btn btn-s btn-default">自定义音乐</button>
                        </div>
                        <div id="adm-panel-netease" style="display:block;">
                            <div style="font-size:12px;color:#666;margin-bottom:6px;">填写网易云歌曲 ID 或粘贴歌曲链接，自动获取封面/歌名/歌手：</div>
                            <div style="display:flex;gap:8px;align-items:center;">
                                <input type="text" id="adm-netease-val" placeholder="例如：447925558 或歌曲链接" class="text" style="flex:1;height:32px;padding:4px 8px;border-radius:4px;" />
                                <button type="button" id="adm-btn-fetch" class="btn btn-s" style="background:#07c160;border-color:#07c160;color:#fff;height:32px;">获取并添加</button>
                                <button type="button" id="adm-btn-clear-netease" class="btn btn-s btn-default" style="height:32px;">清除</button>
                            </div>
                        </div>
                        <div id="adm-panel-custom" style="display:none;">
                            <div style="font-size:12px;color:#666;margin-bottom:6px;">填写音乐直链、封面、歌名和歌手：</div>
                            <div style="display:flex;flex-direction:column;gap:6px;">
                                <input type="text" id="adm-custom-url" placeholder="音乐链接（mp3 直链）" class="text" style="width:100%;height:30px;padding:4px 8px;" />
                                <input type="text" id="adm-custom-pic" placeholder="封面图片链接（可选）" class="text" style="width:100%;height:30px;padding:4px 8px;" />
                                <div style="display:flex;gap:8px;">
                                    <input type="text" id="adm-custom-title" placeholder="歌名" class="text" style="flex:1;height:30px;padding:4px 8px;" />
                                    <input type="text" id="adm-custom-artist" placeholder="歌手" class="text" style="flex:1;height:30px;padding:4px 8px;" />
                                </div>
                                <div style="display:flex;gap:8px;margin-top:4px;">
                                    <button type="button" id="adm-btn-add-custom" class="btn btn-s" style="background:#07c160;border-color:#07c160;color:#fff;height:32px;flex:1;">添加音乐</button>
                                    <button type="button" id="adm-btn-clear-custom" class="btn btn-s btn-default" style="height:32px;flex:1;">清除</button>
                                </div>
                            </div>
                        </div>
                        <div id="adm-music-preview" style="display:none;margin-top:10px;padding:8px 12px;background:#fff;border:1px solid #cbd5e1;border-radius:6px;align-items:center;justify-content:space-between;">
                            <div style="display:flex;align-items:center;gap:10px;">
                                <img id="adm-preview-cover" src="" style="width:40px;height:40px;object-fit:cover;border-radius:4px;background:#eee;" />
                                <div>
                                    <div id="adm-preview-title" style="font-weight:600;font-size:13px;color:#333;"></div>
                                    <div id="adm-preview-artist" style="font-size:11px;color:#888;"></div>
                                </div>
                            </div>
                            <button type="button" id="adm-btn-remove" class="btn btn-s btn-warn" style="font-size:11px;">移除</button>
                        </div>
                    </div>`;

                var musicInput = $('.t-music-find');
                if (musicInput.length) {
                    musicInput.before(musicHelperHtml);
                }

                // 切换后台音乐 Tab
                $("#adm-tab-netease").click(function () {
                    $(this).css({background:'#07c160', borderColor:'#07c160', color:'#fff'}).removeClass('btn-default').addClass('btn-primary');
                    $("#adm-tab-custom").css({background:'', borderColor:'', color:''}).removeClass('btn-primary').addClass('btn-default');
                    $("#adm-panel-netease").show();
                    $("#adm-panel-custom").hide();
                });
                $("#adm-tab-custom").click(function () {
                    $(this).css({background:'#07c160', borderColor:'#07c160', color:'#fff'}).removeClass('btn-default').addClass('btn-primary');
                    $("#adm-tab-netease").css({background:'', borderColor:'', color:''}).removeClass('btn-primary').addClass('btn-default');
                    $("#adm-panel-custom").show();
                    $("#adm-panel-netease").hide();
                });

                var siteApiUrl = '<?php echo rtrim(getWebsiteHomeUrl(), '/') . '/api/music-info'; ?>';

                // 解析网易云歌曲ID
                function parseNeteaseId(str) {
                    if (!str) return '';
                    str = $.trim(str);
                    var m = str.match(/id=(\d+)/i) || str.match(/song\/(\d+)/i) || str.match(/song\?id=(\d+)/i) || str.match(/^(\d+)$/) || str.match(/(\d{4,12})/);
                    return m ? m[1] : '';
                }

                // 判断标题是否需要自动替换为音乐分享标题
                function shouldUpdateMusicTitle(title) {
                    title = $.trim(title || '');
                    return !title || title === '未命名文档' || /^\d{4}-\d{2}-\d{2}/.test(title) || title.indexOf('[图片分享]') === 0 || title.indexOf('[音乐分享]') === 0;
                }
                function setAdminMusicTitle(songTitle, artist) {
                    if (!songTitle) return;
                    var newTitle = '[音乐分享]' + songTitle + (artist ? ' - ' + artist : '');
                    var current = $('#title').val();
                    if (shouldUpdateMusicTitle(current)) {
                        $('#title').val(newTitle);
                    }
                }

                // 获取并添加网易云
                $("#adm-btn-fetch").click(function () {
                    var val = $.trim($("#adm-netease-val").val());
                    if (!val) {
                        alert('请输入网易云歌曲 ID 或链接');
                        return;
                    }
                    var songId = parseNeteaseId(val);
                    if (!songId) {
                        alert('未能识别出网易云歌曲ID，请输入歌曲ID（如 447925558）或歌曲链接');
                        return;
                    }

                    // 1. 立即生成可播放的外链，确保100%成功，绝不阻断
                    var audioUrl = 'https://music.163.com/song/media/outer/url?id=' + songId + '.mp3';
                    var defaultVal = '网易云歌曲 (' + songId + ') || 网易音乐人 || ' + audioUrl + ' || ';
                    $('.t-music-find').val(defaultVal);

                    $("#adm-preview-cover").attr('src', '');
                    $("#adm-preview-title").text('网易云歌曲 (' + songId + ')');
                    $("#adm-preview-artist").text('网易音乐人');
                    $("#adm-music-preview").css('display', 'flex');

                    // 立即自动填充标题
                    setAdminMusicTitle('网易云歌曲 (' + songId + ')', '网易音乐人');

                    // 2. 异步获取更精确的歌曲名与专辑封面
                    var btn = $(this);
                    btn.text('已生成外链');
                    $.ajax({
                        url: siteApiUrl,
                        type: 'POST',
                        data: { song: songId },
                        dataType: 'json',
                        timeout: 5000,
                        success: function (res) {
                            btn.text('获取并添加');
                            if (res && res.status === 1 && res.data) {
                                var s = res.data;
                                var valStr = s.title + ' || ' + s.artist + ' || ' + s.url + ' || ' + (s.pic || '');
                                $('.t-music-find').val(valStr);

                                if (s.pic) $("#adm-preview-cover").attr('src', s.pic);
                                $("#adm-preview-title").text(s.title);
                                $("#adm-preview-artist").text(s.artist);

                                // 异步获取成功后，更新标题为准确的 [音乐分享]歌曲名 - 歌手
                                setAdminMusicTitle(s.title, s.artist);
                            }
                        },
                        error: function () {
                            btn.text('获取并添加');
                            // 保持已生成的有效外链，不弹错误窗
                        }
                    });
                });

                $("#adm-btn-clear-netease").click(function () {
                    $("#adm-netease-val").val('');
                    $('.t-music-find').val('');
                    $("#adm-music-preview").hide();
                    var curT = $('#title').val();
                    if (curT && curT.indexOf('[音乐分享]') === 0) {
                        $('#title').val('');
                    }
                });

                // 添加自定义音乐
                $("#adm-btn-add-custom").click(function () {
                    var u = $.trim($("#adm-custom-url").val());
                    var p = $.trim($("#adm-custom-pic").val());
                    var t = $.trim($("#adm-custom-title").val());
                    var a = $.trim($("#adm-custom-artist").val()) || '未知歌手';
                    if (!u) {
                        alert('请填写音乐直链');
                        return;
                    }
                    if (!t) {
                        alert('请填写歌名');
                        return;
                    }
                    var valStr = t + ' || ' + a + ' || ' + u + ' || ' + p;
                    $('.t-music-find').val(valStr);

                    $("#adm-preview-cover").attr('src', p || '');
                    $("#adm-preview-title").text(t);
                    $("#adm-preview-artist").text(a);
                    $("#adm-music-preview").css('display', 'flex');

                    // 自动填充标题
                    setAdminMusicTitle(t, a);
                });

                $("#adm-btn-clear-custom").click(function () {
                    $("#adm-custom-url").val('');
                    $("#adm-custom-pic").val('');
                    $("#adm-custom-title").val('');
                    $("#adm-custom-artist").val('');
                    $('.t-music-find').val('');
                    $("#adm-music-preview").hide();
                    var curT = $('#title').val();
                    if (curT && curT.indexOf('[音乐分享]') === 0) {
                        $('#title').val('');
                    }
                });

                $("#adm-btn-remove").click(function () {
                    $('.t-music-find').val('');
                    $("#adm-music-preview").hide();
                    var curT = $('#title').val();
                    if (curT && curT.indexOf('[音乐分享]') === 0) {
                        $('#title').val('');
                    }
                });

                // 监听输入框变化实时更新预览与标题
                $('.t-music-find').on('input propertychange change', function () {
                    var v = $.trim($(this).val());
                    if (v && v.indexOf('||') !== -1) {
                        var parts = v.split('||').map(function (s) { return $.trim(s); });
                        if (parts.length >= 2 && parts[0]) {
                            $("#adm-preview-title").text(parts[0]);
                            $("#adm-preview-artist").text(parts[1] || '未知歌手');
                            if (parts[3]) {
                                $("#adm-preview-cover").attr('src', parts[3]).show();
                            } else {
                                $("#adm-preview-cover").hide();
                            }
                            $("#adm-music-preview").css('display', 'flex');

                            // 若标题为空或默认，同步更新标题
                            setAdminMusicTitle(parts[0], parts[1]);
                            return;
                        }
                    }
                    if (!v) {
                        $("#adm-music-preview").hide();
                    }
                });

                // 后台表单提交时兜底：若有音乐且标题为默认/时间戳/图片分享，强制设置为 [音乐分享]歌曲名 - 歌手
                $('form[name="write_post"]').on('submit', function () {
                    var musicVal = $.trim($('.t-music-find').val() || '');
                    var curT = $('#title').val();
                    if (musicVal && shouldUpdateMusicTitle(curT)) {
                        var parts = musicVal.split('||').map(function (p) { return $.trim(p); });
                        var songName = parts[0] || '';
                        var artistName = parts[1] || '';
                        if (songName) {
                            $('#title').val('[音乐分享]' + songName + (artistName ? ' - ' + artistName : ''));
                        }
                    }
                });

                // 如果已有音乐值，尝试回显预览
                var initMusicVal = $('.t-music-find').val();
                if (initMusicVal && initMusicVal.indexOf('||') !== -1) {
                    var parts = initMusicVal.split('||').map(function (s) { return $.trim(s); });
                    if (parts.length >= 3) {
                        $("#adm-preview-title").text(parts[0]);
                        $("#adm-preview-artist").text(parts[1]);
                        if (parts[3]) $("#adm-preview-cover").attr('src', parts[3]);
                        $("#adm-music-preview").css('display', 'flex');
                    }
                }

                //初始化，全部隐藏
                $("#custom-field>table>tbody").find("tr").hide();

                //初始化显示
                $(".tabss>li.current").parent().siblings("table").find('.t-default-find').closest('tr').show();

                $(".tabss li").click(function () {
                    var clasz = this.id;
                    //删除同胞的current
                    $(this).siblings().removeClass('current');
                    //自身添加current
                    $(this).addClass('current');
                    //全部隐藏
                    $("#custom-field>table>tbody").find("tr").hide();
                    //显示自身底下的子元素
                    $(".tabss>li.current").parent().siblings("table").find('.' + clasz + '-find').closest('tr').show();
                });
            });
        </script>
        <?php
    }
}

/**
 * 备份主题数据
 * @return void
 */
function backupThemeData()
{
    // 公共分发版：备份/还原键跟随本主题目录名（icefox_mogai），
    // 避免与 icefox 原版主题共用同一个数据库 options 键而互相覆盖设置
    $name = "icefox_mogai";
    $db = Typecho_Db::get();
    if (isset($_POST["type"])) {

        if ($_POST["type"] == "创建备份") {
            $value = $db->fetchRow(
                $db
                    ->select()
                    ->from("table.options")
                    ->where("name = ?", "theme:" . $name)
            )["value"];
            if (
                $db->fetchRow(
                    $db
                        ->select()
                        ->from("table.options")
                        ->where("name = ?", "theme:" . $name . "_backup")
                )
            ) {

                $db->query(
                    $db
                        ->update("table.options")
                        ->rows(["value" => $value])
                        ->where("name = ?", "theme:" . $name . "_backup")
                );
                try {
                    Widget_Notice::alloc()->set("备份更新成功", "success");
                    Widget_Options::alloc()->response->redirect(Common::url("options-theme.php", Widget_Options::alloc()->adminUrl));
                } catch (Exception $exception) {
                }
            ?>
            <?php
            } else {
                ?>
                <?php if ($value) {

                    $db->query(
                        $db
                            ->insert("table.options")
                            ->rows(["name" => "theme:" . $name . "_backup", "user" => "0", "value" => $value])
                    );
                    try {
                        Widget_Notice::alloc()->set("备份成功", "success");
                        Widget_Options::alloc()->response->redirect(Common::url("options-theme.php", Widget_Options::alloc()->adminUrl));
                    } catch (Exception $exception) {
                    }
                ?>
                <?php
                }
            }
        }
        if ($_POST["type"] == "还原备份") {
            if (
                $db->fetchRow(
                    $db
                        ->select()
                        ->from("table.options")
                        ->where("name = ?", "theme:" . $name . "_backup")
                )
            ) {

                $_value = $db->fetchRow(
                    $db
                        ->select()
                        ->from("table.options")
                        ->where("name = ?", "theme:" . $name . "_backup")
                )["value"];
                $db->query(
                    $db
                        ->update("table.options")
                        ->rows(["value" => $_value])
                        ->where("name = ?", "theme:" . $name)
                );
                try {
                    Widget_Notice::alloc()->set("备份还原成功", "success");
                    Widget_Options::alloc()->response->redirect(Common::url("options-theme.php", Widget_Options::alloc()->adminUrl));
                } catch (Exception $exception) {
                }
            ?>
            <?php
            } else {

                try {
                    Widget_Notice::alloc()->set("无备份数据，请先创建备份", "error");
                    Widget_Options::alloc()->response->redirect(Common::url("options-theme.php", Widget_Options::alloc()->adminUrl));
                } catch (Exception $exception) {
                }
            ?>
            <?php
            } ?>
        <?php
        }
        ?>
        <?php if ($_POST["type"] == "删除备份") {
            if (
                $db->fetchRow(
                    $db
                        ->select()
                        ->from("table.options")
                        ->where("name = ?", "theme:" . $name . "_backup")
                )
            ) {

                $db->query($db->delete("table.options")->where("name = ?", "theme:" . $name . "_backup"));
                try {
                    Widget_Notice::alloc()->set("删除备份成功", "success");
                    Widget_Options::alloc()->response->redirect(Common::url("options-theme.php", Widget_Options::alloc()->adminUrl));
                } catch (Exception $exception) {
                }
            ?>
            <?php
            } else {

                try {
                    Widget_Notice::alloc()->set("无备份数据，无法删除", "success");
                    Widget_Options::alloc()->response->redirect(Common::url("options-theme.php", Widget_Options::alloc()->adminUrl));
                } catch (Exception $exception) {
                }
            ?>
            <?php
            } ?>
        <?php
        } ?>
    <?php
    }
    ?>

    </form>
    <?php echo '<div class="text-xs">请先点击右下角的保存设置按钮，再创建备份！<br/><br/><form class="backup" action="?calm_backup" method="post">
    <input type="submit" name="type" class="btn primary" value="创建备份" />
    <input type="submit" name="type" class="btn primary" value="还原备份" />
    <input type="submit" name="type" class="btn primary" value="删除备份" /></form></div>';
}