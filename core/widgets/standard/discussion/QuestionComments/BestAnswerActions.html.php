<? $userTypeMarking = $this->helper->shouldDisplayBestAnswerActions($comment, isset($this->data['socialUser']) ? $this->data['socialUser'] : null, true, $this->data['attrs']['best_answer_types']);
   $userTypeRemoving = $this->helper->shouldDisplayBestAnswerActions($comment, isset($this->data['socialUser']) ? $this->data['socialUser'] : null, false, $this->data['attrs']['best_answer_types']); ?>

<? if ($userTypeMarking || $userTypeRemoving): ?>
<div class="rn_BestAnswerActions">
    <? if ($userTypeMarking): ?>
        <? if ($userTypeMarking === "Author" || $userTypeMarking === "Both"): ?>
            <span class="rn_BestAnswerAssignment rn_UserTypeAuthor">
                <button type="button" data-commentid="<?= $comment->ID ?>" title="<?= $this->data['attrs']['label_mark_best_answer_author_tooltip'] ?>">
                    <?= $this->data['attrs']['label_best_answer_action_author'] ?>
                </button>
            </span>
        <? endif; ?>
        <? if ($userTypeMarking === "Moderator" || $userTypeMarking === "Both"): ?>
            <span class="rn_BestAnswerAssignment rn_UserTypeModerator">
                <button type="button" data-commentid="<?= $comment->ID ?>" title="<?= $this->data['attrs']['label_mark_best_answer_moderator_tooltip'] ?>">
                    <?= $this->data['attrs']['label_best_answer_action_moderator'] ?>
                </button>
            </span>
        <? endif; ?>
    <? endif; ?>

    <? if ($userTypeRemoving): ?>
        <? if ($userTypeRemoving === "Author" || $userTypeRemoving === "Both"): ?>
            <span class="rn_BestAnswerRemoval rn_UserTypeAuthor">
                <button type="button" data-commentid="<?= $comment->ID ?>" title="<?= $this->data['attrs']['label_unmark_best_answer_author_tooltip'] ?>">
                    <?= $this->data['attrs']['label_unmark_best_answer_author'] ?>
                </button>
            </span>
        <? endif; ?>
        <? if ($userTypeRemoving === "Moderator" || $userTypeRemoving === "Both"): ?>
            <span class="rn_BestAnswerRemoval rn_UserTypeModerator">
                <button type="button" data-commentid="<?= $comment->ID ?>" title="<?= $this->data['attrs']['label_unmark_best_answer_moderator_tooltip'] ?>">
                    <?= $this->data['attrs']['label_unmark_best_answer_moderator'] ?>
                </button>
            </span>
        <? endif; ?>
    <? endif; ?>
</div>
<? endif; ?>