<?php
/**
 * 归档页（按年份/按月份归档朋友圈列表）
 *
 * @package icefox
 */

if (!defined('__TYPECHO_ROOT_DIR__'))
    exit;
?>

<?php $this->need('/components/header.php'); ?>

<div class="bg-white dark:bg-[#323232] dark:text-[#cccccc] mx-auto main-container" :class="{'dark':darkMode}">
    <?php $this->need('/components/option-header.php'); ?>

    <?php $this->need('/components/post-list-top.php'); ?>

    <div class="article-container">
        <?php $this->need('/components/post-list.php'); ?>
    </div>
</div><!-- end #main-->

<?php $this->need('/components/modal.php'); ?>
<?php $this->need('/components/footer.php'); ?>
