<?php

if (!defined('__TYPECHO_ROOT_DIR__'))
    exit;

?>

<?php
$agreeNum = getAgreeNumByCid($this->cid);
echo renderAgreeBox($this->cid, $agreeNum, 'border-b-solid');
?>