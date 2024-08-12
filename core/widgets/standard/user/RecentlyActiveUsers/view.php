<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <div class="rn_RecentlyActiveUsers">
        <? if($this->data['users'] === null): ?>
            <?= $this->data['attrs']['label_no_recent_users']; ?>
        <? else: ?>
            <?= $this->render($this->data['attrs']['content_display_type']); ?>
        <? endif; ?>
    </div>
    <rn:block id="bottom"/>
</div>