<rn:block id="preCommentRatingGivenToUser"/>
<div class="rn_CommentRatingGivenToUserActivity">
    <rn:block id="topCommentRatingGivenToUser"/>
    <rn:block id="commentRatingGivenToUserActivityContent">
    <div class="rn_ActivityContent">
        <div class="rn_ParentContent">
            <?= $this->render('activityUser', array('user' => $action->CommunityQuestion->CreatedByCommunityUser)) ?>

            <div class="rn_Info">
                <a itemprop="url" href="<?= \RightNow\Utils\Url::defaultQuestionUrl($action->CommunityQuestion->ID) ?>">
                    <?= \RightNow\Utils\Text::truncateText($action->Body, $this->data['attrs']['truncate_size']) ?>
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
    <rn:block id="bottomCommentRatingGivenToUser"/>
</div>
<rn:block id="postCommentRatingGivenToUser"/>
