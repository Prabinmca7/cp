<div class="rn_<?= ucfirst($channel) ?>Link">
    <a href="<?= $channelData['url'] ?>"><?= $channelData['label'] ?></a>
    <? if ($channelData['description']): ?>
        <div class="rn_<?= ucfirst($channel) ?>Description">
            <?= $channelData['description'] ?>
        </div>
    <? endif ?>
</div>