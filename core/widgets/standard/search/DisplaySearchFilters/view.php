<div id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?>">
<rn:block id="top"/>
<span class="rn_Heading"><?=$this->data['attrs']['label_title'];?></span>
<rn:block id="preOuterLoop"/>
<div id="rn_<?=$this->instanceID;?>_FilterContainer">
<? for($i = 0; $i < count($this->data['js']['filters']); $i++):?>
    <div id="rn_<?=$this->instanceID . '_Filter_' . $i;?>" class="rn_Filter">
        <div class="rn_Label">
            <?=$this->data['js']['filters'][$i]['label'];?>
            <rn:block id="preRemoveFilterLink"/>
            <a id="rn_<?=$this->instanceID .'_Remove_' . $i?>" title="<?= sprintf($this->data['attrs']['label_filter_remove'], $this->data['js']['filters'][$i]['label']);?>" href="javascript:void(0);">
                <? if(!$this->data['attrs']['show_filter_label']): ?>
                    <span class="rn_ScreenReaderOnly"><?= sprintf($this->data['attrs']['label_filter_remove'], $this->data['js']['filters'][$i]['label']);?></span>
                <? else: ?>
                    <?= sprintf($this->data['attrs']['label_filter_remove'], $this->data['js']['filters'][$i]['label']);?>
                <? endif; ?>
            </a>
            <rn:block id="postRemoveFilterLink"/>
        </div>
        <rn:block id="preInnerLoop"/>
        <? foreach($this->data['js']['filters'][$i]['data'] as $index => $filter):?>
            <? $class = ($index === count($this->data['js']['filters'][$i]['data']) - 1) ? 'rn_Selected' : ''; ?>
            <rn:block id="preFilterLink"/>
                <? if ($index === count($this->data['js']['filters'][$i]['data']) - 1): ?>
                    <span id="rn_<?=$this->instanceID;?>_Filter<?=$filter['id']?>" class="rn_FilterItem <?=$class;?>" id="rn_<?=$this->instanceID;?>_Filter<?=$filter['id']?>"><?=htmlspecialchars($filter['label'], ENT_QUOTES, 'UTF-8');?></span>
                <? else: ?>
                    <a href="<?=$filter['linkUrl'];?>" class="rn_FilterItem <?=$class;?>" id="rn_<?=$this->instanceID;?>_Filter<?=$filter['id']?>"><?=htmlspecialchars($filter['label'], ENT_QUOTES, 'UTF-8');?></a>
                <? endif; ?>
            <rn:block id="postFilterLink"/>
            <span class="rn_Separator <?=$class;?>"></span>
        <? endforeach;?>
        <rn:block id="postInnerLoop"/>
    </div>
<? endfor;?>
</div>
<rn:block id="postOuterLoop"/>
<rn:block id="bottom"/>
</div>
