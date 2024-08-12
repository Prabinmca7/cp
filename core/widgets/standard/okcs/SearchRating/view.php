<div id="rn_<?=$this->instanceID;?>" class="<?=$this->classList;?>">
    <? if (($this->data['attrs']['toggle_title']) && ($this->data['js']['searchFlag'])) :?>
    <h2 id="rn_SearchRatingHeader"><?= $this->data['attrs']['label_search_rating'] ?></h2>
    <? endif ?>
    <rn:block id="top"/>
    <div id="rn_<?=$this->instanceID;?>_Alert" role="alert" class="rn_ScreenReaderOnly"></div>
    <div id="rn_<?=$this->instanceID;?>_Content" class="<?=$this->data['js']['searchFlag'] === true ? "rn_Content" : "rn_Hidden" ?>">
        <rn:block id="topContent"/>
            <form>
                <div id="rn_<?=$this->instanceID;?>_SearchRating" class="rn_SearchRatingDiv">
                    <div class="rn_SearchRatingTitle"><?= $this->data['attrs']['label_title'] ?></div>
                    <? for ($index = 1; $index <= $this->data['attrs']['max_rating_count']; $index++) { ?>
                        <? $buttonText = sprintf($this->data['attrs']['label_rating'], $index, $this->data['attrs']['max_rating_count']); ?>
                        <button id="rn_<?=$this->instanceID?>_Rating_<?=$index?>" class="rn_Rating" data-rating="<?=$index?>" title="<?=$buttonText?>" type="button"><span class="rn_ScreenReaderOnly"><?=$buttonText?></span></button>
                    <? } ?>
                </div>
                <div id="rn_<?=$this->instanceID;?>_SearchComment" class="rn_SearchCommentDiv rn_Hidden">
                    <label for="rn_<?=$this->instanceID;?>_FeedbackMessage" class="rn_ScreenReaderOnly"><?= $this->data['attrs']['label_textarea_screenreader'] ?></label>
                    <textarea class="rn_SearchTextArea" id="rn_<?=$this->instanceID;?>_FeedbackMessage" aria-label="<?= $this->data['attrs']['label_textarea_screenreader'] ?>"></textarea>
                    <div class="rn_SubmitButtonDiv">
                        <label for="rn_<?=$this->instanceID;?>_SubmitButton" class="rn_ScreenReaderOnly"><?= $this->data['attrs']['label_button_screenreader'] ?></label>
                        <input type="submit" id="rn_<?=$this->instanceID;?>_SubmitButton" value="<?= $this->data['attrs']['label_button'] ?>" aria-label="<?= $this->data['attrs']['label_button_screenreader'] ?>"/>
                        <span id="rn_<?= $this->instanceID ?>_StatusMessage" class="rn_Hidden"><?= $this->data['attrs']['label_submitting_message'] ?></span>
                    </div>
                </div>
            </form>
        <div id="rn_<?=$this->instanceID;?>_ThanksMessage" class="rn_HiddenMessage rn_Hidden"><?= $this->data['attrs']['label_thanks_msg'] ?></div>
    </div>
    <rn:block id="bottom"/>
</div>
