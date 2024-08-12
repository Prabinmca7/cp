<rn:block id="preComment"/>
<div class="rn_CommentActivity">
    <rn:block id="commentTop"/>
    <rn:block id="commentActivityContent">
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
    <rn:block id="commentBottom"/>
</div>
<rn:block id="postComment"/>
