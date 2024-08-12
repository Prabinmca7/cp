<span id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?>">
    <a href="<?=$this->data['survey_url'];?>" target="<?=$this->data['attrs']['target'];?>">
    <? if($this->data['attrs']['icon_path']):?>
        <img src="<?=$this->data['attrs']['icon_path'];?>" alt="<?=$this->data['attrs']['label_icon_alt']?>"/>
    <?else:?>
        <?=$this->data['attrs']['link_text'];?>
    <? endif;?>
    </a>
</span>
