<span id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?> rn_Hidden">
    <button id="rn_<?=$this->instanceID;?>_Button" title="<?=$this->data['attrs']['label_tooltip_disconnect'];?>">
        <? if($this->data['attrs']['disconnect_icon_path'] !== ''):?>
            <rn:block id="preDisconnectIcon"/>
            <img src="<?=$this->data['attrs']['disconnect_icon_path']?>" alt="<?=$this->data['attrs']['label_tooltip_disconnect'];?>" />
            <rn:block id="postDisconnectIcon"/>
        <? endif;?> 
        <?=$this->data['attrs']['label_disconnect']?>
    </button>
</span>
