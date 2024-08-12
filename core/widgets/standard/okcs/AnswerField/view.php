<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>" >
    <? $index = null ?>
    <? if($this->data['attrs']['answer_key'] !== '') : ?>
        <?= $this->render('metadata', $this->data['fieldData']) ?>
    <? else : ?>
        <?= $this->render('attribute', $this->data['fieldData'], isset($index) ? $index : null) ?>
        <? if($this->data['attrs']['type'] !== 'NODE') : ?>
            <?= $this->render('attributeValue', $this->data['fieldData']) ?>
        <? endif; ?>
    <? endif; ?>
</div>
