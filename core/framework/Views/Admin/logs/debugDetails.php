<h2><?=\RightNow\Utils\Config::getMessage(DEBUG_LOGS_LBL);?></h2>
<br />

<a href="/ci/admin/logs/debug">&lt;&lt;&nbsp;<?=\RightNow\Utils\Config::getMessage(BACK_TO_LOG_LISTING_CMD);?></a>
<br/>
<br/>

<h4><?=\RightNow\Utils\Config::getMessage(LOGS_FMT_TIMESTAMP_CALLING_LOC_LN_MSG);?></h4>

<p>
<?
if($logContent){
    echo $logContent;
}
else{
    echo \RightNow\Utils\Config::getMessage(NO_LOG_FILE_FOUND_LBL);
}
?>
</p>

<br/>