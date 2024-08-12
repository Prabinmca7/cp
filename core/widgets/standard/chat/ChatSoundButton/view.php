<span id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <span id="rn_<?=$this->instanceID;?>_On" class="rn_ChatSoundButton rn_Hidden">
        <rn:block id="preOnButton"/>
        <button id="rn_<?=$this->instanceID;?>_ButtonOn" title="<?=$this->data['attrs']['label_tooltip_sound_on'];?>">
            <? if($this->data['attrs']['sound_on_icon_path'] !== ''):?>
                <rn:block id="preOnButtonImage"/>
                <img src="<?=$this->data['attrs']['sound_on_icon_path']?>" alt="<?=$this->data['attrs']['label_tooltip_sound_on'];?>" />
                <rn:block id="postOnButtonImage"/>
            <? endif;?> 
            <?=$this->data['attrs']['label_sound_on']?>
        </button>
        <rn:block id="postOnButton"/>
    </span>
    <span id="rn_<?=$this->instanceID;?>_Off" class="rn_ChatSoundButton rn_Hidden">
        <rn:block id="preOffButton"/>
        <button id="rn_<?=$this->instanceID;?>_ButtonOff" title="<?=$this->data['attrs']['label_tooltip_sound_off'];?>">
            <? if($this->data['attrs']['sound_off_icon_path'] !== ''):?>
                <rn:block id="preOffButtonImage"/>
                <img src="<?=$this->data['attrs']['sound_off_icon_path']?>" alt="<?=$this->data['attrs']['label_tooltip_sound_off'];?>" />
                <rn:block id="postOffButtonImage"/>
            <? endif;?> <?=$this->data['attrs']['label_sound_off']?>
        </button>
        <rn:block id="postOffButton"/>
    </span>
    <rn:block id="bottom"/>
</span>