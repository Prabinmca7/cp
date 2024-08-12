<div id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?> rn_Hidden">
    <rn:block id="cancelButtonTop"/>
    <button id="rn_<?=$this->instanceID;?>_Button"  title="<?=$this->data['attrs']['label_tooltip'];?>"><? if($this->data['attrs']['cancel_icon_path'] !== ''):?><img src="<?=$this->data['attrs']['cancel_icon_path']?>" alt="<?=$this->data['attrs']['label_tooltip'];?>" /><? endif;?> <?=$this->data['attrs']['label_cancel']?></button>
    <rn:block id="cancelButtonBottom"/>
</div>
