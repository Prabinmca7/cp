<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <div id="rn_<?= $this->instanceID ?>_Alert" role="alert" class="rn_ScreenReaderOnly"></div>
    <rn:block id="content">
    <div id="rn_<?=$this->instanceID;?>_Loading"></div>
    <div id="rn_<?= $this->instanceID ?>_Content" class="rn_Content">
        <? if (strtolower($this->data['js']['viewType']) === 'table'): ?>
            <? $type = ''; ?>
            <?= $this->render('table', array('data' => $this->data['recommendations'], 'type' => $type, 'fields' => $this->data['fields'], 'attrs' => $this->data['attrs'], 'url' => isset($this->data['js']['answerUrl']) ? $this->data['js']['answerUrl'] : '')) ?>
        <? endif; ?>
    </div>
    </rn:block>
    <rn:block id="bottom"/>
</div>
