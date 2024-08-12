<div id="toggleLogDisplay">
    <a href="javascript:void(0);" id="viewLogDisplay"><?= \RightNow\Utils\Config::getMessage(VIEW_LOG_CMD) ?></a>
    <a href="javascript:void(0);" id="hideLogDisplay" class="hide"><?= \RightNow\Utils\Config::getMessage(HIDE_LOG_CMD) ?></a>
</div>
<div id="entireLog" class="hide">
    <pre class="scrollable"><?=$entireLog;?></pre>
</div>
