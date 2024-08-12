<h2><?= \RightNow\Utils\Config::getMessage(OLD_FRMEWORK_MIGRATION_LBL) ?></h2>

<? if ($error): ?>
    <div class="error"><?= $error ?></div>
<? endif; ?>


<? if ($downgradeAllowed): ?>
    <h3><?= \RightNow\Utils\Config::getMessage(SWITCH_BACK_OLD_FRAMEWORK_VERSION_LBL) ?></h3>
    <p><?= \RightNow\Utils\Config::getMessage(SET_DEVELOPMENT_ENVIRONMENT_VERSION_CMD) ?></p>
    <h4><?= \RightNow\Utils\Config::getMessage(THINGS_TO_KNOW_LBL)?></h4>
    <ul>
        <li><?= \RightNow\Utils\Config::getMessage(AFFECT_PRODUCTION_STAGING_LBL) ?></li>
        <li><?= \RightNow\Utils\Config::getMessage(THE_CHANGE_CAN_BE_REVERTED_LATER_MSG) ?></li>
        <li><?= \RightNow\Utils\Config::getMessage(ASSETS_SHARED_VERSION_2_3_CHANGES_MSG) ?></li>
    </ul>
    
    <form id="migrateFramework" method="post" action="/ci/admin/tools/migrateFramework">
        <input type="hidden" name="formToken" value="<?= $formToken ?>" />
        <button type="submit"><?= \RightNow\Utils\Config::getMessage(SWITCH_BACK_TO_V2_LBL) ?> →</button>
    </form>
<? else: ?>
    <div class="error"><?= \RightNow\Utils\Config::getMessage(DOWNGRADE_FRAMEWORK_ALLOWED_MSG); ?></div>
<? endif; ?>
