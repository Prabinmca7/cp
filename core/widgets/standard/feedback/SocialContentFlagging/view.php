<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <a href="javascript:void(0);" id="rn_<?= $this->instanceID ?>_Button" class="rn_CommentAction rn_FlagAction" aria-haspopup="<?= count($this->data['js']['flags']) > 1 ? 'true' : 'false' ?>" title="<?= $this->helper->getFlagTitle() ?>">
        <rn:block id="preFlaggedLabel"/>
        <span class="rn_Flagged <?= $this->helper->getFlaggedClassNames() ?>">
            <?= $this->data['attrs']['label_already_flagged_button'] ?>
            <span class="rn_ScreenReaderOnly">
                <?= $this->helper->getFlagTitle() ?>
            </span>
        </span>
        <rn:block id="postFlaggedLabel"/>
    <? if (!$this->data['userFlag']): ?>
        <rn:block id="preUnflaggedLabel"/>
        <span class="rn_Unflagged">
            <?= $this->data['attrs']['label_button'] ?>
            <span class="rn_ScreenReaderOnly">
                <?= $this->helper->getFlagMenuScreenReaderText() ?>
            </span>
        </span>
        <rn:block id="postUnflaggedLabel"/>
    <? endif; ?>
    </a>
    <rn:block id="bottom"/>
</div>
