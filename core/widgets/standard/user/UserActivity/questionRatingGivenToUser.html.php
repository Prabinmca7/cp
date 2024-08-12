<rn:block id="preQuestionRatingGivenToUser"/>
<div class="rn_QuestionRatingGivenToUserActivity">
    <rn:block id="topQuestionRatingGivenToUser"/>
    <rn:block id="questionRatingGivenToUserActivityContent">
    <div class="rn_ActivityContent">
        <div class="rn_ParentContent">
            <?= $this->render('activityUser', array('user' => $action->CreatedByCommunityUser)) ?>

            <div class="rn_Info">
                <a itemprop="url" href="<?= \RightNow\Utils\Url::defaultQuestionUrl($action->ID) ?>">
                    <?= \RightNow\Utils\Text::truncateText($action->Subject, $this->data['attrs']['truncate_size']) ?>
                </a>
                <rn:block id="activityTimestamp">
                <span class="rn_Time">
                    <?= $this->helper->formatTimestamp($date) ?>
                </span>
                </rn:block>
            </div>
        </div>
    </div>
    </rn:block>
    <rn:block id="bottomQuestionRatingGivenToUser"/>
</div>
<rn:block id="postQuestionRatingGivenToUser"/>
