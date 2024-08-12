<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
        <ul class="<?= $this->data['attrs']['list_display'] === 'horizontal' ? 'rn_Horizontal' : '' ?>">
            <? $defaultContentType = \RightNow\Utils\Text::escapeHtml($this->data['defaultContentType']);?>
            <? if($this->data['contentTypes'] === null): ?>
                <li class="rn_NoChannelsMsg"><?= $this->data['attrs']['label_no_content_type'] ?></li>
            <? else: ?>
            <? if($this->data['attrs']['display_all_content']): ?>
            <? $allContent = str_replace(' ', '-', $this->data['attrs']['label_display_all_content']); ?>
            <? $allContentClass = strcmp($this->data['defaultContentType'], $this->data['attrs']['label_display_all_content']) ? '' : 'rn_Selected' ?>
            <li><a id="rn_<?= $this->instanceID ?>_<?= $allContent ?>" data-id="<?= $allContent ?>" class="<?= $allContentClass ?>" href="javascript:void(0)"><?= \RightNow\Utils\Text::escapeHtml($this->data['attrs']['label_display_all_content']) ?></a></li>
            <? endif; ?>
            <?foreach ($this->data['contentTypes'] as $item):?>
                <? if(strlen($defaultContentType) === 0) { ?>
                    <? $defaultContentType = \RightNow\Utils\Text::escapeHtml($item->name);?>
                <? } ?>
                <?
                    $isContentTypeSelected = \RightNow\Utils\Text::escapeHtml(strtoupper($item->referenceKey)) === strtoupper($defaultContentType);
                    $selectedStyleClass = $isContentTypeSelected ? 'rn_Selected' : '';
                ?>
                <li><a id="rn_<?= $this->instanceID ?>_<?=$item->referenceKey;?>" data-id="<?=$item->referenceKey;?>" class="<?= $selectedStyleClass ?>" href="javascript:void(0)"><?= \RightNow\Utils\Text::escapeHtml($item->name); ?></a></li>
            <?endforeach;?>
            <? endif; ?>
        </ul>
    <rn:block id="bottom"/>
</div>