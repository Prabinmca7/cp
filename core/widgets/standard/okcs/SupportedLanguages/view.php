<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
        <a id="rn_<?= $this->instanceID ?>_Link" href="javascript:void(0);"><?=$this->data['attrs']['label_language']; ?></a>
        <div id="rn_<?= $this->instanceID ?>_Container" class="rn_Hidden" >
            <? if (count($this->data['availableLanguages']) > 0): ?>
                <? foreach($this->data['availableLanguages'] as $language): ?>
                    <? $decoded = urldecode($language['description']); ?>
                    <fieldset>
                        <label for="rn_<?= $this->instanceID ?>_<?=$language['code'];?>"><? echo \RightNow\Utils\Text::escapeHtml($decoded); ?></label>
                        <input id="rn_<?= $this->instanceID ?>_<?=$language['code'];?>" type="checkbox" value="<?=$language['code'];?>" <?=$language['selected'] ? "checked='checked'" : "";?> class="rn_SupportedLanguagesCheckbox">
                    </fieldset>
                <? endforeach;?>
            <? endif; ?>
        </div>
    <rn:block id="bottom"/>
</div>
