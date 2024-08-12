<h2><?= \RightNow\Utils\Config::getMessage(SELECT_FILES_LBL) ?></h2>
<div class="box">
    <?= $buttons ?>
    <section id="contentsContainer">
        <?= $files ?>
    </section>
    <section class="nextStep">
        <button id="next" type="submit" data-next-step="selectVersions" title="<?= \RightNow\Utils\Config::getMessage(CONFIRM_SEL_FILE_ACTIONS_PROCEED_MSG) ?>">
            <?= \RightNow\Utils\Config::getMessage(NEXT_GT_WIN_G_HK) ?>
        </button>
        <label for="next"><?= \RightNow\Utils\Config::getMessage(CONFIRM_SEL_FILE_ACTIONS_PROCEED_MSG) ?></label>
    </section>
</div>
