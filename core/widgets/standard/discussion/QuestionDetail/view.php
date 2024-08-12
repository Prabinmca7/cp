<div id="rn_<?=$this->instanceID?>" class="<?=$this->classList?>">
    <rn:block id="top"/>
    <rn:block id="questionToolbar">
        <div class="rn_QuestionActionsBar">
            <? if($this->data['attrs']['author_roleset_callout'] !== "0" && ($index = $this->helper('Social')->highlightAuthorContent($this->data['question']->CreatedByCommunityUser->ID, $this->data['author_roleset_callout'])) > 0): ?>
                <? $strToDisplay = $this->data['author_roleset_callout'][$index];?>
                <div class="rn_HighlightQuestion <?= $this->data['author_roleset_styling'][$strToDisplay]?>">
                    <?= $strToDisplay ?>
                </div>
            <? endif; ?>
            <rn:condition is_social_moderator="true">
            <? if (!$this->data['question']->SocialPermissions->isDeleted()): ?>
                <div class="rn_QuestionToolbar">
                    <rn:widget path="moderation/ModerationInlineAction" label_action_suspend_user="#rn:msg:SUSPEND_AUTHOR_LBL#" label_action_approve_restore_user="#rn:msg:APPROVERESTORE_AUTHOR_LBL#" label_user_not_found_error="#rn:msg:AUTHOR_DOES_NOT_EXIST_MSG#" object_type="CommunityQuestion" object_id="#rn:php:$this->data['question']->ID#" sub_id="moderation"/>
                </div>
            <? endif; ?>
            </rn:condition>
        </div>
    </rn:block>
    <div class="rn_QuestionContent">
        <? if ($this->data['question']->SocialPermissions->canUpdate()): ?>
        <?= $this->render('edit', array('question' => $this->data['question'], 'useRichTextInput' => $this->data['attrs']['use_rich_text_input'])) ?>
        <? endif; ?>

        <article class="rn_ExistingQuestionDetails">
            <?= $this->render('header', array('question' => $this->data['question'])) ?>

            <rn:block id="questionDetailArea">
            <div class="rn_QuestionBody" itemprop="articleBody">
                <?= $this->helper('Social')->formatBody($this->data['question'], $this->data['attrs']['highlight']) ?>
                <? if($this->data['attrs']['use_file_attachments']): ?>
                    <rn:widget path="output/DataDisplay" name="CommunityQuestion.FileAttachments" label="#rn:msg:FILE_ATTACHMENTS_LBL#" sub_id="fileDisplay"/>
                <? endif; ?>
            </div>
            </rn:block>

            <?= $this->render('details', array('question' => $this->data['question'])) ?>
        </article>
    </div>
    <rn:block id="bottom"/>
</div>
