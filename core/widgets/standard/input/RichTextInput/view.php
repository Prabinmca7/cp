<div class="<?= $this->classList ?>" id="rn_<?= $this->instanceID ?>">
    <rn:block id="top"/>
    <rn:block id="preLabel"/>
    <label id="rn_<?= $this->instanceID ?>_Label" class="rn_Label">
        <span class="rn_LabelInput">
            <?= $this->data['attrs']['label_input'] ?>
        </span>
        <? if ($this->data['attrs']['required']): ?>
            <rn:block id="preRequired"/>
            <span class="rn_Required">
                <?= \RightNow\Utils\Config::getMessage(FIELD_REQUIRED_MARK_LBL) ?>
            </span>
            <span class="rn_ScreenReaderOnly">
                <?= \RightNow\Utils\Config::getMessage(REQUIRED_LBL) ?>
            </span>
            <rn:block id="postRequired"/>
        <? endif; ?>
        <? if ($this->data['attrs']['hint']): ?>
            <span class="rn_ScreenReaderOnly"><?= $this->data['attrs']['hint'] ?></span>
        <? endif; ?>
    </label>
    <rn:block id="postLabel"/>
    <rn:block id="preInput"/>
    <div id="rn_<?= $this->instanceID ?>_Editor" class="rn_InputEditor">
        <textarea id="rn_<?= $this->instanceID ?>_ckeditor" name="<?= $this->data['attrs']['name'] ?>" class="rn_Hidden" ></textarea>
        <div class="rn_Loading" id="rn_<?= $this->instanceID ?>_LoadingIcon">
            <span class="rn_ScreenReaderOnly">
                <?= \RightNow\Utils\Config::getMessage(LOADING_EDITOR_LBL) ?>
            </span>
        </div>
    </div>
    <rn:block id="postInput"/>
    <div class="rn_HelpArea">
        <div class="rn_ContextualTip rn_Hidden">
            <p class="rn_HelpText"></p>
        </div>
        <div class="rn_Hidden rn_AdvancedHelp">
            <p></p>
        </div>
    </div>
    <? if ($this->data['attrs']['hint'] && $this->data['attrs']['always_show_hint']): ?>
        <rn:block id="preHint"/>
        <span class="rn_HintText"><?= $this->data['attrs']['hint'] ?></span>
        <rn:block id="postHint"/>
    <? endif; ?>
    <rn:block id="bottom"/>
</div>
