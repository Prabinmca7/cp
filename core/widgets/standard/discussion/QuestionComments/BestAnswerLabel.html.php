<? if ($this->helper->commentIsBestAnswer($comment) && $comment->SocialPermissions->isActive() && $this->question->SocialPermissions->isActive()): ?>
    <span class="rn_BestAnswerLabel" title="<?= $this->helper->getBestAnswerLabel($comment) ?>">
        <?= $this->data['attrs']['label_best_answer'] ?>
        <span class="rn_ScreenReaderOnly">
        <?= $this->helper->getBestAnswerLabel($comment) ?>
        </span>
    </span>
<? endif; ?>
