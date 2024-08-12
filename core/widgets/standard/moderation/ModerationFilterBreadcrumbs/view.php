<div id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <span class="rn_Heading"><?=$this->data['attrs']['label_title'];?></span>
    <rn:block id="preOuterLoop"/>
    <div id="rn_<?=$this->instanceID;?>_FilterContainer">
    <? for($i = 0; $i < count($this->data['js']['filters']); $i++):?>
        <div id="rn_<?=$this->instanceID . '_Filter_' . $i;?>" class="rn_Filter">
            <rn:block id="preRemoveFilterLink"/>
            <span class="rn_FilterItem rn_Selected"><?= $this->data['js']['filters'][$i]['label']; ?>
                <a id="rn_<?=$this->instanceID . '_Remove_' . $i?>" title="<?=$this->data['attrs']['label_filter_remove'];?>" tabindex="0" href="javascript:void(0);"></a>
            </span>
            <rn:block id="postRemoveFilterLink"/>
            <rn:block id="preInnerLoop"/>
            <? foreach($this->data['js']['filters'][$i]['data'] as $index => $filter):?>
                <rn:block id="preFilterLink"/>
                <span id="rn_<?= $this->instanceID . '_Filter_' . $i . '_' . $filter['id']; ?>"  class="rn_FilterItem rn_FilterItem_<?=  ucfirst($this->data['js']['filters'][$i]['urlParameter'] ?: '0') ?>">
                    <?= \RightNow\Utils\Text::escapeHtml($filter['label']); ?>
                </span>
                <rn:block id="postFilterLink"/>
            <? endforeach;?>
            <rn:block id="postInnerLoop"/>
        </div>
    <? endfor;?>
    </div>
    <rn:block id="postOuterLoop"/>
    <rn:block id="bottom"/>
</div>
