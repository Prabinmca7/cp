<div id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
<? if ($this->data['attrs']['label']): ?>
    <rn:block id="label">
    <span class="rn_DataLabel"><?=$this->data['attrs']['label'];?></span>
    </rn:block>
<? endif; ?>
    <div class="rn_DataValue<?=$this->data['wrapClass']?>">
        <rn:block id="preList"/>
        <ul>
        <? foreach($this->data['value'] as $item):?>
            <rn:block id="listItem">
            <li>
            <?= str_repeat('&nbsp;&nbsp;', $item['Depth']) ?>
            <?=\RightNow\Utils\Text::escapeHtml($item['Name']);?>
            </li>
            </rn:block>
        <? endforeach; ?>
        </ul>
        <rn:block id="postList"/>
    </div>
    <rn:block id="bottom"/>
</div>
