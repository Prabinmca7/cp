<?php
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<?
require DOCROOT.'/ma/cci/head.phph';
if ($this->isMobile)
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
?>
<title>
<?=getMessage(SEND_FRIEND_RES_HDG)?>
</title>
</head>
<body class="bgcolor">
<?require DOCROOT.'/ma/cci/top.phph'?>
<?if($previewMode):?>
    <span class="text"><?=getMessage(PREV_MODE_NO_EMAILS_SENT_MSG)?></span>
<?else:?>
    <span class="text"><?=getMessage(MSG_SENT_COLLEGUES_MSG)?></span>
<?endif?>

<?require DOCROOT.'/ma/cci/bottom.phph'?>
</body>
</html>
