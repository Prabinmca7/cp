<? if ($this->helper->commentIsBestAnswer($comment) && $comment->SocialPermissions->isActive() && $this->question->SocialPermissions->isActive()): ?>
<div id="rn_<?= $this->instanceID ?>_<?= $comment->ID ?>" data-commentID="<?= $comment->ID ?>" data-contentType="<?= $comment->BodyContentType->LookupName ?>" class="rn_CommentContainer rn_BestAnswer" itemprop="suggestedAnswer acceptedAnswer" itemscope itemtype="http://schema.org/Answer">
<? else: ?>
<div id="rn_<?= $this->instanceID ?>_<?= $comment->ID ?>" data-commentID="<?= $comment->ID ?>" data-contentType="<?= $comment->BodyContentType->LookupName ?>" class="rn_CommentContainer" itemprop="suggestedAnswer" itemscope itemtype="http://schema.org/Answer" aria-label="Required">
<? endif; ?>

    <? if(!$this->data['attrs']['use_rich_text_input']): ?>
        <span id="rn_<?= $this->instanceID ?>_<?= $comment->ID ?>_rawComment" data-rawCommentText="<?= \RightNow\Utils\Text::escapeHtml($comment->Body) ?>" class="rn_Hidden rn_rawCommentText"></span>
    <? endif; ?>

    <rn:block id="preCommentContainer"/>
    <div class="rn_CommentInfo">
        <rn:block id="preCommentAvatar"/>
        <span class="rn_CommentAvatarImage" itemprop="author" itemscope itemtype="http://schema.org/Person">
            <?= $this->render('Partials.Social.Avatar', $this->helper('Social')->defaultAvatarArgs($comment->CreatedByCommunityUser, array(
                'size' => $this->data['attrs']['avatar_size'],
            ))) ?>
        </span>
        <rn:block id="postCommentAvatar"/>
    </div>

    <? /* ID serves as the comment's permalink. */ ?>
    <div class="rn_CommentContent <?= $comment->SocialPermissions->canDelete() ? 'rn_CommentDeletable' : '' ?>" id="comment_<?=$comment->ID ?>">

        <? if($comment->SocialPermissions->isSuspended() && $comment->SocialPermissions->canUpdateStatus()): ?>
        <div class="rn_Banner rn_SuspendedComment">
            <?= $this->data['attrs']['label_comment_suspended_banner']; ?>
        </div>
        <? endif; ?>

        <div class="rn_HeaderToolbar">
                <div class="rn_CommentLabel">
                    <? if($this->data['attrs']['author_roleset_callout'] && ($index = $this->helper('Social')->highlightAuthorContent($comment->CreatedByCommunityUser->ID, $this->data['author_roleset_callout'])) > 0): ?>
                    <? $strToDisplay = $this->data['author_roleset_callout'][$index]; ?>
                        <div class="rn_HighlightComment <?= $this->data['author_roleset_styling'][$strToDisplay] ?>">
                            <?= $strToDisplay ?>
                        </div>
                    <? endif; ?>
                    <? if ($this->data['attrs']['original_poster_callout'] && ($this->helper->question->CreatedByCommunityUser->ID == $comment->CreatedByCommunityUser->ID)): ?>
                        <div class="rn_HighlightComment rn_OriginalPosterStyle">
                            <?= $this->data['attrs']['label_original_poster'] ?>
                        </div>
                    <? endif; ?>
                    
                    <div class="rn_BestAnswerInfo" aria-live="assertive" aria-relevant="all">
                        <?= $this->render('BestAnswerLabel', array('comment' => $comment)) ?>
                    </div>
                </div>

                <rn:condition is_social_moderator="true">
                    <rn:widget path="moderation/ModerationInlineAction" label_action_suspend_user="#rn:msg:SUSPEND_AUTHOR_LBL#" label_action_approve_restore_user="#rn:msg:APPROVERESTORE_AUTHOR_LBL#" label_user_not_found_error="#rn:msg:AUTHOR_DOES_NOT_EXIST_MSG#" label_action_menu="#rn:msg:MODERATE_LBL#" object_type="CommunityComment" object_id="#rn:php:$comment->ID#" sub_id="moderate"/>
                </rn:condition>

        </div>

        <rn:block id="preCommentContent"/>
        <? if ($this->helper->shouldDisplayCommentContent($comment)): ?>
            <div class="rn_CommentText" itemprop="text">
                <?= $this->helper('Social')->formatBody($comment, $this->data['attrs']['highlight']) ?>
            </div>
        <? else: ?>
            <div class="rn_CommentText rn_RemovedComment"><?= $this->data['attrs']['label_comment_unavailable'] ?></div>
        <? endif; ?>
        <rn:block id="postCommentContent"/>
        <? if($this->data['attrs']['use_file_attachments']): ?>
            <rn:widget path="output/DataDisplay" name="CommunityComment.FileAttachments" sub:file:label="#rn:msg:FILE_ATTACHMENTS_LBL#" sub:file:parent_object_id="#rn:php:$comment->ID#" sub_id="fileDisplay"/>
        <? endif; ?>
        <div class="rn_CommentFooter">
            <div class="rn_Timestamps">
                <span class="rn_CommentTimestamp">
                    <rn:block id="preCommentCreatedTime"/>
                        <span class="rn_Created">
                            <? printf($this->data['attrs']['label_created_time'], '<time itemprop="dateCreated" datetime="' . $this->helper->formattedTimestamp($comment->CreatedTime, true) . '">' . $this->helper->formattedTimestamp($comment->CreatedTime) . '</time>') ?>
                        </span>
                    <rn:block id="postCommentCreatedTime"/>
                    <? if ($comment->CreatedTime !== $comment->UpdatedTime): ?>
                    <rn:block id="preCommentUpdatedTime"/>
                        <span class="rn_Updated">
                            <? printf($this->data['attrs']['label_updated_time'], '<time itemprop="dateUpdated" datetime="' . $this->helper->formattedTimestamp($comment->UpdatedTime, true) . '">' . $this->helper->formattedTimestamp($comment->UpdatedTime) . '</time>') ?>
                        </span>
                    <rn:block id="postCommentUpdatedTime"/>
                    <? endif; ?>
                </span>
            </div>

            <? if (!in_array('none', $this->data['attrs']['best_answer_types'])): ?>
                <?= $this->render('BestAnswerActions', array('comment' => $comment)) ?>
            <? endif; ?>
            
            <div class="rn_CommentToolbar">
                <?= $this->render('ActionsToolbar', isset($questionID) ? array('comment' => $comment, 'questionID' => $questionID) : array('comment' => $comment, 'questionID' => 0)) ?>
            </div>
        </div>
    </div>

    <rn:block id="postCommentContainer"/>
</div>
