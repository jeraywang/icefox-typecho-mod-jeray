<?php

/**
 * 根据文章id获取最新文章列表
 * @throws Exception
 */
function getArticleByCid($cid): array
{
    $db = Typecho_Db::get();
    $select = $db->select('table.contents.*,table.users.screenName')
        ->from('table.contents')
        ->join('table.users', 'table.contents.authorId = table.users.uid')
        ->where('cid = ?', $cid);
    return $db->fetchAll($select);
}

/**
 * 根据文章Id获取文章作者名称
 */
function _getUserScreenNameByCid($cid){
	$db = Typecho_Db::get();
    $select = $db->select('table.users.screenName')
        ->from('table.contents')
        ->join('table.users', 'table.contents.authorId = table.users.uid')
        ->where('cid = ?', $cid)
        ->limit(1);
    return $db->fetchRow($select);
}

/**
 * 根据文章id获取文章字段
 */
function getArticleFieldsByCid($cid, $name)
{
    $db = Typecho_Db::get();
    $select = $db->select('*')
        ->from('table.fields')
        ->where('cid = ?', $cid)
        ->where('name = ?', $name);
    return $db->fetchAll($select);
}
/**
 * 时间格式化
 */
function getTimeFormatStr($time)
{
    $time = (int) substr($time, 0, 10);
    $int = time() - $time;

    /* 2026-10-25：改为按日历天显示——
       当天：相对时间（刚刚/X秒前/X分钟前/X小时前）
       昨天：显示"昨天"（跨年 0 点前后也正确归类）
       今年更早：X月X日；非当年：X年X月X日 */
    $str = '';
    $todayStart = strtotime(date('Y-m-d'));       // 今天 0 点
    if ($time >= $todayStart) {
        if ($int <= 2) {
            $str = '刚刚';
        } elseif ($int < 60) {
            $str = sprintf('%d秒前', $int);
        } elseif ($int < 3600) {
            $str = sprintf('%d分钟前', floor($int / 60));
        } else {
            $str = sprintf('%d小时前', floor($int / 3600));
        }
    } elseif ($time >= $todayStart - 86400) {
        $str = '昨天';
    } elseif (date('Y', $time) === date('Y')) {
        $str = date('n月j日', $time);
    } else {
        $str = date('Y年n月j日', $time);
    }
    return $str;
}

/**
 * 获取站点所有已发布文章的时间归档树（按年、月统计文章数量）
 * @return array
 */
function getArticleArchiveDates(): array
{
    static $archiveTree = null;
    if ($archiveTree !== null) {
        return $archiveTree;
    }

    $db = Typecho_Db::get();
    $rows = $db->fetchAll(
        $db->select('created')
            ->from('table.contents')
            ->where('type = ?', 'post')
            ->where('status = ?', 'publish')
            ->order('created', Typecho_Db::SORT_DESC)
    );

    $tree = [];
    foreach ($rows as $row) {
        $year = date('Y', $row['created']);
        $month = date('m', $row['created']);

        if (!isset($tree[$year])) {
            $tree[$year] = [
                'year' => $year,
                'total' => 0,
                'months' => []
            ];
        }

        $tree[$year]['total']++;

        if (!isset($tree[$year]['months'][$month])) {
            $tree[$year]['months'][$month] = 0;
        }
        $tree[$year]['months'][$month]++;
    }

    $archiveTree = $tree;
    return $archiveTree;
}

/**
 * 根据指定年份和月份，获取筛选后的文章列表及分页统计信息
 *
 * @param int $year 年份，如 2025
 * @param int $month 月份，如 8 或 08，若为 0 则代表全年
 * @param int $page 当前页码
 * @param int $pageSize 每页条数
 * @return array
 */
function getFilteredPostsData(int $year, int $month = 0, int $page = 1, int $pageSize = 10): array
{
    $db = Typecho_Db::get();

    if ($month > 0) {
        $startTime = mktime(0, 0, 0, $month, 1, $year);
        $daysInMonth = (int)date('t', $startTime);
        $endTime = mktime(23, 59, 59, $month, $daysInMonth, $year);
    } else {
        $startTime = mktime(0, 0, 0, 1, 1, $year);
        $endTime = mktime(23, 59, 59, 12, 31, $year);
    }

    // 统计总数
    $countSelect = $db->select('COUNT(cid) AS total')
        ->from('table.contents')
        ->where('type = ?', 'post')
        ->where('status = ?', 'publish')
        ->where('created >= ?', $startTime)
        ->where('created <= ?', $endTime);
    $totalRow = $db->fetchRow($countSelect);
    $total = isset($totalRow['total']) ? (int)$totalRow['total'] : 0;

    $totalPage = $pageSize > 0 ? (int)ceil($total / $pageSize) : 1;
    $page = max(1, min($page, max(1, $totalPage)));
    $offset = ($page - 1) * $pageSize;

    // 查询当前页文章
    $listSelect = $db->select('*')
        ->from('table.contents')
        ->where('type = ?', 'post')
        ->where('status = ?', 'publish')
        ->where('created >= ?', $startTime)
        ->where('created <= ?', $endTime)
        ->order('created', Typecho_Db::SORT_DESC)
        ->offset($offset)
        ->limit($pageSize);
    $rawPosts = $db->fetchAll($listSelect);

    // 格式化为与 Typecho Archive stack 一致的对象结构
    $postList = [];
    $options = Helper::options();
    foreach ($rawPosts as $raw) {
        $raw['permalink'] = Typecho_Router::url('post', $raw, $options->index);
        $postList[] = (object)$raw;
    }

    return [
        'posts' => $postList,
        'total' => $total,
        'totalPage' => $totalPage,
        'currentPage' => $page,
        'year' => $year,
        'month' => $month,
        'startTime' => $startTime,
        'endTime' => $endTime
    ];
}
?>