<rn:block id="preGridView"/>
<div class="rn_RecentUsersGrid">
    <? for ($i = 0; $i < count($this->data['users']); $i++): ?>
        <div class="rn_ProfileAvatar">
            <? if($this->data['attrs']['avatar_size'] !== 'none') { ?>
                <?= $this->render('Partials.Social.Avatar', $this->helper('Social')->defaultAvatarArgs(isset($this->data['users'][$i]['user']) ? $this->data['users'][$i]['user'] : null, array(
                    'size' => $this->data['attrs']['avatar_size'],
                ), true)) ?>
            <? } ?>
        </div>
    <? endfor; ?>
</div>
<rn:block id="postGridView"/>
