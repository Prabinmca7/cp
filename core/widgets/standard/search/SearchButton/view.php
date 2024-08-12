<div id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <? if ($this->data['attrs']['icon_path']): ?>
        <rn:block id="preImage"/>
        <input type="image" class="rn_SubmitImage" id="rn_<?=$this->instanceID;?>_SubmitButton" src="<?=$this->data['attrs']['icon_path'];?>" alt="<?=$this->data['attrs']['icon_alt_text'];?>" title="<?=$this->data['attrs']['label_button'];?>"/>
        <rn:block id="postImage"/>
    <? else: ?>
        <rn:block id="preSubmit"/>
        <button type="submit" class="rn_SubmitButton" id="rn_<?=$this->instanceID;?>_SubmitButton">
            <span class="rn_ButtonText"><?= $this->data['attrs']['label_button'] ?></span>
        </button>
        <rn:block id="postSubmit"/>
    <? endif;?>
    <? if (isset($this->data['isIE']) && $this->data['isIE']): ?>
        <label for="rn_<?=$this->instanceID;?>_HiddenInput" class="rn_Hidden">&nbsp;</label>
        <input id="rn_<?=$this->instanceID;?>_HiddenInput" type="text" class="rn_Hidden" disabled="disabled"/>
    <? endif; ?>
    <rn:block id="bottom"/>
</div>
