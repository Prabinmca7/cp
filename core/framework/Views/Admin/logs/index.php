<h2><?=\RightNow\Utils\Config::getMessage(LOGS_OVERVIEW_LBL);?></h2>

<h3><a href="/ci/admin/logs/webdav"><?=\RightNow\Utils\Config::getMessage(WEBDAV_LOGS_LBL);?></a></h3>
<p>
<?=\RightNow\Utils\Config::getMessage(WEBDAV_LOGS_TRANS_HIST_CHG_SITE_MSG);?>
</p>

<h3><a href="/ci/admin/logs/debug"><?=\RightNow\Utils\Config::getMessage(DEBUG_LOGS_LBL);?></a></h3>
<p>
<?=sprintf(\RightNow\Utils\Config::getMessage(DEBUG_LOGS_DEVELOPER_GENERATED_LOG_MSG), 'logMessage()');?>
</p>

<h3><a href="/ci/admin/logs/deploy"><?=\RightNow\Utils\Config::getMessage(DEPLOYMENT_LOGS_LBL);?></a></h3>
<p>
<?=\RightNow\Utils\Config::getMessage(DEPLOY_LOGS_HIST_ACTIONS_PERFORMED_MSG);?>
</p>
