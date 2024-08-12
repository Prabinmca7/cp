<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <rn:block id="preStatus"/>
    <? $status = $this->data['state']['status'] ?>
    <span class="rn_StatusContainer" title="<?= $this->data['attrs']["label_{$status}_tooltip"] ?>">
        <? if ($this->data['state']['locked']): ?>
            <rn:block id="preLockedIcon"/>
            <span class="rn_LockedContainer" title="<?= $this->data['attrs']['label_locked_tooltip'] ?>">
                <span class="rn_ScreenReaderOnly"><?= $this->data['attrs']['label_locked'] ?></span>
            </span>
            <rn:block id="postLockedIcon"/>
        <? endif; ?>
        <?= $this->data['attrs']["label_{$status}"] ?>
    </span>
    <rn:block id="postStatus"/>
    <rn:block id="bottom"/>
</div>
