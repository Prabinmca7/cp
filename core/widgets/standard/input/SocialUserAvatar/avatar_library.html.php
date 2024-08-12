<rn:block id="preAvatarLibraryOption"/>
<div class="rn_AvatarLibrary rn_AvatarOption <?= $this->data['currentAvatar']['type'] === 'assets' ? 'rn_ChosenAvatar' : '' ?>">
    <? if ($this->data['currentAvatar']['type'] === 'assets'): ?>
    <span class="rn_ScreenReaderOnly"><?= $this->data['attrs']['label_chosen_avatar_library'] ?></span>
    <? endif; ?>
    <rn:block id="preAvatarLibraryLabel"/>
    <span class="rn_OptionTitle">
        <?= $this->data['attrs']['label_choose_avatar_library'] ?>
    </span>
    <rn:block id="postAvatarLibraryLabel"/>
    <button id="rn_<?= $this->instanceID ?>_ChooseAvatar"><?= $this->data['attrs']['label_choose_button'] ?></button>
    <? if ($this->data['attrs']['label_avatar_library_hint'] || $this->data['attrs']['label_avatar_library_hint_user']): ?>
    <rn:block id="preAvatarLibraryHint"/>
    <div class="rn_HintText" id="rn_<?= $this->instanceID ?>_AvatarLibraryHint">
        <?= $this->data['attrs']['label_avatar_library_hint'] ?>
    </div>
    <rn:block id="postAvatarLibraryHint"/>
    <? endif; ?>
    <div class="rn_AvatarLibraryTabs"></div>
    <div class="rn_AvatarLibraryForm"></div>
    <rn:block id="postAvatarLibraryOption"/>
</div>
