<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <? if ((IS_DEVELOPMENT || IS_OKCS_REFERENCE) && (\RightNow\Utils\Url::getParameter('logApiTimings') === 'true')): ?>
    <div class="rn_Container rn_KAPerformanceContainer">
    <? $apiTiminig = \RightNow\Utils\Okcs::getCachedTimings('timingCacheKey'); ?>
    <? if (count($apiTiminig) > 0): ?>
        <div class="rn_KAPerformanceTableRow">
            <div class = 'rn_KAPerformanceTableCell rn_KAPerformanceTableCol1'>API URL</div>
            <div  class = 'rn_KAPerformanceTableCell rn_KAPerformanceTableCol2'>Method</div>
            <div  class = 'rn_KAPerformanceTableCell rn_KAPerformanceTableCol2'>Statue Code</div>
            <div  class = 'rn_KAPerformanceTableCell rn_KAPerformanceTableCol2'>API Duration</div>
        </div>
    <? endif; ?>
    <? for ($i = 0; $i < count($apiTiminig); $i++): ?>
        <? if ($apiTiminig[$i]['key']): ?>
        <? $apiDetails = explode(' | ', $apiTiminig[$i]['key']) ?>
        <div class="rn_KAPerformanceTableRow">
            <div class = 'rn_KAPerformanceTableCell rn_KAPerformanceTableCol1'><?= $apiDetails[0] ?></div>
            <div  class = 'rn_KAPerformanceTableCell rn_KAPerformanceTableCol2'><?= $apiDetails[1] ?></div>
            <div  class = 'rn_KAPerformanceTableCell rn_KAPerformanceTableCol2'><?= explode('Status code - ', $apiDetails[2])[1] ?></div>
            <div  class = 'rn_KAPerformanceTableCell rn_KAPerformanceTableCol2'><?= $apiTiminig[$i]['value'] ?></div>
        </div>
        <? endif; ?>
    <? endfor; ?>
    </div>
<? endif; ?>
    <rn:block id="bottom"/>
</div>