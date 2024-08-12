<div id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?> rn_Hidden">
    <rn:block id="top"/>
    <rn:block id="preInstructions"/>
<? if(!isset($this->data['attrs']['is_persistent_chat']) || !$this->data['attrs']['is_persistent_chat']): ?>
    <label for="rn_<?=$this->instanceID;?>_Input" class="rn_Label"><?=$this->data['attrs']['label_send_instructions'];?></label>
<? endif; ?>
    <rn:block id="postInstructions"/>
    <rn:block id="preTextArea"/>
    <span>
    <? if(isset($this->data['attrs']['is_persistent_chat']) && $this->data['attrs']['is_persistent_chat']): ?>
        <textarea id="rn_<?=$this->instanceID;?>_Input" placeholder="<?=$this->data['attrs']['label_send_instructions'];?>" rows="2" cols="50"></textarea>
    <? else: ?>
        <textarea id="rn_<?=$this->instanceID;?>_Input" rows="3" cols="50"></textarea>
    <? endif; ?>
    </span>
    <rn:block id="postTextArea"/>
    <rn:block id="bottom"/>
</div>
