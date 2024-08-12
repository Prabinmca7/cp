<div id="rn_<?=$this->instanceID;?>" class="<?=$this->classList;?>">
    <rn:block id="top"/>
    <div id="rn_<?=$this->instanceID;?>_Alert" role="alert" class="rn_ScreenReaderOnly"></div>
    <div id="rn_<?=$this->instanceID;?>_Content" class="rn_Content">
        <rn:block id="topContent"/>
        <? if (isset($this->data['ratingData']['questions']) && is_array($this->data['ratingData']['questions']) && count($this->data['ratingData']['questions']) > 0) : ?>
                <div class="rn_SectionTitle"></div>
                <? foreach ($this->data['ratingData']['questions'] as $question): ?>
                    <div class="rn_Well">
                        <h3><?=$question->name;?></h3>
                        <?
                            $answerLength = count($question->answers);
                            $starRateVal = explode("|", $this->data['attrs']['display_stars']);
                            $index = 1;
                            if (in_array($answerLength, $starRateVal)) :
                                $answers = $this->sortAnswer($question->answers);
                            else :
                                $answers = $question->answers;
                            endif;
                        ?>
                        <div>
                            <? foreach ($answers as $key => $answer): ?>
                                <? if (in_array($answerLength, $starRateVal)) : ?> 
                                    <? if (!$this->data['attrs']['label_rating']) : ?>
                                        <? $buttonText = $answer->name; ?>
                                    <? else : ?>
                                        <? $buttonText = sprintf($this->data['attrs']['label_rating'], $key + 1, $answerLength); ?>
                                    <? endif; ?>
                                    <button id="rn_<?=$this->instanceID?>_Rating_<?=$index?>" class="rn_Rating rn_StarRatingInput" data-id="<?=$index?>" title="<?=$buttonText?>" type="button" data-value="<?=$answer->numberValue?>" data-rating="<?= $this->data['ratingData']['surveyRecordID'] ?>:<?= $answer->recordId ?>:<?= $this->data['ratingData']['contentID'] ?>" data-maxRating="<?=count($answers)?>"><span class="rn_ScreenReaderOnly"><?=$buttonText?></span></button>
                                <? else : ?>
                                    <div>
                                        <input id="rn_<?=$this->instanceID?>_Rating_<?=$index?>" type="radio" class="rn_Rating rn_RatingInput" name="survey" data-value="<?=$answer->numberValue?>" data-rating="<?=$this->data['ratingData']['surveyRecordID']?>:<?=$answer->recordId;?>:<?=$this->data['ratingData']['contentID']?>" data-maxRating="<?=count($answers)?>"/>
                                        <label for="rn_<?=$this->instanceID?>_Rating_<?=$index?>" class="rn_RatingLabel"><?=$answer->name?></label>
                                    </div>
                                <? endif; ?>
                                <? $index++; ?>
                            <? endforeach; ?>
                        </div>
                        <div id="rn_<?=$this->instanceID;?>_DocumentComment" class="rn_DocumentCommentDiv rn_Hidden">
                             <textarea class="rn_DocumentTextArea" id="rn_<?=$this->instanceID;?>_FeedbackMessage" aria-label="<?= $this->data['attrs']['label_textarea_screenreader'] ?>" maxlength="4000"></textarea>
                        </div>
                        <button type="submit" class="rn_SubmitButton" id="rn_<?=$this->instanceID;?>_SubmitButton" disabled><?= $this->data['attrs']['label_button'] ?></button>
                        <span id="rn_<?= $this->instanceID ?>_StatusMessage" class="rn_Hidden"><?= $this->data['attrs']['label_submitting_message'] ?></span>
                        <div id="rn_<?=$this->instanceID;?>_ThanksMessage" class="rn_HiddenMessage rn_Hidden"><?= $this->data['attrs']['label_thanks_msg'] ?></div>
                        <div id="rn_<?=$this->instanceID;?>_ErrorMessage" class="rn_HiddenMessage rn_Hidden"><?= $this->data['attrs']['label_error_msg'] ?></div>
                    </div>
                <? endforeach; ?>
        <? endif; ?>
    </div>
    <rn:block id="bottom"/>
</div>
