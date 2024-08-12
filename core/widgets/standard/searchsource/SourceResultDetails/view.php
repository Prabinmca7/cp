<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <? if ($this->data['results']->size): ?>
        <? if ($this->data['results']->total && $this->data['attrs']['label_known_results']): ?>
        <? printf($this->data['attrs']['label_known_results'], $this->data['results']->offset + 1, $this->data['results']->offset + $this->data['results']->size, $this->data['results']->total) ?>
        <? else: ?>
        <? printf($this->data['attrs']['label_results'], $this->data['results']->offset + 1, $this->data['results']->offset + $this->data['results']->size) ?>
        <? endif; ?>
    <? endif; ?>
    <rn:block id="bottom"/>
</div>