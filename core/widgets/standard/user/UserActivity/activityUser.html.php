<rn:block id="preActivityUser"/>
<div class="rn_ActivityUser rn_ActivityUser<?= ucfirst($this->data['attrs']['avatar_size']) ?>" itemprop="author" itemscope itemtype="http://schema.org/Person">
    <rn:block id="activityUser">
    <?= $this->render('Partials.Social.Avatar', $this->helper('Social')->defaultAvatarArgs($user, array(
        'size' => $this->data['attrs']['avatar_size'],
    ))) ?>
    </rn:block>
</div>
<rn:block id="postActivityUser"/>
