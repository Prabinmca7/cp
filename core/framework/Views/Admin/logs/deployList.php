<h2><?=\RightNow\Utils\Config::getMessage(DEPLOYMENT_LOGS_LBL);?></h2>
<br/>
<div><?=$searchControls;?></div>
<br/>
<div id="deployLogs"></div>
<script>window.logData = <?= json_encode($table); ?></script>
