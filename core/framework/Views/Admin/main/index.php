<?
use RightNow\Utils\Config;
$stagingAreas = \RightNow\Internal\Utils\Admin::getStagingEnvironmentModes();
$versionLabel = \RightNow\Internal\Utils\Framework::getPhpVersionLabel();
$versionDialogContent = "<div class='rn_SupportedPhpVersions'><strong>".Config::getMessage(PHP_VERSION_LBL)."</strong> <select id='phpVersionstList'></select></div><div>&nbsp;</div>
                        <div class='rn_VersionUpdate'>".Config::ASTRgetMessage(sprintf("Current PHP Version: %s", $versionLabel)) ."</div><div>&nbsp;</div>
                        <div class='rn_info'><i>".Config::getMessage(BACK_II_CHG_CUSTOMIZATINS_BHV_FLD_VRSNS_MSG)."<i></div>";
?>

<h2><?=Config::getMessage(DASHBOARD_LBL);?></h2>
    <a href="javascript:void(0);" class="versionInfoToggle"><?=Config::getMessage(VERSION_INFO_UC_LBL);?></a>
    <div class="versionInfo hide">
        <p><?=Config::getMessage(RIGHTNOW_VERSION_LBL) . " " . sprintf(Config::getMessage(BUILD_PCT_S_LBL), MOD_CX_BUILD_NUM . ((MOD_BUILD_SP) ? ' SP ' . MOD_BUILD_SP : '') . ', CP ' . MOD_BUILD_NUM);?></p>
        <? $frameworkVersions = \RightNow\Internal\Utils\Version::getVersionsInEnvironments('framework'); ?>
        <? if ($frameworkVersions['Development'] === $frameworkVersions['Staging'] && $frameworkVersions['Development'] === $frameworkVersions['Production']): ?>
            <p><?= sprintf(Config::getMessage(FRAMEWORK_VERSION_PCT_S_LBL), $frameworkVersions['Development']) ?></p>
        <? else: ?>
            <p><?= sprintf(Config::getMessage(DEVELOPMENT_S_REF_FRAMEWORK_PCT_S_LBL), $frameworkVersions['Development']); ?></p>
            <p><?= sprintf(Config::getMessage(STAGING_FRAMEWORK_PCT_S_COLON_LBL), $frameworkVersions['Staging']); ?></p>
            <p><?= sprintf(Config::getMessage(PRODUCTION_FRAMEWORK_PCT_S_COLON_LBL), $frameworkVersions['Production']); ?></p>
        <? endif; ?>
        <? $phpVersions = \RightNow\Internal\Utils\Version::getVersionsInEnvironments('phpVersion'); ?>
        <? if ($phpVersions['Development'] === $phpVersions['Staging'] && $phpVersions['Development'] === $phpVersions['Production']): ?>
            <p><?= Config::getMessage(PHP_VERSION_LBL) .' '. $versionLabel ?></p>
        <? else: ?>
            <p><?= Config::getMessage(DEVELOPMENT_PHP_VERSION_LBL)?>: <?= \RightNow\Internal\Utils\Framework::getPhpVersionLabel($phpVersions['Development']); ?></p>
            <p><?= Config::getMessage(STAGING_PHP_VERSION_LBL)?>: <?= \RightNow\Internal\Utils\Framework::getPhpVersionLabel(!empty($phpVersions['Staging']) ? $phpVersions['Staging'] : CP_LEGACY_PHP_VERSION); ?></p>
            <p><?= Config::getMessage(PRODUCTION_PHP_VERSION_LBL)?>: <?= \RightNow\Internal\Utils\Framework::getPhpVersionLabel(!empty($phpVersions['Production']) ? $phpVersions['Production'] : CP_LEGACY_PHP_VERSION); ?></p>
        <? endif; ?>
    </div>
<section class="links">
    <h3><?=Config::getMessage(ENVIRONMENT_LBL);?></h3>
    <div class="quicklinks">
        <div class="link"><a href="/ci/admin/overview/set_cookie/development"><span id="dev"></span><?=Config::getMessage(DEVELOPMENT_MODE_LBL);?></a></div>
        <? if($stagingAreas):
           $href = array_keys($stagingAreas);
           $href = $href[0];?>
        <div class="link"><a id="iWantToViewStaging" href="/ci/admin/overview/set_cookie/<?=$href;?>"><span id="staging"></span><?=Config::getMessage(STAGING_MODE_LBL);?></a></div>
        <? else:?>
        <div class="link disabled"><a href="javascript:void(0);"><span id="staging"></span><?=Config::getMessage(STAGING_MODE_LBL);?></a></div>
        <? endif;?>
        <? if(Config::getConfig(MOD_CP_ENABLED, 'COMMON')): ?>
            <div class="link"><a href="/ci/admin/overview/set_cookie/production"><span id="production"></span><?=Config::getMessage(PRODUCTION_MODE_LBL);?></a></div>
        <? else:?>
            <div class="link disabled"><a href="javascript:void(0);"><span id="production"></span><?=Config::getMessage(PRODUCTION_MODE_LBL);?></a></div>
        <? endif;?>
        <div class="link"><a href="/ci/admin/overview/setMode"><span id="setMode"></span><?=Config::getMessage(SET_ENVIRONMENT_CMD);?></a></div>
        <div class="link"><a href="/ci/admin/configurations/pageSet"><span id="userAgent"></span><?=Config::getMessage(PAGE_SET_MAPPINGS_LBL);?></a></div>
        <div class="link"><a href="javascript:void(0)" id="phpVersion"><span id="phpVersionUpdate"></span><?=Config::getMessage(PHP_VERSION_LBL);?></a></div>
    </div>
</section>
<section class="links">
    <h3><?=Config::getMessage(DESIGN_LBL);?></h3>
    <div class="quicklinks">
        <div class="link"><a href="/ci/admin/tools/widgetBuilder"><span id="widgetBuilder"></span><?=Config::getMessage(CREATE_A_NEW_WIDGET_UC_CMD);?></a></div>
        <div class="link"><a href="/ci/admin/overview/set_cookie/developmentInspector"><span id="widgetInspector"></span><?=Config::getMessage(WIDGET_INSPECTOR_LBL);?></a></div>
    </div>
</section>
<section class="links">
    <h3><?=Config::getMessage(TOOLS_LBL);?></h3>
    <div class="quicklinks">
    <div class="link"><a href="/ci/admin/assistant"><span id="codeAssistant"></span><?=Config::getMessage(CODE_ASSISTANT_LBL);?></a></div>
    <div class="link"><a href="/s/oit/latest" target="_blank"><span id="inlayRegistry"></span>Inlay Registry</a></div>
    </div>
</section>
<section class="links">
    <h3><?=Config::getMessage(DOCUMENTATION_LBL);?></h3>
    <div class="quicklinks">
    <div class="link"><a href="/ci/admin/docs/widgets/browse"><span id="documentation"></span><?=Config::getMessage(WIDGET_DOCUMENTATION_UC_LBL);?></a></div>   
    <div class="link"><a href="/ci/admin/docs/help"><span id="help"></span><?=Config::getMessage(HELP_LBL);?></a></div>
    </div>
</section>

<div class="yui3-widget-mask" style="position: fixed; width: 100%; height: 100%; top: 0px; left: 0px; z-index: 100; display: none;" ></div>

<script type="text/javascript">
var phpVersionUpdateDialogVars= <?= json_encode(array(
    'cancelLabel' => Config::getMessage(CANCEL_CMD),
    'updateLabel' => Config::getMessage(SAVE_CHANGE_CMD),
    'detailsLabel' => Config::ASTRgetMessage(sprintf("Current PHP Version: %s", $versionLabel)),
    'headerLabel' => Config::getMessage(UPDATE_PHP_VERSION_LBL),
    'phpVersions' => $vars['phpVersionList'],
    'phpCurrentVerion' => CP_PHP_VERSION,
    'dialogContent' => $versionDialogContent,
    'closeLabel' => Config::getMessage(CLOSE_LBL),
    'okLabel' => Config::getMessage(OK_LBL),
    'failedLabel' => Config::getMessage(FALIED_LBL),
    'successLabel' => Config::getMessage(SUCCESS_LBL),
    'failedText' => Config::getMessage(PHP_S_UPGRADE_FAILED_TRY_AGAIN_LATER_MSG),
    'successText' => Config::getMessage(S_RPT_UPGRADE_MODE_RR_BACK_CHG_CSTMZTNS_MSG),
)) ?>;
</script>