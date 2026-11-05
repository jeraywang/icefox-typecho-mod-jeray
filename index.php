<?php
/**
 * 朋友圈icefox魔改主题
 * 欢迎体验使用
 * 示例站：<a href="https://hi.jeray.wang/onenote/" target="_blank">https://hi.jeray.wang/onenote/</a>
 *
 * @package icefox魔改版
 * @author <a href="https://hi.jeray.wang/" target="_blank">Jeray</a>
 * @version 1.0.0
 * @link https://hi.jeray.wang/
 */

if (!defined('__TYPECHO_ROOT_DIR__'))
    exit;
?>

<?php $this->need('/components/header.php'); ?>


<div class="bg-white dark:bg-[#323232] dark:text-[#cccccc] mx-auto main-container" :class="{'dark':darkMode}">
    <?php /* 2026-10-19：整站原先没有 h1，补一个仅供读屏器/搜索引擎识别的页面主标题
               （uno.css 未生成 sr-only，用内联样式做视觉隐藏） */ ?>
    <h1 style="position:absolute;width:1px;height:1px;margin:-1px;padding:0;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0;"><?php echo htmlspecialchars($this->options->title, ENT_QUOTES, 'UTF-8'); ?></h1>
    <?php $this->need('/components/option-header.php'); ?>

    <?php $this->need('/components/post-list-top.php'); ?>


    <?php // $this->need('/components/option-article-top.php');  ?>

    <div class="article-container">
        <?php $this->need('/components/post-list.php'); ?>
        <?php //while ($this->next()):  ?>
        <?php //$this->need('/components/option-article.php');  ?>
        <?php //endwhile;  ?>
    </div>
</div><!-- end #main-->

<?php $this->need('/components/modal.php'); ?>
<?php $this->need('/components/footer.php'); ?>