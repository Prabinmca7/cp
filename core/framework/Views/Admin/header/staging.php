<!-- +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++ -->
<!-- Begin Staging Header HTML.  It does not appear on production pages.     -->
<!-- +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++ -->
<script src="<?= $assetBasePath ?>debug-js/RightNow.UI.DevelopmentHeader.js"></script>
<div id="rn_DevelopmentHeader">
    <div id="rn_DevelopmentHeaderPanel">
        <div class="yui3-widget-hd">
            <span class="rn_PanelTitle" data-alternate="<?= \RightNow\Utils\Config::getMessage(CUSTOMER_PORTAL_ADMIN_PAGE_LBL) ?>" data-default="<?= $title ?> - <?= $frameworkVersion ?>"><?= $title ?> - <?= $frameworkVersion ?></span> &nbsp;&nbsp;
            <div id="rn_ExpandedDevelopmentHeader" style="display:none"></div>
        </div>
    </div>
</div>
<script>YUI().use('event-base', function(y){y.on('domready', function(){RightNow.UI.DevelopmentHeader.initializePanel("", 0, 0, "<?= \RightNow\Utils\Url::getShortEufBaseUrl(true) ?>");}, window);});</script>
<!-- +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++ -->
<!-- End Staging Header HTML. It does not appear on production pages.        -->
<!-- +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++ -->
