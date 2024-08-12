<rn:block id="preQuestionInfo"/>
<div class="rn_QuestionInfo">
    <rn:block id="questionAvatar">
    <div class="rn_Author" itemprop="author" itemscope itemtype="http://schema.org/Person">
        <?= $this->render('Partials.Social.Avatar', $this->helper('Social')->defaultAvatarArgs($question->CreatedByCommunityUser, array(
            'size' => isset($this->data['attrs']['avatar_size']) ? $this->data['attrs']['avatar_size'] : 'medium',
        ))) ?>
    </div>
    </rn:block>

    <rn:block id="questionSubject">
    <div class="rn_QuestionSubject">
        <a href="<?= $link ?>" class="rn_Content<?= $question->Attributes->ContentLocked ? 'Locked' : 'Unlocked' ?>"><?= $question->Subject ?></a>
        <? if($excerpt): ?>
            <div class="rn_Excerpt"><?= $excerpt ?></div>
        <? endif; ?>
    </div>
    </rn:block>
</div>

<rn:block id="postQuestionInfo"/>
