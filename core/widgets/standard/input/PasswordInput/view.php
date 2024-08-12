<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
<rn:block id="top"/>
<? if ($this->data['attrs']['require_current_password']): ?>
    <div class="rn_PasswordInputCurrent">
        <rn:block id="preCurrentInput"/>
        <? if ($this->data['attrs']['label_current_password']): ?>
            <rn:block id="preCurrentLabel"/>
            <label for="rn_<?= $this->instanceID ?>_<?= $this->data['js']['name'] ?>_CurrentPassword" id="rn_<?= $this->instanceID ?>_Current_Label" class="rn_Label">
            <?= $this->data['attrs']['label_current_password'] ?>
            </label>
            <rn:block id="postCurrentLabel"/>
        <? endif; ?>
        <input type="password" id="rn_<?= $this->instanceID ?>_<?= $this->data['js']['name'] ?>_CurrentPassword" name="<?= $this->data['inputName'] ?>_CurrentPassword" class="rn_Password rn_Current" <?= $this->outputConstraints(); ?> />
        <rn:block id="postCurrentInput"/>
    </div>
<? endif; ?>
    <div id="rn_<?= $this->instanceID ?>_LabelContainer">
        <rn:block id="preLabel"/>
        <label for="rn_<?= $this->instanceID ?>_<?= $this->data['js']['name'] ?>" id="rn_<?= $this->instanceID ?>_Label" class="rn_Label">
        <?= $this->data['attrs']['label_input'] ?>
        <? if ($this->data['attrs']['label_input'] && $this->data['attrs']['required']): ?>
            <rn:block id="preRequired"/>
            <span class="rn_Required"> <?= \RightNow\Utils\Config::getMessage(FIELD_REQUIRED_MARK_LBL) ?></span><span class="rn_ScreenReaderOnly"> <?= \RightNow\Utils\Config::getMessage(REQUIRED_LBL) ?></span>
            <rn:block id="postRequired"/>
        <? endif; ?>
        </label>
        <rn:block id="postLabel"/>
    </div>
<rn:block id="preInput"/>
    <input type="password" aria-describedby="<?if (isset($this->data['js']['requirements']) && $this->data['js']['requirements'] && $this->data['js']['requirements']['length']) echo "rn_{$this->instanceID}_PasswordLength ";?>rn_<?= $this->instanceID ?>_PasswordHelp" id="rn_<?= $this->instanceID ?>_<?= $this->data['js']['name'] ?>" name="<?= $this->data['inputName'] ?>" class="rn_Password" <?= $this->outputConstraints(); ?> />
    <? if (isset($this->data['js']['requirements'] ) && $this->data['js']['requirements'] && $this->data['js']['requirements']['length']): ?>
        <span class="rn_PasswordLength" id="rn_<?= $this->instanceID ?>_PasswordLength"><? printf(\RightNow\Utils\Config::getMessage(MUST_BE_LEAST_PCT_S_CHARACTERS_MSG), $this->data['js']['requirements']['length']['count']) ?></span>
    <? endif; ?>
    <div id="rn_<?= $this->instanceID ?>_PasswordHelp"><? /* Password validation overlay inserted here */ ?></div>
<rn:block id="postInput"/>
<? if ($this->data['attrs']['require_validation']): ?>
    <div class="rn_PasswordInputValidate">
        <div id="rn_<?= $this->instanceID ?>_LabelValidateContainer">
            <rn:block id="preValidateLabel"/>
            <label for="rn_<?= $this->instanceID ?>_<?= $this->data['js']['name'] ?>_Validate" id="rn_<?= $this->instanceID ?>_<?= $this->data['js']['name'] ?>_LabelValidate" class="rn_Label"><? printf($this->data['attrs']['label_validation'], $this->data['attrs']['label_input']) ?>
            <? if ($this->data['attrs']['required']): ?>
                <rn:block id="preValidateRequired"/>
                <span class="rn_Required"><?= \RightNow\Utils\Config::getMessage(FIELD_REQUIRED_MARK_LBL) ?></span><span class="rn_ScreenReaderOnly"> <?= \RightNow\Utils\Config::getMessage(REQUIRED_LBL) ?></span>
                <rn:block id="postValidateRequired"/>
            <? endif; ?>
            </label>
            <rn:block id="postValidateLabel"/>
        </div>
        <rn:block id="preValidateInput"/>
        <input type="password" aria-describedby="rn_<?= $this->instanceID ?>_PasswordValidationHelp" id="rn_<?= $this->instanceID ?>_<?= $this->data['js']['name'] ?>_Validate" name="<?= $this->data['inputName'] ?>_Validation" class="rn_Password rn_Validation" <?= $this->outputConstraints(); ?> />
        <div id="rn_<?= $this->instanceID ?>_PasswordValidationHelp" class="rn_ScreenReaderOnly"><? printf(\RightNow\Utils\Config::getMessage(MUST_MATCH_PCT_S_MSG), $this->data['attrs']['label_input']) ?></div>
        <rn:block id="postValidateInput"/>
    </div>
<? endif; ?>
<rn:block id="bottom"/>
</div>