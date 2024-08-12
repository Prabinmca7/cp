<strong><?=$pageSetLabel;?></strong>
<? if ($pageSetData): ?>
<div id="pageSetContent"><div class="wait"></div></div>
<script>
/*Caution: do not change or remove the following variable; it's referenced in deploy.php...*/
var cellFormatter,
    PageSet = {
        columns:  [<?= $columnDefinitions ?>],
        pagesets: [<?= $pageSetData ?>],
        summary: "<?= \RightNow\Utils\Config::getMessageJS(PG_ST_MAPPING_DIFFERENCES_MSG) ?>"
    };
</script>
<? else: ?>
<div class="info"><?= \RightNow\Utils\Config::getMessage(PAGE_SET_DIFFERENCES_DISPLAY_MSG) ?></div>
<? endif; ?>