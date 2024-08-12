<div class="rn_WidgetBreadCrumb">
<? if (isset($pathLinks) && is_array($pathLinks)): ?>
    <? foreach ($pathLinks as $segment): ?>
        <? foreach ($segment as $option): ?>
            <? if (isset($option['selected']) && $option['selected']): ?>
                <span class="breadcrumbLink">
                    <a href="/ci/admin/docs/widgets/<?= $option['value'] ?>"><?= $option['label'] ?></a>
                    <img src="images/downArrow.gif" alt="<?= \RightNow\Utils\Config::getMessage(DOWN_LBL) ?>"/>&nbsp;/
                </span>
            <? endif; ?>
        <? endforeach; ?>

        <div class="dropdown hide">
            <div class="bd">
                <? sort($segment); ?>
                <? foreach ($segment as $option): ?>
                    <div class="widgetItem"><a href="/ci/admin/docs/widgets/<?= $option['value'] ?>"><?= $option['label'] ?></a></div>
                <? endforeach; ?>
            </div>
        </div>

    <? endforeach; ?>
<? endif; ?>
</div>
