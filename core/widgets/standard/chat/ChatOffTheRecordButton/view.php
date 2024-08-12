<span id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?> rn_Hidden">
    <rn:block id="top"/>
    <button id="rn_<?=$this->instanceID;?>_Button" title="<?=$this->data['attrs']['label_tooltip'];?>">
        <? if($this->data['attrs']['off_the_record_icon_path'] != ''): ?>
            <rn:block id="preIcon"/>
            <img src="<?=$this->data['attrs']['off_the_record_icon_path']?>" alt="<?=$this->data['attrs']['label_tooltip'];?>" />
            <rn:block id="postIcon"/>
        <? endif; ?> 
        <?=$this->data['attrs']['label_off_the_record']?>
    </button>
    <rn:block id="bottom"/>
</span>
