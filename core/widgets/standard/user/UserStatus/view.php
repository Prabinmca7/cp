<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <span class="rn_StatusContainer" title="<?= $this->data['attrs']["label_{$this->data['status']}_tooltip"] ?>"><?= $this->data['attrs']["label_{$this->data['status']}"] ?></span>
    <rn:block id="bottom"/>
</div>