<li data-index="<?= $index + 1 ?>" class="rn_<?= $result->type ?>">
    <rn:block id="listItemContent">
    <div class="rn_Title">
        <h3><a href="<?= $result->url ?>" target="<?= $this->data['attrs']['target'] ?>"><?= $this->helper->formatSummary($result->text, ($this->data['attrs']['highlight'] && $query) ? $query : $this->data['attrs']['highlight'], $this->data['attrs']['link_truncate_size']) ?></a></h3>
    </div>
    <? if(!is_null($result->summary)): ?>
    <div class="rn_Summary">
        <?= $this->helper->formatSummary($result->summary, ($this->data['attrs']['highlight'] && $query) ? $query : $this->data['attrs']['highlight'], $this->data['attrs']['truncate_size']) ?>
    </div>
    <? endif; ?>
    </rn:block>
</li>
