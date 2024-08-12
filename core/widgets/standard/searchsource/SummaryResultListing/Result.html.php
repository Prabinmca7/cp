<rn:block id="listItemContent">
    <a href="<?= $result->url ?>" target="<?= $this->data['attrs']['target'] ?>"><?= $this->helper->formatSummary($result->text, ($this->data['attrs']['highlight'] && $query) ? $query : $this->data['attrs']['highlight'], $this->data['attrs']['truncate_size']) ?></a>
</rn:block>