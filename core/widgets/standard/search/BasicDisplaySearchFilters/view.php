<div id="rn_<?=$this->instanceID?>" class="<?= $this->classList ?>">
<rn:block id="top"/>
<? for($i = 0; $i < count($this->data['filters']); ++$i): ?>
        <? if (count($this->data['filters'][$i]['data']) > 0): ?>
            <rn:block id="preRemoveFilterLink"/>
            <a id="rn_<?=$this->instanceID .'_Remove_' . $i?>" href="<?=$this->getRemovalUrl($this->data['filters'][$i]['urlParameter'])?>">
                <?if($this->data['filters'][$i]['urlParameter'] === 'p'):?>
                <?=$this->data['attrs']['label_all_products']?>
                <?else:?>
                <?=$this->data['attrs']['label_all_categories']?>
                <?endif;?>
            </a> &gt;
            <rn:block id="postRemoveFilterLink"/>
        <? endif; ?>
        <?if(count($this->data['filters'][$i]['data'])):?>
        <rn:block id="preInnerLoop"/>
        <? foreach($this->data['filters'][$i]['data'] as $index => $filter): ?>
            <rn:block id="preFilterLink"/>
            <? if (isset($filter['linkUrl'])): ?>
                <a href="<?=$filter['linkUrl']?>" class="rn_FilterItem <?=$class?>" id="rn_<?=$this->instanceID?>_Filter<?=$filter['id']?>"><?=htmlspecialchars($filter['label'], ENT_QUOTES, 'UTF-8')?></a>
            <? else: ?>
                <?=htmlspecialchars($filter['label'], ENT_QUOTES, 'UTF-8')?>
            <? endif; ?>
            <rn:block id="postFilterLink"/>
            <?=($index === count($this->data['filters'][$i]['data']) - 1) ? '' : '&gt;'?>
        <? endforeach; ?>
        <rn:block id="postInnerLoop"/>
        <br/>
        <?endif;?>
<? endfor; ?>
<rn:block id="bottom"/>
</div>
