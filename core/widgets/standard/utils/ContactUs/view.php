<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <div class="rn_Well">
        <rn:block id="preLegend"/>
        <h2><?= $this->data['attrs']['label_header'] ?></h2>
        <rn:block id="postLegend"/>
        <? foreach($this->data['channelData'] as $channel => $channelData): ?>
            <rn:block id="preChannel"/>
            <?= $this->render($channelData['view'], array('channel' => $channel, 'channelData' => $channelData)) ?>
            <rn:block id="postChannel"/>
        <? endforeach ?>
    </div>
    <rn:block id="bottom"/>
</div>
