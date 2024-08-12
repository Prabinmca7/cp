<? use RightNow\Utils\Config;?>
<h2><?= Config::getMessage(STAGE_LBL) ?></h2>
<div class="box">
    <?=$buttons;?>
    <div id="contentsContainer">
        <div id="statusContainer"></div>
        <div id="responseContainer" class="rn_ResponseContainer">
            <div id="fileTable" class="table"><?=$fileTable;?></div>
            <div id="pageSetTable" class="table"><?=$pageSetTable;?></div>
            <div id="versionsTable" class="table"><?=$versionsTable;?></div>
            <div class="lighterShadedBox table">
                <div id="commentArea" class="rn_CommentArea"><?=$comment;?></div>
                <form id="initializeArea">
                    <input type="checkbox" id="stageInitialize"/>
                    <label for="stageInitialize"><?= Config::getMessage(RE_INITIALIZE_STAGING_ENVIRONMENT_LBL) ?></label>
                    <span class="explain"><?= Config::getMessage(FILES_CONFIGURATIONS_VERSION_MSG) ?></span>
                </form>
            </div>
        </div>
        <div class="stageErrorAction hide">
            <?= Config::getMessage(ERRORS_CORRECTED_COLON_LBL) ?>
            <span class="underlinedLink"><a href="/ci/admin/deploy/selectFiles"><?= Config::getMessage(RE_STAGE_YOUR_CHANGES_LBL) ?></a></span>
        </div>
    </div>
    <div id="submitArea" class="lastStep">
        <button <?=$stageButtonDisabled;?> type="submit" id="stageSubmit">
            <?= \RightNow\Utils\Config::getMessage(STAGE_LBL) ?>
        </button>
        <label for="stageSubmit" id="stageButtonLabel"><?=$stageButtonLabel;?></label>
    </div>
</div>

<script>
var config = <?= json_encode(array(
    'accountID'  => $accountID,
    'deployType' => Config::getMessage(STAGE_LBL),
    'changes'    => $changesExist,
)) ?>;
var stageLabels = <?= json_encode(array(
    'confirmLabel'     => $confirmLabel,
    'proceedLabel'     => $proceedLabel,
    'loadingLabel'     => $loadingLabel,
    'loadingBody'      => $loadingBody,
    'copyAllLabel'     => $copyAllLabel,
    'confirmAllLabel'  => $confirmAllLabel,
    'warningLabel'     => $initializeWarning,
    'loadingAllLabel'  => $loadingAllLabel,
    'disabledLabel'    => $disabledLabel,
    'stageButtonLabel' => $stageButtonLabel,
    )) ?>;
</script>
