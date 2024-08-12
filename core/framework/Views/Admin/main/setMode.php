<? use RightNow\Utils\Config;
   use RightNow\Utils\Text; ?>

<h2><?=Config::getMessage(SET_ENVIRONMENT_CMD);?></h2>

<?
    require_once CPCORE . 'Internal/Utils/Admin.php';
    $modeArray = \RightNow\Internal\Utils\Admin::getEnvironmentModes();
    $defaultPageSet = array('/' => Config::getMessage(STANDARD_LBL));
    $pageSets = array_filter(array_merge($defaultPageSet, $vars['mappings']));
    if(isset($_COOKIE['agent']) && $_COOKIE['agent']) {
        $agentCookie = $_COOKIE['agent'];
        $browserDefault = false;
    }
    else {
        $agentCookie = '/';
        $browserDefault = true;
    }
    //currently selected page set is either the default page set or something else
    $agentName = ($agentCookie === '/') ? $pageSets[$agentCookie] : $agentCookie;
    //mode has been set or defaults to production
    list($location) = \RightNow\Environment\retrieveModeAndModeTokenFromCookie();
    $modeCookie = ($location) ? $location : 'production';
    $isReference = ($modeCookie === 'reference') ? 'true' : 'false';
    $defaultPageSets = array_merge($defaultPageSet, $vars['defaultMappings']);
    $isInAbuse = \RightNow\Libraries\AbuseDetection::isForceAbuseCookieSet();
?>

<div class="pageMode" id="pageMode">
    <h3><?=Config::getMessage(HERE_WE_GO_LBL);?></h3>
    <? /* Error message for the only trouble a user can get into... */?>
    <div id="pageSetError" class="hide error"><?=Config::getMessage(WHOA_PG_SET_YOUVE_DOESNT_REF_MODE_MSG);?></div>
    <? /* Reference/Development/Production mode-chooser */ ?>
    <section class="rel">
        <h4><?=Config::getMessage(FIRST_LBL);?></h4>
        <div class="rel donkey">
            <label for="siteModeButton"><?=Config::getMessage(SELECT_SITE_MODE_YOUD_LIKE_VIEW_LBL);?></label>
            <button type="button" id="siteModeButton"><?=$modeArray[$modeCookie];?><small>▼</small></button>
            <div class="bd">
                <ul class="hide dropdown abs" id="modeSelection">
                    <? $eventHandler = "select(this, 'siteModeButton', 'modeSelection', '%s')";?>
                    <? foreach ($modeArray as $mode => $description): ?>
                        <li class="<?= (($modeCookie === $mode) ? 'selected' : '') ?>">
                            <a href="javascript:void(0);" data-value="<?= $mode ?>"><?= $modeArray[$mode] ?></a>
                        </li>
                    <? endforeach; ?>
                </ul>
            </div>
        </div>

        <? /* Allow user to trigger Abuse Detection while in Development mode */ ?>
        <div><br />
            <em><?=Config::getMessage(ABUSE_DETECTION_ELLIPSIS_MSG);?></em>

            <input type="checkbox" <?=(($modeCookie === 'development' && $isInAbuse === true) ? 'checked' : '')?> <?=(($modeCookie !== 'development') ? 'disabled' : '')?> id="enableAbuse"/>
            <label for="enableAbuse" id="enableAbuseLabel" <?=(($modeCookie !== 'development') ? 'class="disabled"' : '')?>><?=Config::getMessage(TRIGGER_ABUSE_DETECTION_LBL)?></label>

            <a href="javascript:void(0);" id="abuseDetectionHelp"><span class="screenreader"><?=Config::getMessage(HELP_LBL);?></span><i class="fa fa-question-circle fa-lg"></i></a>
            <div class="hide tooltip abs" id="abuseDetectionTooltip">
                <?=Config::getMessage(ORACLE_RN_CX_CLOUD_SERV_PROV_WEB_MSG);?>
            </div>
        </div>
    </section>

    <? /* Page set-chooser */ ?>
    <section class="rel">
        <h4><?=Config::getMessage(NEXT_LBL);?></h4>
        <div class="rel">
            <label for="pageSetButton"><? printf(Config::getMessage(SELECT_PCT_S_YOUD_VIEW_LBL), '<a href="/ci/admin/configurations/pageset">' . Config::getMessage(PAGE_SET_LC_LBL) . '</a>') ?></label>
            <button <?=(($browserDefault) ? 'disabled' : '');?> type="button" id="pageSetButton"><?=Text::escapeHtml($agentName);?><small>▼</small></button>
            <div class="bd">
                <ul class="hide dropdown abs donkey" id="pageSelection">
                    <? $eventHandler = "select(this, 'pageSetButton', 'pageSelection', '%s')";
                        foreach ($pageSets as $label) {
                            $value = ($label === Config::getMessage(STANDARD_LBL)) ? '/' : \RightNow\Utils\Text::escapeStringForJavaScript($label);
                            $selected = ($agentCookie === $value) ? 'selected' : ''; ?>
                            <li class='<?= $selected ?>'>
                                <a href='javascript:void(0)' data-value="<?= $value ?>"><?= Text::escapeHtml($label) ?></a>
                            </li>
                        <? }
                    ?>
                </ul>
            </div>
            </div>
            <? /* Choose browser default instead */ ?>
            <div><br/>
                <em><?=Config::getMessage(OR_ELLIPSIS_MSG);?></em>
                <input type="checkbox" <?=(($browserDefault) ? 'checked' : '');?> id="userAgent"/>
                <label for="userAgent" id="userAgentLabel"><?=Config::getMessage(BROWSER_S_AGT_DECIDING_FACTOR_MSG);?></label>
                <div class="<?=(($browserDefault) ? '' : 'hide');?> tooltip abs" id="userAgentHelper"><? printf('<b>' . Config::getMessage(YOUR_USER_AGENT_IS_PCT_S_MSG), '</b><i>' . \RightNow\Utils\Text::escapeHtml($_SERVER['HTTP_USER_AGENT'])) . '</i>';?></i></div>
            </div>
            <hr class="clear"/>
    </section>

    <? /*Let's do this! section*/?>
    <section id="submit">
        <h4><?=Config::getMessage(READY_MSG);?></h4>
        <button id="itsGoTime" type="submit"><?=Config::getMessage(SET_VIEWING_MODE_AND_VIEW_SITE_CMD);?></button>
    </section>
</div>
<script>
var referenceMode = <?= $isReference ?>,
    defaultPageSets = <?= json_encode($defaultPageSets) ?>,
    agentCookie = "<?= Text::escapeHtml($agentCookie) ?>",
    modeCookie = "<?= $modeCookie ?>";
</script>
