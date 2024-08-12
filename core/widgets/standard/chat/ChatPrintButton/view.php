<span id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?> rn_Hidden">
    <rn:block id="top"/>
    <button id="rn_<?=$this->instanceID;?>_Button" title="<?=$this->data['attrs']['label_tooltip'];?>"><? if($this->data['attrs']['print_icon_path'] !== ''):?><img src="<?=$this->data['attrs']['print_icon_path']?>" alt="<?=$this->data['attrs']['label_tooltip'];?>" /><? endif; ?> <?=$this->data['attrs']['label_print']?></button>
    <rn:block id="bottom"/>
</span>
