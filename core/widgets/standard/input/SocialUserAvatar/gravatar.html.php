<? if ($this->data['js']['editingOwnAvatar'] && $this->data['attrs']['label_gravatar_account']): ?>
    <rn:block id="preGravatarOption"/>
    <div class="rn_Service rn_Gravatar rn_AvatarOption <?= $this->data['currentAvatar']['type'] === 'gravatar' ? 'rn_ChosenAvatar' : '' ?> ">
        <? if ($this->data['currentAvatar']['type'] === 'gravatar'): ?>
            <span class="rn_ScreenReaderOnly"><?= $this->data['attrs']['label_chosen_gravatar'] ?></span>
        <? endif; ?>
        <div id="rn_<?= $this->instanceID ?>_GravatarForm">
            <rn:block id="preGravatarLabel"/>
            <span class="rn_OptionTitle">
                <?= $this->data['attrs']['label_gravatar_account'] ?>
            </span>
            <rn:block id="postGravatarLabel"/>    
            <div class="rn_GravatarAddress"><?= $this->data['js']['email']['address'] ?></div>
            <button id="rn_<?= $this->instanceID ?>_SelectGravatar" data-service-name="gravatar" type="button"><?= $this->data['attrs']['label_apply_button'] ?></button>
            <? if ($this->data['attrs']['label_gravatar_hint']): ?>
                <rn:block id="preGravatarHint"/>
                <div class="rn_HintText" id="rn_<?= $this->instanceID ?>_GravatarHint">
                    <?= $this->data['attrs']['label_gravatar_hint'] ?>
                </div>
                <rn:block id="postGravatarHint"/>
            <? endif; ?>
        </div>
    </div>
    <rn:block id="postGravatarOption"/>
<? endif; ?>