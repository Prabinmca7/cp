<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <rn:block id="preLoadingIcon"/>
    <div role="status" aria-live="assertive" class="rn_Loading rn_Hidden" id="rn_<?= $this->instanceID ?>_LoadingIcon">
        <span class="rn_ScreenReaderOnly">
            <?= \RightNow\Utils\Config::getMessage(LOADING_LBL) ?>
        </span>
    </div>
    <rn:block id="postLoadingIcon"/>
    <div class="rn_ActionMenu">
        <a href="javascript:void(0);" role="button" id="rn_<?= $this->instanceID ?>_Button" aria-label="<?= sprintf($this->data['attrs']['label_menu_accessibility'], $this->data['attrs']['label_action_menu']) ?>" title="<?= $this->data['attrs']['label_action_menu'] ?>">
            <span>
                <?= $this->data['attrs']['label_action_menu'] ?>
            </span>
        </a>
    </div>
    <rn:block id="bottom"/>
</div>
