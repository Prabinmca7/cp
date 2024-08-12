<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <div id="rn_<?=$this->instanceID;?>_ProfilePicture">
        <div id="rn_ErrorLocation"></div>
        <? if ($this->data['js']['editingOwnAvatar']): ?>
            <h2><?= $this->data['attrs']['label_update_picture'] ?></h2>
        <? endif ?>

        <? if ($this->data['js']['socialUser']): ?>
            <rn:block id="prePreviewImage"/>
            <section class="rn_PreviewImage">
                <div class="rn_image">
                    <div class="rn_UserAvatar">
                        <div class="rn_Avatar rn_Large rn_Placeholder">
                            <span class="rn_Default rn_DefaultColor<?= $this->data['js']['defaultAvatar']['color'] ?> <?= !$this->data['currentAvatar']['url'] ? '' : 'rn_Hidden'?>">
                                <span class="rn_Liner">
                                    <?= $this->data['js']['defaultAvatar']['text'] ?>
                                </span>
                            </span>
                            <img class="<?= $this->data['currentAvatar']['url'] ? '' : 'rn_Hidden'?>" src="<?= $this->data['currentAvatar']['url'] ?: '#' ?>" alt=""/>
                        </div>
                    </div>
                    <div><a href="<?= $_SERVER['REQUEST_URI'] ?>" class="rn_Refresh" title="<?= $this->data['attrs']['label_reset_avatar_tooltip']; ?>"><?= $this->data['attrs']['label_reset_avatar'] ?></a></div>
                </div>
                <div class="rn_DisplayName" title="<?= $this->data['js']['socialUserDisplayName'] ?>">
                    <?= $this->data['js']['socialUserDisplayName'] ?>
                </div>
            </section>
            <rn:block id="postPreviewImage"/>

            <rn:block id="preAvatarOptions"/>
            <section class="rn_AvatarOptions">
                <? foreach($this->data['attrs']['avatar_selection_options'] as $avatarOptionName): ?>
                    <?= $this->render($avatarOptionName); ?>
                <? endforeach; ?>
            </section>
            <rn:block id="postAvatarOptions"/>
            <div class="rn_AvatarButtons">
                <rn:condition is_active_social_user="true">
                    <button class="rn_SaveButton"><?= $this->data['attrs']['label_save_changes_button'] ?></button>
                    <button class="rn_CancelButton"><?= $this->data['attrs']['label_cancel_button'] ?></button>
                </rn:condition>
            </div>
        <? else: ?>
            <div class="rn_NoSocialUser">
                <?= $this->data['attrs']['label_no_public_profile'] ?>
                <a href="javascript:void(0);" id="rn_<?= $this->instanceID ?>_AddSocialUser" class="rn_AddSocialUser"><?= $this->data['attrs']['label_add_public_profile'] ?></a>
            </div>
        <? endif; ?>
    </div>
    <rn:block id="bottom"/>
</div>
