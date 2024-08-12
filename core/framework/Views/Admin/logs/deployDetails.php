<h2><?= \RightNow\Utils\Config::getMessage(DEPLOYMENT_LOGS_LBL) ?></h2>
<br>
<a href="<?=$backToLogUrl;?>">&lt;&lt;&nbsp;<?= \RightNow\Utils\Config::getMessage(BACK_TO_LOG_LISTING_CMD) ?></a>&nbsp;&nbsp;&nbsp;
<?= isset($viewDebugLink) ? $viewDebugLink : '';?>
<br>
<div>
    <?=$logContent;?>
</div>
<br>
