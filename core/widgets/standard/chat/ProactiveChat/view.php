<div id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?> rn_Hidden" >
    <rn:block id="top"/>
    <div id="rn_<?=$this->instanceID;?>_ProactiveChatBox" class="rn_ProactiveChatBox">
        <rn:block id="proactiveChatBoxTop"/>
        <div id="rn_<?=$this->instanceID;?>_ProactiveChatBoxContent" class="rn_ProactiveChatBoxContent">
        </div>
        <div id="rn_<?=$this->instanceID;?>_ProactiveChatBoxDescription">
            <?= $this->data['attrs']['label_chat_question']?>
        </div>
        <rn:block id="proactiveChatBoxBottom"/>
    </div>
    <rn:block id="bottom"/>
</div>
