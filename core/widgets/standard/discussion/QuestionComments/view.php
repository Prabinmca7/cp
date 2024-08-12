<div id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <rn:block id="preTitle"/>
    <? if ($this->data['attrs']['label_title']): ?>
    <div class="rn_CommentsTitle" role="heading" aria-level="2">
        <h2><?= $this->data['attrs']['label_title'] ?></h2>
    </div>
    <? endif; ?>
    <rn:block id="postTitle"/>
    <div class="rn_SortOrder">
        <button class="<?= 'rn_NewestButton' . (($this->data['attrs']['comments_sort_order'] === 'DESC') ? ' rn_Disabled' : '') ?>" type="button" data-sort="ASC" title="<?= $this->data['attrs']['label_sort_newest'] ?>"><?= $this->data['attrs']['label_sort_newest'] ?></button>
        <span class="rn_Separator"></span>
        <button class="<?= 'rn_OldestButton' . (($this->data['attrs']['comments_sort_order'] === 'ASC') ? ' rn_Disabled' : '') ?>" type="button" data-sort="DESC" title="<?= $this->data['attrs']['label_sort_oldest'] ?>"><?= $this->data['attrs']['label_sort_oldest'] ?></button>
    </div>
    <div id="rn_<?=$this->instanceID;?>_Comments" aria-live="polite">
        <?= $this->render('CommentList', array('comments' => $this->data['comments'], 'questionID' => $this->data['questionID'])); ?>
    </div>

    <? if($this->helper->shouldDisplayNewCommentArea()):?>
        <div class="rn_CommentContainer rn_NewComment">
        <? if (isset($this->data['isLoggedIn']) && $this->data['isLoggedIn'] && isset($this->data['socialUser']) && $this->data['socialUser']): ?>
            <div class="rn_CommentInfo">
                <span class="rn_CommentAvatarImage">
                    <?= $this->render('Partials.Social.Avatar', $this->helper('Social')->defaultAvatarArgs($this->data['socialUser'], array(
                        'size' => $this->data['attrs']['avatar_size'],
                    ))) ?>
                </span>
            </div>
        <? endif; ?>
            <div class="rn_CommentContent">
                <div class="rn_NoNewCommentMessage rn_Hidden"><?= $this->data['attrs']['label_no_new_comment_allowed']; ?></div>
                <div class="rn_PostNewComment" id="rn_<?= $this->instanceID ?>_NewComment">
                    <? if (isset($this->data['isLoggedIn']) && $this->data['isLoggedIn'] && isset($this->data['socialUser']) && $this->data['socialUser']): ?>
                    <form id="rn_<?=$this->instanceID;?>_CommentForm" method="post" action="/ci/ajaxRequest/sendForm">
                        <div id="rn_<?= $this->instanceID ?>_NewCommentError"></div>
                        <? if($this->data['attrs']['use_rich_text_input']): ?>
                            <rn:widget path="input/RichTextInput" name="CommunityComment.Body" label_input="#rn:php:$this->data['attrs']['label_add_new_comment']#" required="true" label_required="#rn:php:$this->data['attrs']['label_comment_required']#" sub_id="newCommentBody" />
                        <? else: ?>
                            <rn:widget path="input/TextInput" name="CommunityComment.Body" label_input="#rn:php:$this->data['attrs']['label_add_new_comment']#" required="true" label_required="#rn:php:$this->data['attrs']['label_comment_required']#" sub_id="newCommentBody" />
                        <? endif; ?>
                        <? if($this->data['attrs']['use_file_attachments']): ?>
                            <rn:widget path="input/SocialFileAttachmentUpload" name="CommunityComment.FileAttachments" sub_id="newCommentFile" />
                        <? endif; ?>

                        <rn:widget path="input/FormSubmit" error_location="rn_#rn:php:$this->instanceID#_NewCommentError" label_button="#rn:php:$this->data['attrs']['label_post_button']#" on_success_url="#rn:php:'/app/' . $this->CI->page#" sub_id="newCommentSubmit" />
                    </form>
                    <? /* Single rich text widget used for editable comments and reply comments */ ?>
                    <form id="rn_<?=$this->instanceID;?>_RovingCommentForm" class="rn_Hidden rn_CommentForm" method="post" action="/ci/ajaxRequest/sendForm">
                        <div class="rn_CommentInfo">
                            <span class="rn_CommentAvatarImage">
                                <?= $this->render('Partials.Social.Avatar', $this->helper('Social')->defaultAvatarArgs($this->data['socialUser'], array(
                                    'size' => $this->data['attrs']['avatar_size'],
                                ))) ?>
                            </span>
                        </div>
                        <div class="rn_CommentContent">
                            <div id="rn_<?=$this->instanceID;?>_RovingCommentFormError"></div>
                            <? if($this->data['attrs']['use_rich_text_input']): ?>
                                <rn:widget path="input/RichTextInput" name="CommunityComment.Body" label_input="#rn:php:$this->data['attrs']['label_add_new_comment']#" required="true" label_required="#rn:php:$this->data['attrs']['label_comment_required']#" sub_id="inlineCommentBody" />
                            <? else: ?>
                                <rn:widget path="input/TextInput" name="CommunityComment.Body" label_input="#rn:php:$this->data['attrs']['label_add_new_comment']#" required="true" label_required="#rn:php:$this->data['attrs']['label_comment_required']#" sub_id="inlineCommentBody" />
                            <? endif; ?>
                            <? if($this->data['attrs']['use_file_attachments']): ?>
                                <rn:widget path="input/SocialFileAttachmentUpload" name="CommunityComment.FileAttachments" sub_id="editCommentFile" />
                            <? endif; ?>
                            <div class="rn_CommentEditOptions">
                                <div class="rn_CommentCancelAndEdit">
                                    <span class="rn_CancelEdit">
                                        <a href="javascript:void(0);" class="rn_CancelEditor"><?= $this->data['attrs']['label_cancel_edit'] ?></a>
                                    </span>
                                    <rn:widget path="input/FormSubmit" error_location="rn_#rn:php:$this->instanceID#_RovingCommentFormError" label_button="#rn:php:$this->data['attrs']['label_save_edit']#" on_success_url="#rn:php:'/app/' . $this->CI->page#" sub_id="inlineCommentSubmit" />
                                </div>
                                <rn:block id="preCommentDelete"/>
                                <button class="rn_DeleteCommentAction">
                                    <?= $this->data['attrs']['label_delete'] ?>
                                </button>
                                <rn:block id="postCommentDelete"/>
                            </div>
                        </div>
                    </form>
                    <? elseif (isset($this->data['isLoggedIn']) && $this->data['isLoggedIn']): ?>
                    <a href="javascript:void(0);" role="button" aria-haspopup="true" id="rn_<?=$this->instanceID;?>_NeedSocialInfo" class="rn_NeedSocialInfo"><?=$this->data['attrs']['label_add_new_comment'];?></a>
                    <? else: ?>
                    <a href="javascript:void(0);" role="button" aria-haspopup="true" id="rn_<?=$this->instanceID;?>_Login" class="rn_SocialLogin"><?=$this->data['attrs']['label_login_to_comment'];?></a>
                    <? endif; ?>
                </div>
            </div>
        </div>
    <?endif;?>
    <rn:block id="bottom"/>
</div>
