<? if ($this->data['readOnly']): ?>
    <rn:block id="preReadOnlyField"/>
    <rn:widget path="output/FieldDisplay" label="#rn:php:$this->data['attrs']['label_input']#" left_justify="true" sub_id="readOnlyField"/>
    <rn:block id="postReadOnlyField"/>
<? else: ?>
<div class="<?= $this->classList ?>">
<rn:block id="top"/>
<? if ($this->data['attrs']['label_input']): ?>
    <rn:block id="preLabel"/>
    <label for="rn_<?= $this->instanceID ?>_<?= $this->data['js']['name'] ?>" class="rn_Label">
    <?= $this->data['attrs']['label_input'] ?>
    <? if ($this->data['attrs']['required']): ?>
        <rn:block id="preRequired"/>
            <?= $this->render('Partials.Forms.RequiredLabel') ?>
        <rn:block id="postRequired"/>
    <? endif; ?>
    <? if ($this->data['attrs']['hint']): ?>
        <?= $this->data['attrs']['hint_separator'] . ' ' . $this->data['attrs']['hint'] ?>
    <? endif; ?>
    </label><br/>
    <rn:block id="postLabel"/>
<? endif; ?>
<? if ($this->data['displayType'] === 'Textarea'): ?>
    <rn:block id="preInput"/>
    <textarea id="rn_<?= $this->instanceID ?>_<?= $this->data['js']['name'] ?>" class="rn_TextArea" rows="5" cols="20" name="formData[<?= $this->data['inputName'] ?>]"><?= $this->data['value'] ?></textarea>
    <rn:block id="postInput"/>
<? else: ?>
    <rn:block id="preInput"/>
    <input type="text" id="rn_<?=$this->instanceID?>_<?=$this->data['js']['name']?>" name="formData[<?= $this->data['inputName'] ?>]" class="rn_<?=$this->data['displayType']?>" <?if($this->data['value'] !== null && $this->data['value'] !== '') echo "value='{$this->data['value']}'";?> />
    <rn:block id="postInput"/>
    <? if ($this->data['attrs']['require_validation']): ?>
    <div class="rn_TextInputValidate">
        <rn:block id="preValidateLabel"/>
        <br/>
        <label for="rn_<?= $this->instanceID ?>_<?= $this->data['js']['name'] ?>_Validate" class="rn_Label"><?printf($this->data['attrs']['label_validation'], $this->data['attrs']['label_input']) ?>
        <rn:block id="postValidateLabel"/>
        <? if ($this->data['attrs']['required']): ?>
            <rn:block id="preValidateRequired"/>
                <?= $this->render('Partials.Forms.RequiredLabel') ?>
            <rn:block id="postValidateRequired"/>
        <? endif; ?>
        </label><br/>
        <rn:block id="preValidateInput"/>
        <input type="text" id="rn_<?= $this->instanceID ?>_<?= $this->data['js']['name'] ?>_Validate" name="<?= $this->data['inputName'] ?>#Validation" class="rn_<?=$this->data['displayType']?> rn_Validation" value="<?= $this->data['value'] ?>"/>
        <rn:block id="postValidateInput"/>
    </div>
   <? endif; ?>
<? endif; ?>
<? if ($this->data['mask_hint']): ?>
    <div>
    <rn:block id="preMaskHint"/>
    <?=$this->data['mask_hint'];?>
    <rn:block id="postMaskHint"/>
    </div>
<? endif; ?>
<rn:block id="bottom"/>
</div>
<? endif; ?>
