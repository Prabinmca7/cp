<? if($this->data['displayPagination'] && in_array('top', $this->data['attrs']['paginate_comments_position'])): ?>
    <?= $this->render('Paginate'); ?>
<? endif; ?>

<rn:block id="preComments"/>
<div class="rn_Comments">
<? foreach ($comments as $comment): ?>
    <? if ($comment->Parent->ID && !$prevCommentWasReply): ?>
        <rn:block id="preCommentReplies"/>
        <div id="rn_<?=$this->instanceID;?>_Replies_<?= $comment->Parent->ID ?>" data-commentid="<?= $comment->Parent->ID ?>" class="rn_Replies">
            <a class="rn_ReplyTitle" href="javascript:void(0);" data-toggle-parent="rn_Collapsed">
                <?= $this->data['attrs']['label_replies'] ?>
            </a>
    <? elseif (!$comment->Parent->ID && isset($prevCommentWasReply) && $prevCommentWasReply): ?>
        </div>
        <rn:block id="postCommentReplies"/>
    <? endif; ?>

    <? if ($this->helper->shouldDisplayComment($comment)): ?>
        <rn:block id="preCommentContainer"/>
        <?= $this->render('Comment', array('comment' => $comment, 'questionID' => $questionID)) ?>
        <rn:block id="postCommentContainer"/>
    <? endif; ?>

    <? $prevCommentWasReply = !!$comment->Parent->ID; ?>
<? endforeach; ?>
<? if (isset($prevCommentWasReply) && $prevCommentWasReply): ?>
    </div>
    <rn:block id="postCommentReplies"/>
<? endif; ?>
</div>
<rn:block id="postComments"/>

<? if($this->data['displayPagination'] && in_array('bottom', $this->data['attrs']['paginate_comments_position'])): ?>
    <?= $this->render('Paginate'); ?>
<? endif; ?>
