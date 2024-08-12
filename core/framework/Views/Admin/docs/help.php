<h2><?= $pageTitle; ?></h2>

<?if($displayMigrationDetails):?>
    <div class="deprecated"><?=\RightNow\Utils\Config::getMessage(DEVELOPMENT_PAGES_VERSION_3_BEG_MSG);?></div>
    <h3><?=\RightNow\Utils\Config::getMessage(NEXT_STEPS_AND_MIGRATION_RESOURCES_LBL);?></h3>
    <ul>
        <li><a target="_blank" href="http://documentation.custhelp.com/euf/assets/docs/<?=\RightNow\Utils\Url::getProductVersionForLinks();?>/cp_resources/migration/migration_guide.pdf"><?=\RightNow\Utils\Config::getMessage(ONLINE_MIGRATION_GUIDE_FOLLOW_MSG);?></a></li>
        <li><a target="_blank" href="/ci/admin/versions/manage/#tab=1&amp;framework=<?= $frameworkVersion ?>"><? printf(\RightNow\Utils\Config::getMessage(PCT_S_FRAMEWORK_CHANGELOG_LBL), $frameworkVersion) ?></a></li>
        <li><a target="_blank" href="/ci/admin/overview/set_cookie/development"><?=\RightNow\Utils\Config::getMessage(VIEW_DEVELOPMENT_TRACK_MIGRATION_CMD);?> *</a></li>
        * <small><?=\RightNow\Utils\Config::getMessage(CUST_FILES_BROUGHT_CP_FRAMEWORKS_MSG);?></small>
        <li><a target="_blank" href="/ci/admin/assistant"><?=\RightNow\Utils\Config::getMessage(CODE_ASSISTANT_LBL);?></a> (<?= \RightNow\Utils\Config::getMessage(INTF_IMPROVING_S_MIGRATING_CUST_LBL);?>)</li>
    </ul>
<?endif;?>
<h3><?=\RightNow\Utils\Config::getMessage(GET_HELP_LBL);?></h3>
<ul>
<?if($languageCode !== '-jp'):?>
    <li><a target="_blank" href="https://documentation.custhelp.com/euf/assets/devdocs/<?=\RightNow\Utils\Url::getProductVersionForLinks();?>/CustomerPortal/index.html"><?=\RightNow\Utils\Config::getMessage(CUSTOMER_PORTAL_DOCUMENTATION_LBL);?></a></li>
<?else:?>
    <li><a target="_blank" href="https://documentation<?=$languageCode;?>.custhelp.com/euf/assets/docs/<?=\RightNow\Utils\Url::getProductVersionForLinks();?>/CustomerPortal/index.html"><?=\RightNow\Utils\Config::getMessage(CUSTOMER_PORTAL_DOCUMENTATION_LBL);?></a></li>
<?endif;?>
    <li><a target="_blank" href="https://cx.rightnow.com/app/landing/consulting"><?=\RightNow\Utils\Config::getMessage(PROFESSIONAL_SERVICES_OFFERINGS_LBL);?></a></li>
    <li><a target="_blank" href="http://documentation.custhelp.com/euf/assets/devdocs/unversioned/cp_resources/api/index.html"><?=\RightNow\Utils\Config::getMessage(CUSTOMER_PORTAL_API_DOCUMENTATION_LBL);?></a></li>
</ul>
<h3><?=\RightNow\Utils\Config::getMessage(GET_INVOLVED_LBL);?></h3>
<ul>
    <li><a target="_blank" href="https://community.oracle.com/customerconnect/categories/cx-customer-portal"><?=\RightNow\Utils\Config::getMessage(CUSTOMER_PORTAL_DISCUSSION_BOARD_LBL);?></a></li>
    <li><a target="_blank" href="https://community.oracle.com/customerconnect/categories/idealab-cx-b2c-service"><?=\RightNow\Utils\Config::getMessage(MAKE_SUGGESTIONS_LBL);?></a></li>
</ul>
<h3><?=\RightNow\Utils\Config::getMessage(ADDITIONAL_RESOURCES_LBL);?></h3>
<ul>
    <li><a href="/ci/admin/overview/previewErrorPages"><?=\RightNow\Utils\Config::getMessage(PREVIEW_ERROR_PAGES_CMD);?></a></li>
    <li><a target="_blank" href="http://yuilibrary.com">YUI</a> <i>(<?= sprintf(\RightNow\Utils\Config::getMessage(CURRENT_VERSION_PCT_S_COLON_LBL), CP_YUI_VERSION) ?>)</i></li>
    <li><a target="_blank" href="http://php.net">PHP.net</a> <i>(<?= sprintf(\RightNow\Utils\Config::getMessage(CURRENT_VERSION_PCT_S_COLON_LBL), CP_PHP_VERSION) ?>)</i></li>
    <li><a target="_blank" href="https://codeigniter.com/userguide3/index.html">CodeIgniter</a> <i>(<?= sprintf(\RightNow\Utils\Config::getMessage(CURRENT_VERSION_PCT_S_COLON_LBL), CI_VERSION) ?>)</i></li>
    <li><a target="_blank" href="http://www.w3schools.com/css/default.asp">W3schools - CSS</a></li>
</ul>
