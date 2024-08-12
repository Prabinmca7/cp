<rn:block id="top">
    <div id="rn_<?=$this->instanceID;?>_ButtonContent" class="rn_ChatAttachFileButtonContent">
        <button id="rn_<?=$this->instanceID;?>_Button" title="<?=$this->data['attrs']['label_tooltip'];?>">
            <? if($this->data['attrs']['file_attach_icon_path']): ?>
                <img src="<?=$this->data['attrs']['file_attach_icon_path']?>" alt="<?=$this->data['attrs']['label_tooltip'];?>" />
            <? endif ?> 
            <?=$this->data['attrs']['label_file_attach']?>
        </button>
    </div>
    <form method="post" id="rn_<?=$this->instanceID;?>_Form" class="rn_ChatAttachFileButtonForm">
</rn:block>

<rn:block id="postStatus">
    </form>
</rn:block>
