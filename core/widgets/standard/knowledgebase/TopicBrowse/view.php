<div id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <? if($this->data['attrs']['label_description']):?>
    <rn:block id="preLabel"/>
    <div id="rn_<?=$this->instanceID;?>_Label" class="rn_TopicBrowseLabel"><?=$this->data['attrs']['label_description']?></div>
    <rn:block id="postLabel"/>
    <?endif;?>
    <div id="rn_<?=$this->instanceID;?>_Loading" class="rn_Loading"></div>
    <rn:block id="preTopics"/>
    <div id="rn_<?=$this->instanceID;?>_Topics" class="rn_Topics rn_Hidden"><? /*Topic hierarchy is created here*/?></div>
    <rn:block id="postTopics"/>
    <rn:block id="bottom"/>
</div>
