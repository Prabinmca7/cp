<strong><?=$filesLabel;?></strong>
<? if ($data): ?>
<div id="selectedFilesContent"><div class="wait"></div></div>
<script>
<? /* GLOBAL VARIABLES REFERENCED IN filesTable.js */ ?>
var FilesTable = {
    'data':    [<?= $data ?>],
    'columns': [<?= $columns ?>],
    'summary': "<?= \RightNow\Utils\Config::getMessageJS(FILES_DEVELOPMENT_STAGING_MSG) ?>"
};
</script>
<? else: ?>
<div class="info"><?= \RightNow\Utils\Config::getMessage(FILE_DIFFERENCES_DISPLAY_MSG) ?></div>
<? endif; ?>
