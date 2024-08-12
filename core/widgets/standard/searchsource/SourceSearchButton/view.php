<div id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
        <rn:block id="preSubmit"/>
        <button type="submit" class="rn_SubmitButton" id="rn_<?=$this->instanceID;?>_SubmitButton">
            <span class="rn_ButtonText"><?= $this->data['attrs']['label_button'] ?></span>
        </button>
        <rn:block id="postSubmit"/>
    <rn:block id="bottom"/>
</div>
