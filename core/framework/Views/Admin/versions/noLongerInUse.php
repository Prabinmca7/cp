<h2><?= $title ?></h2>
<br />
<p><?= $description ?></p>
<div class="yui-skin-sam">
    <div id="widgetGrid" >
        <ul>
            <? foreach ($noLongerUsedWidgets as $widget): ?>
                <li><a href='<?= "/ci/admin/versions/manage#widget=" . urlencode($widget) ?>'><?= $widget ?></a></li>
            <? endforeach; ?>

            <? if (empty($noLongerUsedWidgets)): ?>
                <li><em><?= $allWidgetsUsed ?></em></li>
            <? endif; ?>
        </ul>
    </div>
</div>
<br /><br />
