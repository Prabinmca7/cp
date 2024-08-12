<h2><?= \RightNow\Utils\Config::getMessage(REMOVE_MISSING_ACTIVE_WIDGETS_CMD); ?></h2>
<? if ($deactivatedWidgets): ?>
<h3><?= \RightNow\Utils\Config::getMessage(FOLLOWING_WIDGETS_DEACTIVATED_LBL); ?></h3>
    <ul>
    <? foreach ($deactivatedWidgets as $widgetKey => $action): ?>
        <li><?= $widgetKey; ?></li>
    <? endforeach; ?>
    </ul>
<? else: ?>
<h3><?= \RightNow\Utils\Config::getMessage(THERE_WERE_NO_WIDGETS_DEACTIVATE_MSG); ?></h3>
<? endif; ?>
<div class="link"><a href="/ci/admin/overview/set_cookie/development"><span id="dev"></span><?=\RightNow\Utils\Config::getMessage(VIEW_DEVELOPMENT_AREA_CMD);?></a></div>
