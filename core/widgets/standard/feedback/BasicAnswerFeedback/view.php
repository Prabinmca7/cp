<div id="rn_<?=$this->instanceID?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <div id="rn_<?=$this->instanceID?>_AnswerFeedbackControl" class="rn_AnswerFeedbackControl">
        <rn:form action="/app/#rn:config:CP_ANSWERS_DETAIL_URL#/a_id/#rn:php:$this->data['js']['answerID']#" post_handler="#rn:php:$this->data['attrs']['post_request_handler']#">
            <input type="hidden" name="answerFeedback[OptionsCount]" value="<?=$this->data['attrs']['options_count']?>" />
            <input type="hidden" name="answerFeedback[Threshold]" value="<?=$this->data['attrs']['threshold']?>" />

            <? if ($this->data['attrs']['label_title']): ?>
                <h2><?=$this->data['attrs']['label_title']?></h2>
            <? endif; ?>
            <? if ($this->data['js']['buttonView']): ?>
                <input type="radio" id="rn_<?=$this->instanceID?>_RatingYesButton" name="answerRating" value="2" /><label for="rn_<?=$this->instanceID?>_RatingYesButton"><?=$this->data['attrs']['label_yes_button']?></label><br />
                <input type="radio" id="rn_<?=$this->instanceID?>_RatingNoButton" name="answerRating" value="1" /><label for="rn_<?=$this->instanceID?>_RatingNoButton"><?=$this->data['attrs']['label_no_button']?></label><br />
            <? else: ?>
                <? if ($this->data['attrs']['options_descending']): ?>
                    <rn:block id="preRatingButtonsLoop"/>
                    <? for($i = $this->data['attrs']['options_count']; $i > 0; $i--): ?>
                        <rn:block id="topRatingButtonsLoop"/>
                        <input type="radio" id="rn_<?=$this->instanceID?>_RatingButton_<?=$i?>" name="answerRating" value="<?=$i?>" /><label for="rn_<?=$this->instanceID?>_RatingButton_<?=$i?>"><?=\RightNow\Utils\Config::getMessage($this->data['rateLabels'][$i])?></label><br />
                        <rn:block id="bottomRatingButtonsLoop"/>
                    <? endfor; ?>
                    <rn:block id="postRatingButtonsLoop"/>
                <? else: ?>
                    <rn:block id="preRatingButtonsLoop"/>
                    <? for($i = 1; $i <= $this->data['attrs']['options_count']; $i++): ?>
                        <rn:block id="topRatingButtonsLoop"/>
                        <input type="radio" id="rn_<?=$this->instanceID?>_RatingButton_<?=$i?>" name="answerRating" value="<?=$i?>" /><label for="rn_<?=$this->instanceID?>_RatingButton_<?=$i?>"><?=\RightNow\Utils\Config::getMessage($this->data['rateLabels'][$i])?></label><br />
                        <rn:block id="bottomRatingButtonsLoop"/>
                    <? endfor; ?>
                    <rn:block id="postRatingButtonsLoop"/>
                <? endif; ?>
            <? endif; ?>
            <br />
            <rn:widget path="input/BasicFormSubmit" label_button="#rn:php:$this->data['attrs']['label_submit_button']#"/>
        </rn:form>
    </div>
</div>
