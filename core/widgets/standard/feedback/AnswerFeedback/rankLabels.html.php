<div id="rn_<?=$this->instanceID?>_RatingButtons" class="rn_RatingButtons">
    <? if ($this->data['attrs']['options_descending']): ?>
        <rn:block id="preRatingButtonsDescendingLoop"/>
        <? for($i = $this->data['attrs']['options_count']; $i > 0; $i--): ?>
            <rn:block id="topRatingButtonsDescendingLoop"/>
            <button id="rn_<?=$this->instanceID?>_RatingButton_<?=$i?>" type="button"><?=\RightNow\Utils\Config::getMessage($this->data['rateLabels'][$i])?></button>
            <rn:block id="bottomRatingButtonsDescendingLoop"/>
        <? endfor; ?>
        <rn:block id="postRatingButtonsDescendingLoop"/>
    <? else: ?>
        <rn:block id="preRatingButtonsAscendingLoop"/>
        <? for($i = 1; $i <= $this->data['attrs']['options_count']; $i++): ?>
            <rn:block id="topRatingButtonsAscendingLoop"/>
            <button id="rn_<?=$this->instanceID?>_RatingButton_<?=$i?>" type="button"><?=\RightNow\Utils\Config::getMessage($this->data['rateLabels'][$i])?></button>
            <rn:block id="bottomRatingButtonsAscendingLoop"/>
        <? endfor; ?>
        <rn:block id="postRatingButtonsAscendingLoop"/>
    <? endif; ?>
</div>
