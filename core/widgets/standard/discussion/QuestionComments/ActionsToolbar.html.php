<div class="rn_CommentToolbarLeft">
    <ul role="menubar">
        <? if($this->helper->shouldDisplayCommentReply($comment)): ?>
        <li role="menuitem" class="rn_CommentAction" title="<?=$this->data['attrs']['label_reply_button_title'];?>">
            <a href="javascript:void(0);" class="rn_CommentAction rn_ReplyAction <?= (!isset($this->data['socialUser']) || !$this->data['socialUser']) ? 'rn_SocialLogin' : '' ?>" role="button">
                <span class="rn_ActionLabel"><?= $this->data['attrs']['label_reply_link'] ?></span>
            </a>
        </li>
        <? endif; ?>
        <? if($comment->SocialPermissions->isActive() && $this->data['attrs']['use_rich_text_input'] && $this->data['attrs']['quote_comment_enabled']): ?>
            <li role="menuitem" class="rn_CommentAction" title="<?=$this->data['attrs']['label_quote_button_title'];?>">
                <a href="javascript:void(0);" class="rn_CommentAction rn_QuoteAction <?= (!$this->data['socialUser']) ? 'rn_SocialLogin' : '' ?>" role="button">
                    <span class="rn_ActionLabel"><?= $this->data['attrs']['label_quote_link'] ?></span>
                </a>
            </li>
        <? endif; ?>
        <? if($comment->SocialPermissions->isActive() && $this->data['attrs']['show_share_link']): ?>
        <li role="menuitem" class="rn_CommentAction" title="<?=$this->data['attrs']['label_share_button_title'];?>">
            <a href="javascript:void(0);" class="rn_CommentAction rn_ShareAction" role="button">
                <span class="rn_ActionLabel"><?= $this->data['attrs']['label_share_link'] ?></span>
            </a>
        </li>
        <? endif; ?>
    </ul>
</div>

<div class="rn_CommentToolbarRight">
    <ul role="menubar">
        <li role="menuitem" class="rn_CommentRating" title="<?=$this->data['attrs']['label_rate_button_title'];?>">
            <rn:widget path="feedback/SocialContentRating" comment_id="#rn:php:$comment->ID#" question_id="#rn:php:$this->helper->question->ID#" sub_id="commentRating" />
        </li>

        <? if(!\RightNow\Utils\Framework::isSocialUser() || $comment->SocialPermissions->canFlag()): ?>
        <li role="menuitem" class="rn_CommentAction" title="<?=$this->data['attrs']['label_flag_button_title'];?>">
            <rn:widget path="feedback/SocialContentFlagging" comment_id="#rn:php:$comment->ID#" question_id="#rn:php:$questionID#" sub_id="commentFlag" />
        </li>
        <? endif; ?>

        <? if ($comment->SocialPermissions->canUpdate()): ?>
        <li role="menuitem" class="rn_CommentAction" title="<?=$this->data['attrs']['label_edit_button_title'];?>">
            <rn:block id="preCommentEdit"/>
            <a href="javascript:void(0);" class="rn_EditCommentAction" data-commentID="<?= $comment->ID ?>">
                <span class="rn_ActionLabel"><?= $this->data['attrs']['label_edit'] ?></span>
            </a>
            <rn:block id="postCommentEdit"/>
        </li>
        <? endif; ?>
    </ul>
</div>
