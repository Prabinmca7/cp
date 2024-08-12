<span id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?> rn_Hidden" title="<?=$this->data['attrs']['label_tooltip'];?>">
    <rn:block id="top"/>
    <button id="rn_<?=$this->instanceID;?>_Button"><? if($this->data['attrs']['send_icon_path'] !== ''):?><img src="<?=$this->data['attrs']['send_icon_path']?>" alt="<?=$this->data['attrs']['label_tooltip'];?>" /><? endif;?> <?=$this->data['attrs']['label_send']?></button>
    <rn:block id="bottom"/>
</span>
