<rn:block id="preAnswer"/>
<div class="rn_AnswerInfo <?= $className ?>" itemprop="suggestedAnswer acceptedAnswer" itemscope itemtype="http://schema.org/Answer">
    <rn:block id="answerAvatar">
    <div class="rn_Author" itemprop="author" itemscope itemtype="http://schema.org/Person">
        <?= $this->render('Partials.Social.Avatar', $this->helper('Social')->defaultAvatarArgs($comment->CreatedByCommunityUser, array(
            'size' => isset($this->data['attrs']['avatar_size']) ? $this->data['attrs']['avatar_size'] : 'medium',
        ))) ?>
    </div>
    </rn:block>
    <div class="rn_AnswerContent">
        <div class="rn_AnswerText" itemprop="text">
            <span class="rn_BestAnswerLabel"><?= $answerLabel ?>:</span>
            <?= \RightNow\Utils\Text::truncateText(\RightNow\Libraries\Formatter::formatTextEntry($comment->Body, $comment->BodyContentType->LookupName, false), $this->data['attrs']['answer_text_length']) ?>
        </div>
        <div class="rn_AnswerMoreLink">
            <a href="<?= $link ?>" itemprop="discussionUrl"><?= $this->data['attrs']['label_answer_more_link'] ?></a>
        </div>
    </div>
</div>
<rn:block id="postAnswer"/>
