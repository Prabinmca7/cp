<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <div id="rn_<?= $this->instanceID ?>_Alert" aria-live="polite" class="rn_ScreenReaderOnly"></div>

    <? if ($this->data['attrs']['label_heading']): ?>
        <h2>
            <?= $this->data['attrs']['label_heading'] ?>
        </h2>
    <? endif; ?>

    <rn:block id="content">
    <div id="rn_<?= $this->instanceID ?>_Content" class="rn_Content">
        <?= $this->render('Results', array('results' => $this->data['results']->results, 'query' => $this->data['results']->filters['query']['value'])) ?>
    </div>
    </rn:block>

    <div class="rn_AdditionalResults">
        <rn:block id="topAdditionalResults"/>
        <? if (isset($this->data['attrs']['more_link_url']) && $this->data['attrs']['more_link_url'] && $this->data['results']->total > $this->data['results']->filters['limit']['value']): ?>
        <rn:block id="preMoreLink"/>
        <a id="rn_<?= $this->instanceID ?>_MoreResultsLink" href="<?= $this->helper->constructMoreLink($this->data['attrs']['more_link_url'], $this->data['results']->filters) ?>">
            <?= $this->data['attrs']['label_heading'] ? ($this->data['attrs']['label_more_link'] . ' ' . $this->data['attrs']['label_heading']) : $this->data['attrs']['label_more_link'] ?>
        </a>
        <rn:block id="postMoreLink"/>
        <? endif; ?>
        <rn:block id="bottomAdditionalResults"/>
    </div>
    <rn:block id="bottom"/>

    <? if(isset($this->data['historyData'])): ?>
        <script type="text/json" <?= 'id="rn_' . $this->instanceID . '_HistoryData"'?>><?= $this->data['historyData'] ?></script>
    <? endif; ?>
</div>
