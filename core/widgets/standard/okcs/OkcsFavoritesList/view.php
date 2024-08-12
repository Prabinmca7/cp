<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
        <div id="rn_<?= $this->instanceID ?>_Alert" role="alert" class="rn_ScreenReaderOnly"></div>
    <rn:block id="content">
        <? if($this->data['attrs']['view_type'] === 'table'): ?>
            <?= $this->render('table', array('data' => $this->data)); ?>
        <? else: ?>
            <?= $this->render('list', array('data' => $this->data)); ?>
        <? endif; ?>
    </rn:block>
    <rn:block id="bottom"/>
</div>