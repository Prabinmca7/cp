<?use RightNow\Utils\Config, \RightNow\Internal\Utils\Admin;?>
<!DOCTYPE html>
<!--[if IE 7]><html class="ie7" lang="<?=\RightNow\Utils\Text::getLanguageCode();?>"><![endif]-->
<!--[if IE 8]><html class="ie8" lang="<?=\RightNow\Utils\Text::getLanguageCode();?>"><![endif]-->
<!--[if gt IE 8]><!--><html lang="<?=\RightNow\Utils\Text::getLanguageCode();?>"><!--<![endif]-->
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width,initial-scale=0.6"/>
    <title><?= $pageTitle ?></title>
    <base href="<?=\RightNow\Utils\Url::getShortEufBaseUrl('sameAsRequest', \RightNow\Utils\Url::getCoreAssetPath());?>"/>
    <link rel="stylesheet" type="text/css" href="admin/css/admin.css"/>
    <link rel="stylesheet" type="text/css" href="<?= \RightNow\Utils\Url::getYUICodePath('panel') ?>/assets/skins/sam/panel.css" />
    <link rel="stylesheet" type="text/css" href="thirdParty/css/font-awesome.min.css"/>
    <link rel="shortcut icon" href="/euf/core/static/favicon.ico" type="image/x-icon"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <!--[if lt IE 9]><script type="text/javascript" src="/euf/core/static/html5.js"></script><![endif]-->
    <?= $css ?>
    <script>
        labels = {};
        <?
            $cachedContentServer = "/ci/cache/yuiCombo/";
            if(IS_HOSTED && ($cachedServerConfig = Config::getConfig(CACHED_CONTENT_SERVER))){
                $cachedContentServer = "//{$cachedServerConfig}{$cachedContentServer}";
            }
        ?>
        <?if($formToken && $submitTokenExp && $labels):?> 
            formToken = "<?= $formToken ?>";
            submitTokenExp = <?= $submitTokenExp ?>;
            <?foreach($labels as $key => $value):?>
                  labels.<?=$key?> = "<?=$value?>";          
            <?endforeach;?>
        <?endif;?>
        YUI_config = {'comboBase':'<?=$cachedContentServer;?>','lang':['<?=\RightNow\Utils\Text::getLanguageCode();?>','en-US'],'fetchCSS':false, loadErrorFn: function(){
            if(!window.hasSeenYUILoadError){
                window.hasSeenYUILoadError = true;
                document.getElementsByTagName('header')[0].innerHTML += "<div class='error' style='clear:both'><?=Config::getMessage(SITEMISCONFIGURED_CACHED_CONTENT_MSG);?></div>";
            }
        }};
    </script>
    <script src="<?=\RightNow\Utils\Url::getYUICodePath('combined-yui.js');?>"></script>
</head>
<body class="yui-skin-sam yui3-skin-sam">
<header>
    <div class="screenreader"><a href="<?= $_SERVER['REQUEST_URI'] ?>#content"><?=Config::getMessage(SKIP_NAVIGATION_CMD);?></a></div>
    <div id="sitetitle">
        <!--[if IE 7]><marquee width=500 loop=1 behavior=slide><?=Config::getMessage(YOURE_OUTDATED_BROWSER_PLS_MSG);?></marquee><![endif]-->
        <h1><a href="/ci/admin/overview">Customer Portal</a></h1>
        <h2><?= $pageTitle ?></h2>
    </div>
    <div id="user"><? printf(Config::getMessage(WELCOME_B_PCT_S_SLASH_B_LBL), $userName); ?>
        <div id="sitemode"><a href="/ci/admin/overview/setMode"><? printf(Config::getMessage(CURRENT_SITE_MODE_PCT_S_LBL), $siteMode['mode'] . '-' . \RightNow\Utils\Text::escapeHtml($siteMode['agent'])); ?></a></div>
    </div>
</header>
<nav>
    <a class="hide" id="toggleMenu" href="javascript:void(0);"><span class="screenreader"><?= Config::getMessage(TOGGLE_EXTRA_NAVIGATION_ITEMS_LBL) ?></span><i role="presentation" class="fa fa-angle-down"></i></a>
    <div id="navmenu" class="yui3-menu yui3-menu-horizontal yui3-menubuttonnav">
        <div class="yui3-menu-content">
            <ul>
                <li class="yui3-menuitem <?= ($controller === 'overview') ? 'current' : '' ?>">
                    <a href="/ci/admin/overview"><?=Config::getMessage(DASHBOARD_LBL);?></a>
                </li>
                <li class="<?= ($controller === 'docs' && $method === 'framework') ? 'current' : '' ?>">
                    <a class="yui3-menu-label" href="/ci/admin/docs/framework"><?=Config::getMessage(FRAMEWORK_LBL);?> <small><i class="fa fa-chevron-down"></i></small></a>
                    <div class="yui3-menu yui3-menu-hidden">
                        <div class="yui3-menu-content">
                            <ul class="dropdown" id="dd1">
                                <li class="yui3-menuitem"><a class="yui3-menuitem-content" href="/ci/admin/versions/manage/#tab=1"><?=Config::getMessage(FRAMEWORK_VERSIONS_LBL) ?></a></li>
                                <li class="yui3-menuitem"><a class="yui3-menuitem-content" href="/ci/admin/docs/framework/pageTags"><?=Config::getMessage(PAGE_TAGS_LBL);?></a></li>
                                <li class="yui3-menuitem"><a class="yui3-menuitem-content" href="/ci/admin/docs/framework/pageMeta"><?=Config::getMessage(PAGE_META_TAGS_LBL);?></a></li>
                                <li class="yui3-menuitem"><a class="yui3-menuitem-content" href="/ci/admin/docs/framework/businessObjects"><?=Config::getMessage(BUSINESS_OBJECTS_LBL) ?></a></li>
                                <? if (Config::getConfig(CP_DOWNGRADE_TO_V2_ALLOWED)): ?>
                                    <li class="yui3-menuitem"><a class="yui3-menuitem-content" href="/ci/admin/tools/migrateFramework"><?=Config::getMessage(OLD_FRAMEWORK_MIGRATION_LBL) ?></a></li>
                                <? endif; ?>
                            </ul>
                        </div>
                    </div>
                </li>
                <li class="<?= ($controller === 'docs' && $method === 'widgets') ? 'current' : '' ?>">
                    <a class="yui3-menu-label" href="/ci/admin/docs/widgets"><?=Config::getMessage(WIDGETS_LBL);?> <small><i class="fa fa-chevron-down"></i></small></a>
                    <div class="yui3-menu yui3-menu-hidden">
                        <div class="yui3-menu-content">
                            <ul class="dropdown" id="dd2">
                                <li class="yui3-menuitem"><a class="yui3-menuitem-content" href="/ci/admin/docs/widgets/browse"><?=Config::getMessage(BROWSE_WIDGETS_LBL);?></a></li>
                                <li class="yui3-menuitem"><a class="yui3-menuitem-content" href="/ci/admin/versions/manage"><?=Config::getMessage(WIDGET_VERSIONS_LBL) ?></a></li>
                                <li class="yui3-menuitem"><a class="yui3-menuitem-content" href="/ci/admin/tools/widgetBuilder"><?=Config::getMessage(CREATE_A_NEW_WIDGET_UC_CMD);?></a></li>
                                <li class="yui3-menuitem"><a class="yui3-menuitem-content" href="/ci/admin/docs/widgets/info"><?=Config::getMessage(WIDGET_INFO_LBL);?></a></li>
                                <li class="yui3-menuitem"><a class="yui3-menuitem-content" href="/ci/tags/syndicated_widgets"><?=Config::getMessage(SYNDICATED_WIDGETS_LBL);?></a></li>
                                <li class="yui3-menuitem"><a class="yui3-menuitem-content" href="/ci/admin/versions/widgetsNoLongerInUse"><?=Config::getMessage(CUSTOM_WIDGETS_NOT_IN_USE_LBL);?></a></li>
                            </ul>
                        </div>
                    </div>
                </li>
                <li class="<?= ($controller === 'logs') ? 'current' : '' ?>">
                    <a class="yui3-menu-label" href="/ci/admin/logs"><?=Config::getMessage(LOGS_LBL);?> <small><i class="fa fa-chevron-down"></i></small></a>
                    <div class="yui3-menu yui3-menu-hidden">
                        <div class="yui3-menu-content">
                            <ul class="dropdown" id="dd3">
                                <li class="yui3-menuitem"><a class="yui3-menuitem-content" href="/ci/admin/logs/webdav"><?=Config::getMessage(WEBDAV_LOGS_LBL);?></a></li>
                                <li class="yui3-menuitem"><a class="yui3-menuitem-content" href="/ci/admin/logs/debug"><?=Config::getMessage(DEBUG_LOGS_LBL);?></a></li>
                                <li class="yui3-menuitem"><a class="yui3-menuitem-content" href="/ci/admin/logs/deploy"><?=Config::getMessage(DEPLOYMENT_LOGS_LBL);?></a></li>
                            </ul>
                        </div>
                    </div>
                </li>
                <li class="<?= ($controller === 'configurations') ? 'current' : '' ?>">
                    <a class="yui3-menu-label" href="/ci/admin/configurations"><?=Config::getMessage(SETTINGS_LBL);?> <small><i class="fa fa-chevron-down"></i></small></a>
                    <div class="yui3-menu yui3-menu-hidden">
                        <div class="yui3-menu-content">
                            <ul class="dropdown" id="dd4">
                                <li class="yui3-menuitem"><a class="yui3-menuitem-content" href="/ci/admin/configurations/pageSet"><?=Config::getMessage(PAGE_SET_MAPPING_LBL);?></a></li>
                                <li class="yui3-menuitem"><a class="yui3-menuitem-content" href="/ci/admin/overview/setMode"><?=Config::getMessage(SET_ENVIRONMENT_CMD);?></a></li>
                                <li class="yui3-menuitem"><a class="yui3-menuitem-content" href="/ci/admin/configurations/sandboxedConfigs"><?=Config::getMessage(SANDBOXED_CONFIGURATIONS_LBL);?></a></li>
                                <? if (!IS_HOSTED): ?>
                                <li class="yui3-menuitem"><a class="yui3-menuitem-content" href="/ci/admin/versions/"><?=Config::getMessage(SET_VERSION_TEST_MODE_CMD);?></a></li>
                                <? endif; ?>
                                <li class="yui3-menuitem"><a class="yui3-menuitem-content" href="/ci/admin/overview/previewErrorPages"><?=Config::getMessage(PREVIEW_ERROR_PAGES_CMD);?></a></li>
                            </ul>
                        </div>
                    </div>
                </li>
                <li class="<?= ($controller === 'tools') ? 'current' : '' ?>">
                    <a class="yui3-menu-label" href="/ci/admin/tools"><?=Config::getMessage(TOOLS_LBL);?> <small><i class="fa fa-chevron-down"></i></small></a>
                    <div class="yui3-menu yui3-menu-hidden">
                        <div class="yui3-menu-content">
                            <ul class="dropdown" id="dd5">
                                <li class="yui3-menuitem"><a class="yui3-menuitem-content" href="/ci/admin/explorer"><?=Config::getMessage(CONNECT_OBJECT_EXPLORER_LBL);?></a></li>
                                <li class="yui3-menuitem"><a class="yui3-menuitem-content" href="/ci/admin/assistant"><?=Config::getMessage(CODE_ASSISTANT_LBL);?></a></li>
                            </ul>
                        </div>
                    </div>
                </li>
                <? if ($deployMenuItems): ?>
                <li class="<?= ($controller === 'deploy') ? 'current' : '' ?>">
                    <a class="yui3-menu-label" href="/ci/admin/deploy/index"><?=Config::getMessage(DEPLOY_LBL);?> <small><i class="fa fa-chevron-down"></i></small></a>
                    <div class="yui3-menu yui3-menu-hidden">
                        <div class="yui3-menu-content">
                            <ul class="dropdown" id="dd6">
                            <? foreach ($deployMenuItems as $href => $data): ?>
                                <li class="yui3-menuitem">
                                    <span title="<?= htmlspecialchars($data['description']) ?>">
                                        <?
                                           if ($data['disabled'] === true) {
                                            $href = 'javascript:void(0);';
                                            $class = 'disabled';
                                           }
                                           else {
                                              $class = 'yui3-menuitem-content';
                                           }
                                        ?>
                                        <a id="<?= $data['id'] ?>MenuItem" class="<?= $class ?>" href="<?= $href ?>"><?= $data['label'] ?></a>
                                    </span>
                                </li>
                            <? endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </li>
                <? endif; ?>
            </ul>
        </div>
    </div>
    <div id="viewSite" class="collapsible">
        <button type="button"><?=Config::getMessage(VIEW_SITE_CMD);?> <small><i class="fa fa-chevron-down"></i></small></button>
        <ul class="hide dropdown abs" id="viewSiteDropdown">
        <? foreach (Admin::getEnvironmentModes(false) as $environmentMode => $description): ?>
            <li><a href="/ci/admin/overview/set_cookie/<?= $environmentMode ?>"><?= $description ?></a></li>
        <? endforeach; ?>
            <li class="info"><? printf(Config::getMessage(CURRENT_SITE_MODE_PCT_S_LBL), $siteMode['mode'] . '-' . strip_tags($siteMode['agent'])); ?></li>
            <li id="setEnv" class="info">
                <a href="/ci/admin/overview/setMode" title="<?=Config::getMessage(SET_ENVIRONMENT_UC_CMD);?>"><i class="fa fa-cog"></i></a>
            </li>
        </ul>
    </div>

    <? if (($languages = Admin::getLanguageInterfaceMap(!IS_HOSTED)) && count($languages) > 1): ?>
        <div id="langSelect" class="collapsible">
            <button type="button"><?=Config::getMessage(LANGUAGES_LBL);?> <small><i class="fa fa-chevron-down"></i></small></button>
            <ul class="hide dropdown abs" id="langSelectDropdown">
            <? if ($languageData = get_instance()->_getRequestedAdminLangData()): ?>
                <? $selectedLang = $languageData[1]; ?>
            <? else: ?>
                <? $selectedLang = null; ?>
            <? endif; ?>
            <? foreach ($languages as $lang => $pair): ?>
                <? list($label, $interface) = $pair; ?>
                <li<?=($selectedLang === $lang ? ' class="selected"' : ''); ?>>
                    <a href="javascript:void(0);" data-intf="<?=$interface;?>" data-lang="<?=$lang;?>"><?=$label;?></a>
                </li>
            <? endforeach; ?>
            </ul>
        </div>
    <? endif; ?>

    <div id="helpItem" class="donkey collapsible">
        <a href="/ci/admin/docs/help"><span class="screenreader"><?= Config::getMessage(HELP_LBL) ?></span><i class="fa fa-question-circle fa-lg"></i></a>
    </div>
    <div class="right donkey auto collapsible">
        <form class="searchBox" onsubmit="return false;">
            <label for="widgetInput" class="screenreader" id="widgetInputLabel"><?=Config::getMessage(SRCH_WIDGETS_CMD);?></label>
            <input type="search" class="searchType" id="widgetInput" placeholder="<?=Config::getMessage(SRCH_WIDGETS_CMD);?>"/>
        </form>
    </div>
</nav>
<div id="content">
    <?= $content ?>
</div>
<footer>
    <div id="footerLinks" class="left">
        <a href="/ci/admin/overview"><?=Config::getMessage(DASHBOARD_LBL);?></a>
        <a href="/ci/admin/docs"><?=Config::getMessage(DOCS_LBL);?></a>
        <a href="/ci/admin/logs"><?=Config::getMessage(LOGS_LBL);?></a>
    </div>
    <div class="right textright">
        <p>&copy; <?=date('Y');?> <a href="<?=Config::getConfig(rightnow_url);?>">Oracle</a></p>
    </div>
</footer>
<script>
window.autoCompleteData = {
    folderSearchList: <?= $folderSearchList ?>,
    labels: <?= json_encode(array(
        'docs'     => Config::getMessage(DOCUMENTATION_LBL),
        'versions' => Config::getMessage(VERSION_INFO_LBL),
    )) ?>
};
</script>
<script src="admin/js/template.js"></script>
<script src="admin/js/formToken.js"></script>
<?= $js ?>
</body>
</html>
