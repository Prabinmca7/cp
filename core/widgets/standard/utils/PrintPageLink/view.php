<span id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <a onclick="window.print(); return false;" href="javascript:void(0);" title="<?=$this->data['attrs']['label_tooltip'];?>">
        <span><?=$this->data['attrs']['label_link'];?></span>
    </a>
    <rn:block id="bottom"/>
</span>
