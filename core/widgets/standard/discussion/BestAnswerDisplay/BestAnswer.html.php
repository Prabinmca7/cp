<rn:block id="preBestAnswer"/>
<li class="rn_BestAnswerContainer" itemprop="suggestedAnswer acceptedAnswer" itemscope itemtype="http://schema.org/Answer">
    <div class="rn_BestAnswerInfo">
        <rn:block id="authorAvatar">
            <div class="rn_CommentAuthorAvatar" itemprop="author" itemscope itemtype="http://schema.org/Person">
                <?= $this->render('Partials.Social.Avatar', $this->helper('Social')->defaultAvatarArgs($bestAnswer['comment']['data']->CreatedByCommunityUser, array(
                    'size' => $this->data['attrs']['avatar_size'],
                ))) ?>
            </div>
        </rn:block>
    </div>

    <rn:block id="preCommentContent"/>
    <div class="rn_BestAnswerContent" aria-live="polite">
        <rn:block id="preCommentDetails"/>
        <div class="rn_BestAnswerHeader">
            <div class="rn_CommentSelectedBy">
                <rn:block id="selectedBy">
                <?= $this->data['attrs']['label_selected_best_by'] ?>
                <? for ($i = 0; $i < count($bestAnswer['selectedBy']); $i++): ?>
                    <a class="rn_SelectedByUser" href="<?= $this->helper('Social')->userProfileUrl($bestAnswer['selectedBy'][$i]['id']) ?>">
                        <? if ($bestAnswer['selectedBy'][$i]['type'] === SSS_BEST_ANSWER_AUTHOR && $this->data['attrs']['label_selected_best_author'] !== ''): ?>
                            <?= $this->data['attrs']['label_selected_best_author'] ?>
                        <? elseif ($bestAnswer['selectedBy'][$i]['type'] === SSS_BEST_ANSWER_MODERATOR && $this->data['attrs']['label_selected_best_moderator'] !== ''): ?>
                            <?= $this->data['attrs']['label_selected_best_moderator'] ?>
                        <? else: ?>
                            <span class="rn_SelectedByUserDisplayName"><?= $bestAnswer['selectedBy'][$i]['user'] ?></span>
                        <? endif; ?>
                    </a>
                    <? if ($i < count($bestAnswer['selectedBy']) - 1): ?>
                    <span class="rn_SelectedByConnector"><?= $this->data['attrs']['label_selected_by_connector'] ?></span>
                    <? endif; ?>
                <? endfor; ?>
                </rn:block>
            </div>
        </div>
        <rn:block id="postCommentDetails"/>

        <div class="rn_BestAnswerBody rn_CommentCollapsed">
            <rn:block id="preBestAnswerText"/>
            <? if($this->data['attrs']['author_roleset_callout'] || $this->data['attrs']['original_poster_callout']): ?>
                <div class="rn_BestAnswerLabel">
                    <? if($this->data['attrs']['author_roleset_callout'] && ($index = $this->helper('Social')->highlightAuthorContent($bestAnswer['comment']['data']->CreatedByCommunityUser->ID, $this->data['author_roleset_callout'])) > 0): ?>
                        <? $strToDisplay = $this->data['author_roleset_callout'][$index]; ?>
                        <div class="rn_HighlightBestAnswer <?= $this->data['author_roleset_styling'][$strToDisplay] ?>">
                            <?= $strToDisplay ?>
                        </div>
                    <? endif; ?>
                    <? if ($this->data['attrs']['original_poster_callout'] && ($this->helper->question->CreatedByCommunityUser->ID == $bestAnswer['comment']['data']->CreatedByCommunityUser->ID)): ?>
                        <div class="rn_HighlightBestAnswer rn_OriginalPosterStyle">
                            <?= $this->data['attrs']['label_original_poster'] ?>
                        </div>
                    <? endif; ?>
                </div>
            <? endif; ?>
            <div itemprop="text" id="rn_<?= $this->instanceID ?>_BestAnswerCommentText_<?= $bestAnswer['comment']['data']->ID ?>" class="rn_CommentText">
                <div class="rn_CommentLiner">
                    <?= \RightNow\Libraries\Formatter::formatTextEntry($bestAnswer['comment']['data']->Body, $bestAnswer['comment']['data']->BodyContentType->LookupName, false) ?>
                </div>
            </div>
            <rn:block id="postBestAnswerText"/>

            <rn:block id="preBestAnswerActions"/>
            <div class="rn_BestAnswerActions">
                <div class="rn_BestAnswerRemoval">
                <? if($userType = $this->helper->shouldDisplayBestAnswerRemoval($bestAnswer['comment']['data'])): ?>
                    <? if ($userType === "Author" || $userType === "Both"): ?>
                        <span class="rn_UserTypeAuthor">
                            <button type="button" data-commentid="<?= $bestAnswer['comment']['data']->ID ?>" title="<?= $this->data['attrs']['label_unmark_best_answer_author_tooltip'] ?>">
                                <?= $this->data['attrs']['label_unmark_best_answer_author'] ?>
                            </button>
                        </span>
                    <? endif; ?>
                    <? if ($userType === "Moderator" || $userType === "Both"): ?>
                        <span class="rn_UserTypeModerator">
                            <button type="button" data-commentid="<?= $bestAnswer['comment']['data']->ID ?>" title="<?= $this->data['attrs']['label_unmark_best_answer_moderator_tooltip'] ?>">
                                <?= $this->data['attrs']['label_unmark_best_answer_moderator'] ?>
                            </button>
                        </span>
                    <? endif; ?>
                <? else: ?>
                    &nbsp;
                <? endif; ?>
                </div>
                <div class="rn_BestAnswerRating">
                    <rn:widget path="feedback/SocialContentRating" comment_id="#rn:php:$bestAnswer['comment']['data']->ID#" question_id="#rn:php:$question->ID#" sub_id="commentRating" />
                </div>
                <div class="rn_BestAnswerCommentActions">
                    <a itemprop="url" class="rn_ReplyToComment <?= $this->data['attrs']['always_show_jump_to'] ? '' : 'rn_Hidden' ?>" href="javascript:void(0)" data-commentid="<?= $bestAnswer['comment']['data']->ID ?>">
                        <?= $this->data['attrs']['label_jump_to'] ?>
                    </a>
                    <a class="rn_ShowAllCommentText" href="javascript:void(0)" data-commentid="<?= $bestAnswer['comment']['data']->ID ?>">
                        <?= $this->data['attrs']['label_expand'] ?>
                    </a>
                    <a class="rn_CollapseCommentText rn_Hidden" href="javascript:void(0)" data-commentid="<?= $bestAnswer['comment']['data']->ID ?>">
                        <?= $this->data['attrs']['label_collapse'] ?>
                    </a>

                </div>
            </div>
            <rn:block id="postBestAnswerActions"/>
        </div>
    </div>
    <rn:block id="postCommentContent"/>
</li>
<rn:block id="postBestAnswer"/>
