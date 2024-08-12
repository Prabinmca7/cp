<div class="<?= $this->classList ?>">
<rn:block id="top"/>
<? if ($this->data['attrs']['require_current_password']): ?>
    <div class="rn_PasswordInputCurrent">
        <rn:block id="preCurrentInput"/>
        <? if ($this->data['attrs']['label_current_password']): ?>
            <rn:block id="preCurrentLabel"/>
            <label for="rn_<?= $this->instanceID ?>_<?= $this->data['js']['name'] ?>_CurrentPassword" class="rn_Label">
            <?= $this->data['attrs']['label_current_password'] ?>
            </label><br/>
            <rn:block id="postCurrentLabel"/>
        <? endif; ?>
        <input type="password" id="rn_<?= $this->instanceID ?>_<?= $this->data['js']['name'] ?>_CurrentPassword" name="formData[<?= $this->data['inputName'] ?>#CurrentPassword]" class="rn_Password rn_Current" <?=($this->data['attrs']['disable_password_autocomplete']) ? 'autocomplete="off"' : ''?> />
        <rn:block id="postCurrentInput"/>
    </div>
<? endif; ?>
<? if ($this->data['attrs']['label_input']): ?>
    <rn:block id="preLabel"/>
    <label for="rn_<?= $this->instanceID ?>_<?= $this->data['js']['name'] ?>" class="rn_Label">
    <?= $this->data['attrs']['label_input'] ?>
    <? if ($this->data['attrs']['required']): ?>
        <rn:block id="preRequired"/>
            <?= $this->render('Partials.Forms.RequiredLabel') ?>
        <rn:block id="postRequired"/>
    <? endif; ?>
    </label><br/>
    <rn:block id="postLabel"/>
<? endif; ?>
<rn:block id="preInput"/>
    <input type="password" id="rn_<?= $this->instanceID ?>_<?= $this->data['js']['name'] ?>" name="formData[<?= $this->data['inputName'] ?>]" class="rn_Password" <?=($this->data['attrs']['disable_password_autocomplete']) ? 'autocomplete="off"' : ''?> />
<rn:block id="postInput"/>
<? if ($this->data['attrs']['require_validation']): ?>
    <div class="rn_PasswordInputValidate">
        <rn:block id="preValidateLabel"/>
        <label for="rn_<?= $this->instanceID ?>_<?= $this->data['js']['name'] ?>_Validate" class="rn_Label"><? printf($this->data['attrs']['label_validation'], $this->data['attrs']['label_input']) ?>
        <rn:block id="postValidateLabel"/>
        <? if ($this->data['attrs']['required']): ?>
            <rn:block id="preValidateRequired"/>
                <?= $this->render('Partials.Forms.RequiredLabel') ?>
            <rn:block id="postValidateRequired"/>
        <? endif; ?>
        </label><br/>
        <rn:block id="preValidateInput"/>
        <input type="password" id="rn_<?= $this->instanceID ?>_<?= $this->data['js']['name'] ?>_Validate" name="formData[<?= $this->data['inputName'] ?>#Validation]" class="rn_Password rn_Validation" <?=($this->data['attrs']['disable_password_autocomplete']) ? 'autocomplete="off"' : ''?> />
        <rn:block id="postValidateInput"/>
    </div>
<? endif; ?>
<rn:block id="bottom"/>
</div>