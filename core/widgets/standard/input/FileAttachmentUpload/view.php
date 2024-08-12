<div id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <div id="rn_<?= $this->instanceID ?>_LabelContainer">
        <rn:block id="preFileInputLabel"/>
        <label for="rn_<?=$this->instanceID;?>_FileInput" id="rn_<?=$this->instanceID;?>_Label"><?=isset($this->data['attrs']['label_input']) ? $this->data['attrs']['label_input'] : null;?>
        <? if(isset($this->data['attrs']['label_input']) && isset($this->data['attrs']['min_required_attachments'] ) && $this->data['attrs']['label_input'] && $this->data['attrs']['min_required_attachments'] > 0):?>
            <?= $this->render('Partials.Forms.RequiredLabel') ?>
        <? endif;?>
        </label>
        <rn:block id="postFileInputLabel"/>
    </div>
    <rn:block id="preFileInput"/>
    <input name="file" id="rn_<?=$this->instanceID;?>_FileInput" type="file" aria-labelledby="rn_<?=$this->instanceID;?>_Label"/>
    <rn:block id="postFileInput"/>
    <? if($this->data['attrs']['loading_icon_path']):?>
    <img id="rn_<?=$this->instanceID;?>_LoadingIcon" class="rn_Hidden" alt="Loading" src="<?=$this->data['attrs']['loading_icon_path'];?>" />
    <? endif;?>
    <rn:block id="preStatus"/>
    <span id="rn_<?=$this->instanceID;?>_StatusMessage" aria-label="#rn:msg:STATUS_LBL#"></span>
    <rn:block id="postStatus"/>
    <span id="rn_<?=$this->instanceID;?>_ErrorMessage"></span>
    <rn:block id="bottom"/>
</div>
