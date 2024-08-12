<? if ($this->data['readOnly']): ?>
    <rn:block id="preReadOnlyField"/>
    <rn:widget path="output/FieldDisplay" label="#rn:php:$this->data['attrs']['label_input']#" left_justify="true" sub_id="readOnlyField"/>
    <rn:block id="postReadOnlyField"/>
<? else: ?>
<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
<rn:block id="top"/>
    <div id="rn_<?= $this->instanceID ?>_LabelContainer">
        <rn:block id="preLabel"/>
        <label for="rn_<?= $this->instanceID ?>_<?= $this->data['js']['name'] ?>" id="rn_<?= $this->instanceID ?>_Label" class="rn_Label">
        <?= $this->data['attrs']['label_input'] ?>
        <? if ($this->data['attrs']['label_input'] && $this->data['attrs']['required']): ?>
            <rn:block id="preRequired"/>
                <?= $this->render('Partials.Forms.RequiredLabel') ?>
            <rn:block id="postRequired"/>
        <? endif; ?>
        <? if (isset($this->data['js']['mask']) && $this->data['js']['mask']): ?>
            <span class="rn_ScreenReaderOnly"><?= $this->data['attrs']['tooltip_accessibility_message'] ?></span>
        <? endif; ?>
        <? if (isset($this->data['attrs']['hint']) && $this->data['attrs']['hint']): ?>
            <span class="rn_ScreenReaderOnly"><?= $this->data['attrs']['hint'] ?></span>
        <? endif; ?>
        </label>
        <rn:block id="postLabel"/>
    </div>
<? if ($this->data['displayType'] === 'Textarea'): ?>
<rn:block id="preInput"/>
    <textarea id="rn_<?= $this->instanceID ?>_<?= $this->data['js']['name'] ?>" class="rn_TextArea" rows="7" cols="60" name="<?= $this->data['inputName'] ?>" <?= $this->outputConstraints(); ?> ><?= $this->data['value'] ?></textarea>
<rn:block id="postInput"/>
<? if ($this->data['attrs']['hint'] && $this->data['attrs']['always_show_hint']): ?>
    <rn:block id="preHint"/>
    <span class="rn_HintText" aria-hidden="true"><?= $this->data['attrs']['hint'] ?></span>
    <rn:block id="postHint"/>
<? endif; ?>
<? else: ?>
<rn:block id="preInput"/>
    <input type="<?=$this->data['inputType']?>" id="rn_<?=$this->instanceID?>_<?=$this->data['js']['name']?>" name="<?= $this->data['inputName'] ?>" class="rn_<?=$this->data['displayType']?>" <?=$this->outputConstraints();?> <?if($this->data['value'] !== null && $this->data['value'] !== '') echo "value='{$this->data['value']}'";?> />
<rn:block id="postInput"/>
<? if (isset($this->data['attrs']['hint']) && $this->data['attrs']['hint'] && $this->data['attrs']['always_show_hint']): ?>
    <rn:block id="preHint"/>
    <span class="rn_HintText" aria-hidden="true"><?= $this->data['attrs']['hint'] ?></span>
    <rn:block id="postHint"/>
<? endif; ?>
    <? if (isset($this->data['attrs']['require_validation']) && $this->data['attrs']['require_validation']): ?>
    <div class="rn_TextInputValidate">
        <div id="rn_<?= $this->instanceID ?>_LabelValidateContainer">
            <rn:block id="preValidateLabel"/>
            <label for="rn_<?= $this->instanceID ?>_<?= $this->data['js']['name'] ?>_Validate" id="rn_<?= $this->instanceID ?>_<?= $this->data['js']['name'] ?>_LabelValidate" class="rn_Label"><?printf($this->data['attrs']['label_validation'], $this->data['attrs']['label_input']) ?>
            <? if ($this->data['attrs']['required']): ?>
                <rn:block id="preValidateRequired"/>
                    <?= $this->render('Partials.Forms.RequiredLabel') ?>
                <rn:block id="postValidateRequired"/>
            <? endif; ?>
            </label>
            <rn:block id="postValidateLabel"/>
        </div>
        <rn:block id="preValidateInput"/>
        <input type="<?= $this->data['inputType'] ?>" id="rn_<?= $this->instanceID ?>_<?= $this->data['js']['name'] ?>_Validate" name="<?= $this->data['inputName'] ?>_Validation" class="rn_<?=$this->data['displayType']?> rn_Validation" <?= $this->outputConstraints(); ?> value="<?= $this->data['value'] ?>"/>
        <rn:block id="postValidateInput"/>
    </div>
   <? endif; ?>
<? endif; ?>
<rn:block id="bottom"/>
</div>
<? endif; ?>
