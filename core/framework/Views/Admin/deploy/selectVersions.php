<? use RightNow\Utils\Config; ?>
<h2><?= Config::getMessage(SELECT_VERSION_CHANGES_LBL) ?></h2>
<div class="box">
    <?= $buttons ?>
    <section id="contentsContainer">
        <?= $versionsTable ?>
    </section>
    <section class="nextStep">
        <button id="next" type="submit" data-next-step="selectConfigs" title="<?= Config::getMessage(CONFIRM_SEL_VERSION_CHANGES_PROCEED_MSG) ?>">
            <?= Config::getMessage(NEXT_GT_WIN_G_HK) ?>
        </button>
        <label for="next"><?= Config::getMessage(CONFIRM_SEL_VERSION_CHANGES_PROCEED_MSG) ?></label>
    </section>
</div>
