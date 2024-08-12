<div class="rn_QuestionHeader">
    <rn:block id="questionAuthor">
    <div class="rn_QuestionAuthor" itemprop="author" itemscope itemtype="http://schema.org/Person">
        <?= $this->render('Partials.Social.Avatar', $this->helper('Social')->defaultAvatarArgs($question->CreatedByCommunityUser, array(
            'size' => $this->data['attrs']['avatar_size'],
        ))) ?>
    </div>
    </rn:block>

    <rn:block id="questionTitleArea">
    <div class="rn_QuestionTitle">
        <div id="rn_<?=$this->instanceID?>_Summary" role="heading" aria-level="1" itemprop="name">
            <h1><?= $question->Subject ?></h1>
        </div>
    </div>
    </rn:block>
</div>
