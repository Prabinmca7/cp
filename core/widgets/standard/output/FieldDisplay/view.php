<div  id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <? if ($this->data['attrs']['label']): ?>
        <rn:block id="label">
        <span class="rn_DataLabel"><?=$this->data['attrs']['label'];?> </span>
        </rn:block>
    <? endif; ?>
    <div class="rn_DataValue<?=$this->data['wrapClass']?>">
        <rn:block id="value">
        <?=$this->data['value']?>
        </rn:block>
    </div>
    <rn:block id="bottom"/>
</div>
