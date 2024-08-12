<h2><?= \RightNow\Utils\Config::getMessage(FRAMEWORK_LBL) ?></h2>

<h3><a href="/ci/admin/versions/manage/#tab=1"><?= \RightNow\Utils\Config::getMessage(FRAMEWORK_VERSIONS_LBL) ?></a></h3>
<p><?= \RightNow\Utils\Config::getMessage(VIEW_AVAIL_FRAMEWORK_VERSIONS_SET_CMD) ?></p>

<h3><a href="/ci/admin/docs/framework/pageTags"><?=\RightNow\Utils\Config::getMessage(PAGE_TAGS_LBL);?></a></h3>
<p><?=\RightNow\Utils\Config::getMessage(INFO_WIDGET_TAGS_AVAIL_CUST_MSG);?></p>

<h3><a href="/ci/admin/docs/framework/pageMeta"><?=\RightNow\Utils\Config::getMessage(PAGE_META_TAGS_LBL);?></a></h3>
<p><?=\RightNow\Utils\Config::getMessage(INFO_OPTS_ATTRIB_AVAIL_RN_META_TAG_MSG);?></p>

<h3><a href="/ci/admin/docs/framework/businessObjects"><?=\RightNow\Utils\Config::getMessage(BUSINESS_OBJECTS_LBL);?></a></h3>
<p><?=\RightNow\Utils\Config::getMessage(LISTING_DATASOURCES_AVAIL_FLDS_MSG);?></p>
<? if (\RightNow\Utils\Config::getConfig(CP_DOWNGRADE_TO_V2_ALLOWED)): ?>
    <h3><a href="/ci/admin/tools/migrateFramework"><?= \RightNow\Utils\Config::getMessage(OLD_FRAMEWORK_MIGRATION_LBL) ?></a></h3>
    <p><?= \RightNow\Utils\Config::getMessage(SWITCH_BACK_TO_THE_OLD_FRAMEWORK_MSG) ?></p>
<? endif; ?>
