<div id="rn_<?= $this->instanceID; ?>_RatingMeter" class="rn_RatingMeter <?= isset($this->data['RatingMeterHidden']) ? $this->data['RatingMeterHidden'] : ''; ?>" role="application">
    <? if ($this->data['attrs']['options_descending']): ?>
        <rn:block id="preRatingMeterDescendingLoop"/>
        <? for($i = $this->data['attrs']['options_count']; $i > 0; $i--): ?>
             <rn:block id="topRatingMeterDescendingLoop"/>
             <? echo "<a id='rn_".$this->instanceID.'_RatingCell_'.$i."' href='javascript:void(0)' class='rn_RatingCell' title='".\RightNow\Utils\Config::getMessage($this->data['rateLabels'][$i])."' ".sprintf('><span class="rn_ScreenReaderOnly">'.$this->data['attrs']['label_accessible_option_description'], $i, $this->data['attrs']['options_count']).'</span>&nbsp;</a>'; ?>
             <rn:block id="bottomRatingMeterDescendingLoop"/>
        <? endfor; ?>
        <rn:block id="postRatingMeterDescendingLoop"/>
    <? else: ?>
        <rn:block id="preRatingMeterAscendingLoop"/>
        <? for($i = 1; $i <= $this->data['attrs']['options_count']; $i++): ?>
            <rn:block id="topRatingMeterAscendingLoop"/>
            <? echo "<a id='rn_".$this->instanceID.'_RatingCell_'.$i."' href='javascript:void(0)' class='rn_RatingCell' title='".\RightNow\Utils\Config::getMessage($this->data['rateLabels'][$i])."' ".sprintf('><span class="rn_ScreenReaderOnly">'.$this->data['attrs']['label_accessible_option_description'], $i, $this->data['attrs']['options_count']).'</span>&nbsp;</a>'; ?>
            <rn:block id="bottomRatingMeterAscendingLoop"/>
        <? endfor; ?>
        <rn:block id="postRatingMeterAscendingLoop"/>
    <? endif; ?>
</div>
