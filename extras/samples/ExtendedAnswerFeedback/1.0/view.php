<?
/**
 * File: view.php
 * Abstract: Extending PHP view for ExtendedAnswerFeedback widget
 * Version: 1.0
 */
?>
<rn:block id='AnswerFeedback-preFeedbackInput'>
    <fieldset id="rn_<?= $this->instanceID ?>_FeedbackType" aria-describedby="rn_<?= $this->instanceID ?>_FeedbackTypeLabel" class="rn_FeedbackType">
        <div id="rn_<?= $this->instanceID ?>_FeedbackTypeLabel">
            <span class="rn_LabelText"><?= $this->data['js']['typeLabel'] ?></span>
            <span class="rn_Required" > <?=\RightNow\Utils\Config::getMessage(FIELD_REQUIRED_MARK_LBL);?></span><span class="rn_ScreenReaderOnly"><?=\RightNow\Utils\Config::getMessage(REQUIRED_LBL)?></span>
        </div>
        <? foreach ($this->data['feedbackTypes'] as $namedValue): ?>
        <label>
            <input type="radio" name="rn_<?= $this->instanceID ?>_FeedbackTypeChoice" value="<?= $namedValue->ID ?>"/>
            <?= $namedValue->LookupName ?>
        </label>
        <? endforeach; ?>
    </fieldset>
</rn:block>

<rn:block id='AnswerFeedback-bottomForm'>
    <label for="rn_<?= $this->instanceID ?>_Source"><?= $this->data['js']['sourceLabel'] ?></label>
    <textarea id="rn_<?= $this->instanceID ?>_Source" cols="60" rows="2" class="rn_Textarea"></textarea>
</rn:block>
