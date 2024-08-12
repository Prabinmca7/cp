<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <?= $this->render('Partials.Social.Avatar', $this->helper('Social')->defaultAvatarArgs($this->data['js']['socialUser'], array(
        'size'        => $this->data['attrs']['avatar_size'],
        'displayName' => null,
        'profileUrl'  => null,
    ))) ?>
    <rn:block id="bottom"/>
</div>
