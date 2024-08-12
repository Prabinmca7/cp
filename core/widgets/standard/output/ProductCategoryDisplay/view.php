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
            <? $value = htmlspecialchars($item['Name'], ENT_QUOTES, 'UTF-8'); ?>
            <? if ($this->data['attrs']['report_page_url']): ?>
                <a href="<?=$this->data['attrs']['report_page_url'] . '/' . $this->data['filterKey'] . '/' . $item['ID'] . $this->data['appendedParameters'];?>"><?=$value;?></a>
            <? else: ?>
                <?=$value;?>
            <? endif; ?>
            </li>
            </rn:block>
        <? endforeach; ?>
        </ul>
        <rn:block id="postList"/>
    </div>
    <rn:block id="bottom"/>
</div>
