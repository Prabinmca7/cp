<rn:block id="preDefaultOption"/>
    <div class="rn_DefaultOption rn_AvatarOption <?= !$this->data['currentAvatar']['url'] ? 'rn_ChosenAvatar' : '' ?>">
        <? if (!$this->data['currentAvatar']['url']): ?>
            <span class="rn_ScreenReaderOnly"><?= $this->data['attrs']['label_using_default_avatar'] ?></span>
        <? endif; ?>
        <div id="rn_<?= $this->instanceID ?>_DefaultForm">
            <span class="rn_OptionTitle">
                <?= $this->data['attrs']['label_default'] ?>
            </span>
            <button id="rn_<?= $this->instanceID ?>_SelectDefault" type="button"><?= $this->data['attrs']['label_apply_button'] ?></button>

            <? if ($this->data['attrs']['label_default_hint'] || $this->data['attrs']['label_default_hint_user']): ?>
                <rn:block id="preDefaultHint"/>
                <div class="rn_HintText" id="rn_<?= $this->instanceID ?>_DefaultHint">
                    <?= $this->data['js']['editingOwnAvatar'] ? $this->data['attrs']['label_default_hint'] : $this->data['attrs']['label_default_hint_user'] ?>
                </div>
                <rn:block id="postDefaultHint"/>
            <? endif; ?>
        </div>
    </div>
<rn:block id="postDefaultOption"/>