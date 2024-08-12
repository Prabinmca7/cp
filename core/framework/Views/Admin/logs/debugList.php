<h2><?=\RightNow\Utils\Config::getMessage(DEBUG_LOGS_LBL);?></h2>
<br/>

<div id="debugLogs"></div>
<br/>
<div id="logControls">
    <?if(count($table['data'])):?>
        <a href="/ci/admin/logs/viewDebugLog"><?=\RightNow\Utils\Config::getMessage(VIEW_LATEST_LOG_CMD);?></a><br/>
        <a href="/ci/admin/logs/deleteAllDebugLogs"><?=\RightNow\Utils\Config::getMessage(DELETE_ALL_LOGS_CMD);?></a>
    <?endif;?>
</div>

<script>window.logData = <?= json_encode($table); ?></script>
