<?php

/**
 * 根据文章id获取最新评论列表
 */
function getCommentByCid($cid, $parent = 0, $len = 5): array
{
    $db = Typecho_Db::get();
    $select = $db->select('coid,author,authorId,ownerId,mail,text,created,parent,url,cid')
        ->from('table.comments')
        ->where('cid = ?', $cid)
        ->where('parent = ?', $parent)
        ->where('type = ?', 'comment')
        ->where('status = ?', 'approved')
        ->order('created', Typecho_Db::SORT_DESC)
        ->limit($len);
    return $db->fetchAll($select);
}
/**
 * 根据文章id获取最新子评论列表
 */
function getChildCommentByCid($cid, $len = 5): array
{
    $db = Typecho_Db::get();
    $select = $db->select('c1.coid,c1.author,c1.authorId,c1.ownerId,c1.mail,c1.text,c1.created,c1.parent,c1.url,c1.cid,c2.author as toAuthor,c2.authorId as toAuthorId')
        ->from('table.comments as c1')
        ->join('table.comments as c2', 'c1.parent = c2.coid', Typecho_Db::LEFT_JOIN)
        ->where('c1.cid = ?', $cid)
        ->where('c1.type = ?', 'comment')
        ->where('c1.status = ?', 'approved')
        ->order('c1.created', Typecho_Db::SORT_ASC)
        ->limit($len);
    return $db->fetchAll($select);
}

/**
 * 获取下级评论
 */
function getChildCommentByCidOfComplete($parent, $list)
{
    $result = [];
    $newList = array_filter($list, function ($li) use ($parent) {
        return $li['parent'] == $parent;
    });
    foreach ($newList as $item) {
        array_push($result, $item);

        $result = array_merge($result, getChildCommentByCidOfComplete($item['coid'], $list));
    }
    return $result;
}

/**
 * 获取评论列表（包含子级）
 */
function getChildComments($coid, $list)
{
    $result = [];
    $newList = array_filter($list, function ($li) use ($coid) {
        return $li['parent'] == $coid;
    });

    foreach ($newList as $item) {
        array_push($result, $item);
        $childs = getChildCommentByCidOfComplete($item['coid'], $list);

        foreach ($childs as $child) {
            array_push($result, $child);
        }
    }
    return $result;
}

/**
 * 根据文章id获取评论数量
 */
function getCommentCountByCid($cid)
{

    $db = Typecho_Db::get();
    $prefix = $db->getPrefix();

    $list = $db->fetchAll($db->select('cid')->from('table.comments')->where('cid = ?', $cid)->where('status = ?', 'approved'));
    $count = count($list);

    return $count;
}

/**
 * 解析点赞用户列表 JSON
 */
function parseAgreeUsers($json)
{
    if (empty($json)) {
        return [];
    }
    if (is_array($json)) {
        $data = $json;
    } else {
        $data = json_decode($json, true);
    }
    if (!is_array($data)) {
        return [];
    }
    $names = [];
    foreach ($data as $item) {
        if (is_array($item)) {
            $name = trim(strval($item['name'] ?? ''));
        } else {
            $name = trim(strval($item));
        }
        if ($name !== '' && !in_array($name, $names, true)) {
            $names[] = $name;
        }
    }
    return $names;
}

/**
 * 渲染点赞内容内部 HTML
 */
function renderAgreeContentHtml($cid, $agree, $users = [])
{
    $agree = intval($agree);
    if (!empty($users) && is_array($users)) {
        $validUsers = [];
        foreach ($users as $u) {
            $name = trim(strval($u));
            if ($name !== '' && !in_array($name, $validUsers, true)) {
                $validUsers[] = $name;
            }
        }
        $userCount = count($validUsers);

        if ($userCount === 1 && $agree <= 1) {
            // 场景 1：仅 1 个有昵称的用户点赞（不带逗号：某某  点赞）
            $safeName = htmlspecialchars($validUsers[0], ENT_QUOTES, 'UTF-8');
            return '<span class="like-user cursor-pointer font-medium text-color-link">' . $safeName . '</span><span class="text-gray-600 dark:text-gray-400">  点赞</span>';
        } else if ($userCount > 0) {
            // 场景 2：多个有昵称的用户点赞（控制最多显示 6 人，避免排版过多难看）
            $maxShow = 6;
            $showUsers = array_slice($validUsers, 0, $maxShow);

            $userSpans = [];
            foreach ($showUsers as $u) {
                $safeName = htmlspecialchars($u, ENT_QUOTES, 'UTF-8');
                $userSpans[] = '<span class="like-user cursor-pointer font-medium text-color-link">' . $safeName . '</span>';
            }
            $namesStr = implode('、 ', $userSpans);
            if ($userCount > $maxShow) {
                $namesStr .= '<span class="text-gray-500 dark:text-gray-400">等</span>';
            }
            // 格式：例如（藏蓝色、 tod、 1537691  共X个点赞），不带逗号，用空格分隔
            return $namesStr . '<span class="text-gray-600 dark:text-gray-400">  共<span class="fk-cid-' . $cid . '">' . $agree . '</span>个点赞</span>';
        }
    }
    // 场景 3：纯匿名访客点赞（无昵称）
    return '<span class="text-color-link"><span class="fk-cid-' . $cid . '">' . $agree . '</span> 位访客</span>';
}

/**
 * 渲染点赞外层展示容器
 */
function renderAgreeBox($cid, $agreeNum, $dzClass = '')
{
    $agree = intval($agreeNum['agree'] ?? 0);
    $users = is_array($agreeNum['agree_users'] ?? null) ? $agreeNum['agree_users'] : [];
    $isFlex = $agree > 0 ? 'flex' : 'hidden';
    $contentHtml = renderAgreeContentHtml($cid, $agree, $users);

    return <<<HTML
<div class="bg-[#f7f7f7] dark:bg-[#262626] px-3 py-2 bottom-shadow items-start border-1 {$dzClass} dark:border-gray-800 border-gray-100 {$isFlex} like-agree-{$cid}">
    <span class="like inline-block mr-2 flex-shrink-0 mt-0.5"></span>
    <div class="text-[14px] leading-relaxed flex-1 break-words like-agree-content-{$cid}">
        {$contentHtml}
    </div>
</div>
HTML;
}

/**
 * 获取点赞
 */
function getAgreeNumByCid($cid)
{
    $db = Typecho_Db::get();
    $prefix = $db->getPrefix();

    $checkRow = $db->fetchRow($db->select()->from('table.contents')->where('cid = ?', $cid)->limit(1));
    if (!$checkRow) {
        $checkRow = $db->fetchRow($db->select()->from('table.contents')->limit(1));
    }
    if ($checkRow && !array_key_exists('agree', $checkRow)) {
        try {
            $db->query('ALTER TABLE `' . $prefix . 'contents` ADD `agree` INT(10) NOT NULL DEFAULT 0;');
        } catch (Exception $e) {}
    }
    if ($checkRow && !array_key_exists('agree_users', $checkRow)) {
        try {
            $db->query('ALTER TABLE `' . $prefix . 'contents` ADD `agree_users` TEXT;');
        } catch (Exception $e) {}
    }

    $agreeRow = $db->fetchRow($db->select('agree', 'agree_users')->from('table.contents')->where('cid = ?', $cid));
    $agreeCount = intval($agreeRow['agree'] ?? 0);
    $agreeUsers = parseAgreeUsers($agreeRow['agree_users'] ?? '');

    $isRecording = false;
    try {
        $agreeRecordingCookie = Typecho_Cookie::get('typechoAgreeRecording');
        if ($agreeRecordingCookie !== null) {
            $arrayCookie = json_decode($agreeRecordingCookie, true);
            if (is_array($arrayCookie) && in_array($cid, $arrayCookie)) {
                $isRecording = true;
            }
        }
    } catch (Exception $exception) {}

    return array(
        'agree' => $agreeCount,
        'agree_users' => $agreeUsers,
        'recording' => $isRecording
    );
}

/**
 * 是否点过赞
 */
function isAgreeByCid($cid)
{
    //  获取记录点赞的 Cookie
    $agreeRecording = Typecho_Cookie::get('typechoAgreeRecording');
    //  判断记录点赞的 Cookie 是否存在
    if (empty($agreeRecording)) {
        //  如果不存在就写入 Cookie
        return false;
    }
    //  把 Cookie 的 JSON 字符串转换为 PHP 对象
    $agreeRecordingDecode = json_decode($agreeRecording);
    //  判断文章是否点赞过
    if (in_array($cid, $agreeRecordingDecode)) {
        //  如果当前文章的 cid 在 cookie 中就返回文章的赞数，不再往下执行
        return true;
    }
    return false;
}
