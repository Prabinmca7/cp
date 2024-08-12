<div id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <? if(isset($this->data['outOfChatHours'])):?>
           <rn:block id="preChatStatus"/>
           <rn:widget path="chat/ChatStatus"/>
           <rn:block id="postChatStatus"/>
           <rn:block id="preChatHours"/>
           <rn:widget path="chat/ChatHours"/>
           <rn:block id="postChatHours"/>
    <? else:?>
        <div id="rn_<?=$this->instanceID;?>_Connector">
            <rn:block id="preConnector"/>
            <div id="rn_<?=$this->instanceID;?>_ErrorLocation"></div>
            <div id="rn_<?=$this->instanceID;?>_ConnectionStatus">
                <?if($this->data['attrs']['loading_icon_path'] != ''):?>
                    <rn:block id="preLoadingIcon"/>
                    <img alt="" id="rn_<?=$this->instanceID;?>_ConnectingIcon" src="<?=$this->data['attrs']['loading_icon_path'];?>"/>&nbsp;
                    <rn:block id="postLoadingIcon"/>
                <?endif;?>
                <rn:block id="preConnectionStatusMessage"/>
                <span id="rn_<?=$this->instanceID;?>_Message"><?=$this->data['attrs']['label_connecting'];?></span>
                <rn:block id="postConnectionStatusMessage"/>
            </div>
            <rn:block id="postConnector"/>
        </div>
    <? endif;?>
    <rn:block id="bottom"/>
</div>
