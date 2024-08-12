<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <? if($this->data['attrs']['chart_title_location'] === 'top'): ?>
    <rn:block id="chartTitleTop">
        <div class="rn_ChartTitle"><?= $this->data['attrs']['label_chart_title']; ?></div>
    </rn:block>
    <? endif;?>
    <rn:block id="topChart"/>
    <div id="rn_<?= $this->instanceID ?>_CountChart" class="rn_CountChart"></div>
    <rn:block id="bottomChart"/>
    <? if($this->data['attrs']['chart_title_location'] === 'bottom'): ?>
    <rn:block id="chartTitleBottom">
        <div class="rn_ChartTitle"><?= $this->data['attrs']['label_chart_title']; ?></div>
    </rn:block>
    <? endif;?>
    <rn:block id="bottom"/>
</div>