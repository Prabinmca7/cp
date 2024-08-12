<span id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <?=$this->data['attrs']['label_message'];?>
    <rn:block id="preLoop"/>
    <? $count = 0; foreach($this->data['sets'] as $key => $set):?>
        <rn:block id="item">
        <? if(isset($set['current']) && $set['current']):?>
            <span class="rn_Bold"><?=$set['label'];?></span>
        <? else:?>
            <a href="/ci/redirect/pageSet/<?=urlencode($set['mapping'])?>/<?=htmlspecialchars($this->data['currentPage'], ENT_QUOTES, 'UTF-8')?>#rn:session#"><?=$set['label'];?></a>
        <? endif;?>
        </rn:block>

        <rn:block id="pageSetDivider"> 
        <?=((++$count < count($this->data['sets'])) ? '<span class="rn_PageSetDivider">|</span>' : '');?>
        </rn:block>
    <? endforeach;?>
    <rn:block id="postLoop"/>
    <rn:block id="bottom"/>
</span>
