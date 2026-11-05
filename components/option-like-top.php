<?php

if (!defined('__TYPECHO_ROOT_DIR__'))
    exit;

?>

<?php
$agreeNum = getAgreeNumByCid($topArticle['cid']);
echo renderAgreeBox($topArticle['cid'], $agreeNum, 'border-b-solid');
?>