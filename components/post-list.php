<?php

/**
 * 文章列表渲染
 */
if (!defined('__TYPECHO_ROOT_DIR__'))
    exit;
$isSingle = false;
$lineClamp = 'line-clamp-4';

if ($this->is('single')) {
    $isSingle = true;
    $lineClamp = '';
}
$topCids = explode('||', $this->options->topPost);

$filterYear = isset($_GET['filter_year']) ? intval($_GET['filter_year']) : 0;
$filterMonth = isset($_GET['filter_month']) ? intval($_GET['filter_month']) : 0;

if ($filterYear <= 0 && $this->is('archive') && !empty($this->year)) {
    $filterYear = intval($this->year);
    if (!empty($this->month)) {
        $filterMonth = intval($this->month);
    }
}

$isDateFiltered = ($filterYear > 0);
$filteredData = null;

if ($isDateFiltered) {
    $currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : (isset($this->_currentPage) && $this->_currentPage > 1 ? $this->_currentPage : 1);
    $pageSize = 10;
    if (isset($this->parameter) && isset($this->parameter->pageSize) && $this->parameter->pageSize > 0) {
        $pageSize = $this->parameter->pageSize;
    }
    $filteredData = getFilteredPostsData($filterYear, $filterMonth, $currentPage, $pageSize);
    $postStack = $filteredData['posts'];
} else {
    $currentPage = $this->widget('Widget_Archive@index', 'type=month&format=F Y')->currentPage;
    $this->widget('Widget_Archive', 'type=post', 'page=' . $currentPage)->to($list);
    $postStack = $list->stack;
}
?>

<?php if ($isDateFiltered): ?>
    <style>
    .date-filter-bar {
        margin: 12px 16px 4px 16px !important;
        padding: 10px 14px !important;
        background: #f0f4f9 !important;
        border: 1px solid #d0dbee !important;
        border-radius: 10px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        font-size: 13px !important;
        color: #576b95 !important;
    }
    body.dark .date-filter-bar {
        background: #1c2330 !important;
        border-color: #2e3d54 !important;
        color: #8da4cc !important;
    }
    .date-filter-bar-btn {
        font-size: 12px !important;
        background: #ffffff !important;
        border: 1px solid #576b95 !important;
        color: #576b95 !important;
        padding: 4px 12px !important;
        border-radius: 6px !important;
        text-decoration: none !important;
        flex-shrink: 0 !important;
        margin-left: 8px !important;
        transition: all 0.2s !important;
    }
    body.dark .date-filter-bar-btn {
        background: #2a2a2a !important;
        border-color: #8da4cc !important;
        color: #8da4cc !important;
    }
    .date-filter-bar-btn:hover {
        background: #576b95 !important;
        color: #ffffff !important;
    }
    .date-filter-empty {
        text-align: center !important;
        padding: 48px 20px !important;
        color: #999 !important;
    }
    .date-filter-empty-btn {
        display: inline-block !important;
        margin-top: 12px !important;
        padding: 6px 16px !important;
        background: #576b95 !important;
        color: #ffffff !important;
        border-radius: 20px !important;
        text-decoration: none !important;
        font-size: 13px !important;
    }
    </style>
    <div class="date-filter-bar">
        <div style="display:flex;align-items:center;gap:8px;">
            <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#576b95;"></span>
            <span>
                当前查看：<strong style="color:inherit;"><?php echo $filterYear; ?>年<?php echo $filterMonth > 0 ? sprintf('%02d月', $filterMonth) : '全年'; ?></strong>（共 <?php echo $filteredData['total']; ?> 条动态）
            </span>
        </div>
        <a href="<?php $this->options->siteUrl(); ?>" class="date-filter-bar-btn">✕ 全部动态</a>
    </div>
    <?php if (empty($postStack)): ?>
        <div class="date-filter-empty">
            <p style="margin:0;font-size:14px;">该时间段内暂无发表的朋友圈动态</p>
            <a href="<?php $this->options->siteUrl(); ?>" class="date-filter-empty-btn">返回全部动态</a>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php foreach ($postStack as $itemArr) : ?>
    <?php
    $item = (object) $itemArr;
    // Typecho 1.3 起列表行不再带 permalink，缺失时自行生成（1.2 有值则直接沿用）
    if (empty($item->permalink)) {
        $item->permalink = icefoxPostPermalink($itemArr);
    }
    // 非筛选模式下排除置顶文章（置顶文章在 post-list-top 单独渲染）
    if (!$isDateFiltered && in_array($item->cid, $topCids)) {
        continue;
    }
    ?>
    <article class="flex flex-row border-b border-b-2 dark:border-gray-800 border-gray-200 pt-5 pl-5 pr-5">
        <div class="mr-3">
            <div class="w-9 h-9">
                <?php
                // 2026-11-05：恢复原版后台控制 ——「文章侧用户头像」填写则用之；未填写回退头像源 getUserAvatar（不再锁定本地 avatar-author.webp）
                $authorId = $item->authorId;
                $archiveUserAvatarUrl = $this->options->archiveUserAvatarUrl;
                $authorAvatarUrl = !empty($archiveUserAvatarUrl) ? $archiveUserAvatarUrl : getUserAvatar($authorId);
                ?>
                <img src="<?php echo $authorAvatarUrl; ?>" width="36" height="36" alt="" loading="lazy" decoding="async" class="w-9 h-9 object-cover rounded-lg preview-image" />

            </div>
        </div>
        <div class="w-full border-t-0 border-l-0 border-r-0 border-b-1 dark:border-gray-800 border-gray-100 border-solid pb-1">
            <section class="flex flex-row justify-between items-center mb-1">
                <span class="text-color-link cursor-default text-[14px]">
                    <a href="<?php echo $item->permalink; ?>" class="cursor-pointer text-color-link no-underline"><?php print_r(_getUserScreenNameByCid($item->cid)['screenName']); ?></a>
                </span>
                <?php
                $advertiseData = getArticleFieldsByCid($item->cid, 'isAdvertise');
                if (count($advertiseData) > 0) {
                    $isAdvertise = $advertiseData[0]['str_value'];
                    if ($isAdvertise == true) {
                ?>
                        <span class="badge-pill badge-pill--ad">广告</span>
                <?php }
                } ?>
            </section>
            <section class="mb-1 cursor-default text-[14px] article-content break-all <?php echo $lineClamp; ?> content-<?php echo $item->cid; ?>" data-cid="<?php echo $item->cid; ?>">
                <?php
                $clearContent = preg_replace('/<img[^>]+>/i', '', $this::markdown($item->text));
                $clearContent = preg_replace('/<br><br>/i', '', $clearContent);
                echo $this::markdown($clearContent);

                // 图片替代文本（2026-10-19）：用正文前 40 字兜底，供读屏器与图片搜索使用
                $altText = trim(preg_replace('/\s+/u', ' ', strip_tags($clearContent)));
                $altText = mb_substr($altText, 0, 40, 'UTF-8');
                if ($altText === '') {
                    $altText = '动态配图';
                }
                $altAttr = ' alt="' . htmlspecialchars($altText, ENT_QUOTES, 'UTF-8') . '"';
                ?>
            </section>
            <?php
            if (!$isSingle) {
            ?>
                <div class="text-[14px] text-color-link cursor-pointer qw qw-<?php echo $item->cid; ?> hidden mb-1" data-cid="<?php echo $item->cid; ?>">全文
                </div>
                <div class="text-[14px] text-color-link cursor-pointer ss ss-<?php echo $item->cid; ?> hidden mb-1" data-cid="<?php echo $item->cid; ?>">收起
                </div>
            <?php
            }
            ?>

            <?php
            $music = getArticleFieldsByCid($item->cid, 'music');
            if (count($music) > 0 && !empty($music[0]['str_value'])) {
                $music = $music[0]['str_value'];
                $musicArr = explode('||', $music);
            ?>
                <section class="w-full mb-1">
                    <figure class="flex overflow-hidden rounded-sm music-card m-0 bg-cover bg-center " style="background-color:#A2A3A1;">
                        <div class="w-full h-full bg-cover bg-center backdrop-blur-lg backdrop-filter bg-opacity-50 flex flex-row relative">
                            <img src="<?php echo $musicArr[3]; ?>" class="h-full w-auto aspect-square object-cover music-img" id="music-img-<?php echo $item->cid; ?>" />
                            <div class="flex flex-col text-white h-full justify-center pl-[5px]">
                                <span class="mt-1 truncate music-card-text">
                                    <?php echo $musicArr[0]; ?>
                                </span>
                                <span class="mt-1 truncate music-card-text">
                                    <?php echo $musicArr[1]; ?>
                                </span>
                            </div>
                            <div class="music-card-play-position">
                                <img width="36" height="36" src="<?php $this->options->themeUrl('assets/svgs/music-play-light.svg'); ?>" @click="playAudio(<?php echo $item->cid; ?>, '<?php echo $musicArr[2]; ?>', '<?php echo $musicArr[3]; ?>')" id="music-play-<?php echo $item->cid; ?>" class="music-play" />
                                <img width="36" height="36" src="<?php $this->options->themeUrl('assets/svgs/music-pause-light.svg'); ?>" class="music-pause hidden" @click="pauseAudio(<?php echo $item->cid; ?>)" id="music-pause-<?php echo $item->cid; ?>" />
                            </div>
                        </div>
                    </figure>
                </section>
            <?php
            }
            ?>

            <?php
            $friend_video = getArticleFieldsByCid($item->cid, 'friend_video');
            if (count($friend_video) > 0 && !empty($friend_video[0]['str_value'])) {
                $friendVideo = $friend_video[0]['str_value'];
                // 2026-11-05：恢复后台控制 —— 是否默认播放视频(autoPlayVideo) / 是否默认静音播放视频(autoMutedPlayVideo)
                $videoAutoplay = ($this->options->autoPlayVideo == 'yes') ? 'autoplay' : '';
                $videoMuted = ($this->options->autoMutedPlayVideo == 'yes') ? 'muted' : '';
                if (!empty($friendVideo)) {
            ?>
                    <section class="grid grid-cols-12 gap-1 multi-pictures overflow-hidden mb-3" id="preview-<?php echo $item->cid; ?>">
                        <div class="video-shell overflow-hidden rounded-lg w-full col-span-10">
                            <video data-src="<?php echo $friendVideo ?>" <?php echo $videoAutoplay; ?> <?php echo $videoMuted; ?> playsinline webkit-playsinline loop preload="auto" controls="controls" class="w-full js-player" data-cid="<?php echo $item->cid; ?>" data-play="" style="--plyr-color-main:#dcdfe5;" id="v-<?php echo $item->cid; ?>">您的浏览器不支持video标签
                            </video>
                        </div>
                    </section>
                <?php
                }
            } else {
                $contentPictures = getAllImages($this::markdown($item->text));
                $friendPicture = getArticleFieldsByCid($item->cid, 'friend_pictures');

                if (count($friendPicture) > 0) {
                    foreach ($friendPicture as $tmpFriendPic) {
                        $onePic = $tmpFriendPic['str_value'];

                        $friendPictures = explode(',', $onePic);
                        foreach ($friendPictures as $friendPic) {
                            array_push($contentPictures, $friendPic);
                        }
                    }
                }

                $picture_list = array_slice(array_values(array_unique(array_filter($contentPictures))), 0, 9);

                if (count($picture_list) == 1) {
                    $exten = pathinfo(parse_url($picture_list[0], PHP_URL_PATH) ?: $picture_list[0], PATHINFO_EXTENSION);
                    if ($exten) {
                ?>
                    <section class="grid grid-cols-3 gap-1 multi-pictures overflow-hidden mb-3" id="preview-<?php echo $item->cid; ?>">
                        <?php /* 2026-10-19：单图改为固定 4:3 预览框（object-cover 顶部对齐）
                                 1. 手机竖长截图（如 353×1920）不再被压成"细长条"，改为顶部裁切预览，点击看全图；
                                 2. 用 aspect-ratio 预留空间，消除懒加载引起的布局抖动（CLS）。 */ ?>
                        <div class="overflow-hidden cursor-zoom-in col-span-2 rounded-md" style="aspect-ratio: 4 / 3;">
                            <img data-src="<?php echo $picture_list[0] ?>" data-fancybox="<?php echo $item->cid; ?>" class="cursor-zoom-in preview-image w-full h-full object-cover object-top" data-cid="<?php echo $item->cid; ?>" <?php echo $altAttr; ?> />
                        </div>
                    </section>
                <?php
                    }
                } else if (count($picture_list) == 4) {
                ?>
                    <section class="grid grid-cols-3 gap-1 multi-pictures overflow-hidden mb-3" id="preview-<?php echo $item->cid; ?>">
                        <div class="col-span-2 grid grid-cols-2 gap-1">
                            <?php
                            foreach ($picture_list as $picture) :
                            ?>
                                <div class="overflow-hidden cursor-zoom-in w-full h-0 pt-[100%] relative">
                                    <img data-src="<?php echo $picture ?>" data-fancybox="<?php echo $item->cid; ?>" class="w-full h-full object-cover absolute top-0 left-0 cursor-zoom-in preview-image" data-cid="<?php echo $item->cid; ?>" <?php echo $altAttr; ?> />
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php
                } else if (count($picture_list) > 0) {
                ?>
                    <section class="grid grid-cols-3 gap-1 multi-pictures overflow-hidden mb-3" id="preview-<?php echo $item->cid; ?>">
                        <?php
                        foreach ($picture_list as $picture) {
                            $picExt = strtolower(pathinfo(parse_url($picture, PHP_URL_PATH) ?: $picture, PATHINFO_EXTENSION));
                            if ($picExt) {
                        ?>
                            <div class="overflow-hidden cursor-zoom-in w-full h-0 pt-[100%] relative">
                                <img data-src="<?php echo $picture ?>" data-fancybox="<?php echo $item->cid; ?>" class="w-full h-full object-cover absolute top-0 left-0 cursor-zoom-in preview-image" data-cid="<?php echo $item->cid; ?>" <?php echo $altAttr; ?> />
                            </div>
                        <?php
                            }
                        }
                        ?>
                    </section>
            <?php
                }
            }
            ?>

            <!--定位-->
            <section class="mb-1">
                <?php
                $position = getArticleFieldsByCid($item->cid, 'position');
                $positionUrl = getArticleFieldsByCid($item->cid, 'positionUrl');
                if (!empty($position)) {
                    if (count($positionUrl) > 0) {
                        $strValue = $positionUrl[0]['str_value'];
                        if (!empty($strValue)) {
                ?>
                            <a href="<?php echo $strValue; ?>" class=" text-color-link text-xs cursor-pointer no-underline">
                                <img src="<?php $this->options->themeUrl('assets/svgs/position-link.svg'); ?>" class="w-[12px] h-[12px] text-color-link" />
                                <?php echo $position[0]['str_value'] ?>
                            </a>
                        <?php
                        } else {
                        ?>
                            <span class="text-color-link text-xs cursor-default">
                                <?php echo $position[0]['str_value'] ?>
                            </span>
                <?php
                        }
                    }else{
                        ?>
                            <span class="text-color-link text-xs cursor-default">
                                <?php echo $position[0]['str_value'] ?>
                            </span>
                <?php
                    }
                }
                ?>

            </section>
            <!--时间-->
            <section class="flex flex-row justify-between mb-1">
                <?php /* 2026-10-19：改用 <time> 并给出绝对时间（悬停可见），
                         搜索引擎与读屏器都能拿到发布时间，相对时间"3天前"不再是唯一信息 */ ?>
                <time class="text-gray text-xs" datetime="<?php echo date('c', (int) $item->created); ?>"
                    title="<?php echo date('Y-m-d H:i', (int) $item->created); ?>">
                    <?php echo getTimeFormatStr($item->created); ?>
                </time>
                <div class="w-[30px] h-[20px] relative">
                    <div class="hudong dark:bg-[#262626] rounded-sm"></div>
                    <div class="hudong-modal animate-slide-in absolute right-10 top-[-10px] hidden">
                        <div class="bg-[#4c4c4c] text-[#fff] hudong-container pt-2 pb-2 pl-5 pr-5 flex flex-row items-center justify-between">

                            <?php
                            $isAgree = isAgreeByCid($item->cid);
                            ?>
                            <a href="javascript:;" class="cursor-pointer text-[#fff] no-underline  items-center text-[14px] like-to <?php echo $isAgree ? 'flex' : 'hidden'; ?> like-to-cancel-<?php echo $item->cid; ?>" data-cid="<?php echo $item->cid; ?>" data-agree="0"><span class="hudong-liked inline-block mr-2 cursor-pointer" data-cid="<?php echo $item->cid; ?>" data-agree="0"></span>取消</a>

                            <a href="javascript:;" class="cursor-pointer text-[#fff] no-underline  items-center text-[14px] like-to <?php echo $isAgree ? 'hidden' : 'flex'; ?> like-to-show-<?php echo $item->cid; ?>" data-cid="<?php echo $item->cid; ?>" data-agree="1"><span class="hudong-like inline-block mr-2 cursor-pointer" data-cid="<?php echo $item->cid; ?>" data-agree="1"></span>赞</a>

                            <span class="bg-[#454545] h-[22px] w-[1px]"></span>
                            <a href="javascript:;" class="cursor-pointer text-[#fff] no-underline flex items-center text-[14px] comment-to" data-cid="<?php echo $item->cid; ?>"><span class="hudong-comment inline-block mr-2 cursor-pointer comment-to" data-cid="<?php echo $item->cid; ?>"></span>评论</a>
                        </div>
                    </div>
                </div>
            </section>
            <!--评论列表-->
            <section class="break-all mb-1 overflow-hidden rounded-md">
                <?php

                $commentCount = 5;
                if ($this->is('single')) {
                    $commentCount = 999;
                }

                $count = getCommentCountByCid($item->cid);
                $comments = getCommentByCid($item->cid, 0, $commentCount);
                $all = getChildCommentByCid($item->cid, 999);
                $dzClass = "";
                if ($count > 0) {
                    $dzClass = "border-b-solid";
                }
                ?>
                <?php
                $agreeNum = getAgreeNumByCid($item->cid);
                echo renderAgreeBox($item->cid, $agreeNum, $dzClass);
                ?>

                <div class="index-comments bottom-shadow bg-[#f7f7f7] dark:bg-[#262626] ">
                    <ul class="list-none p-0 m-0 comment-ul-cid-<?php echo $item->cid; ?> comment-ul">
                        <?php
                        if ($comments) {
                            foreach ($comments as $comment) : ?>
                                <li class="pos-rlt comment-li-coid-<?php echo $comment['coid'] ?> pb-1 px-2 first-of-type:pt-2">
                                    <div class="comment-body">
                                        <span class="text-[14px] text-color-link">
                                            <a href="<?php echo $comment['url'] ?>" target="_blank" class="cursor-pointer text-color-link no-underline">
                                                <?php echo $comment['author']; ?>
                                                <?php
                                                if ($comment['authorId'] == $item->authorId) {
                                                ?>
                                                    <span class="text-xs badge-author">作者</span>
                                                <?php
                                                }
                                                ?>
                                            </a>
                                        </span>
                                        <span data-separator=":" class="before:content-[attr(data-separator)] text-[14px] cursor-help comment-to" data-coid="<?php echo $comment['coid'] ?>" data-cid="<?php echo $comment['cid'] ?>" data-name="<?php echo $comment['author'] ?>">
                                            <?php echo strip_tags(preg_replace("/<br>|<p>|<\/p>/", ' ', $comment['text'])) ?>
                                        </span>
                                    </div>
                                </li>

                                <?php
                                $childComments = getChildCommentByCidOfComplete($comment['coid'], $all);
                                if ($childComments) {
                                    foreach ($childComments as $childComment) : ?>

                                        <li class="pos-rlt comment-li-coid-<?php echo $childComment['coid'] ?> pb-1 px-2">
                                            <div class="comment-body">
                                                <span class="text-[14px] text-color-link">
                                                    <a href="<?php echo $childComment['url'] ?>" target="_blank" class="cursor-pointer text-color-link no-underline">
                                                        <?php echo $childComment['author'] ?>
                                                        <?php
                                                        if ($childComment['authorId'] == $item->authorId) {
                                                        ?>
                                                            <span class="text-xs badge-author">作者</span>
                                                        <?php
                                                        }
                                                        ?>
                                                    </a>
                                                </span>
                                                <span class="text-[14px]">回复</span>
                                                <span class="text-[14px] text-color-link">
                                                    <?php echo $childComment['toAuthor'] ?>
                                                    <?php
                                                    if ($childComment['toAuthorId'] == $item->authorId) {
                                                    ?>
                                                        <span class="text-xs badge-author">作者</span>
                                                    <?php
                                                    }
                                                    ?>
                                                </span>
                                                <span data-separator=":" class="before:content-[attr(data-separator)] text-[14px] cursor-help comment-to" data-coid="<?php echo $childComment['coid'] ?>" data-cid="<?php echo $childComment['cid'] ?>" data-name="<?php echo $childComment['author'] ?>">
                                                    <?php echo strip_tags(preg_replace("/<br>|<p>|<\/p>/", ' ', $childComment['text'])) ?>
                                                </span>

                                            </div>
                                        </li>
                                <?php endforeach;
                                }
                                ?>

                                <?php ?>
                            <?php endforeach; ?>
                            <?php
                            if ($count > 5 & $commentCount == 5) {
                            ?>
                                <li class="px-2 pb-1">
                                    <a href="<?php echo $item->permalink; ?>" class="cursor-pointer text-color-link no-underline text-[13px]">查看更多…</a>
                                </li>

                        <?php
                            }
                        }
                        ?>
                    </ul>
                </div>
            </section>
        </div>
    </article>
<?php
endforeach;
?>