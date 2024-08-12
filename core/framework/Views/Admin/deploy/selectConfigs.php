<? use RightNow\Utils\Config; ?>
<h2><?= Config::getMessage(SELECT_CONFIGURATIONS_LBL) ?></h2>
<div class="box">
    <?= $buttons ?>
    <section id="contentsContainer">
        <?= $pageSetTable ?>
    </section>
    <section class="nextStep">
        <button id="next" type="submit" data-next-step="stage" title="<?= Config::getMessage(CONFIRM_SEL_CONFIG_ACTS_PROCEED_MSG) ?>">
            <?= Config::getMessage(NEXT_GT_WIN_G_HK) ?>
        </button>
        <label for="next"><?= Config::getMessage(CONFIRM_SEL_CONFIG_ACTS_PROCEED_MSG) ?></label>
    </section>
</div>
