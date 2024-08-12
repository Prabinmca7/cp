<? if ($this->data['js']['editingOwnAvatar'] && $this->data['attrs']['label_facebook_account']): ?>
    <rn:block id="preFacebookOption"/>
    <div class="rn_Service rn_Facebook rn_AvatarOption <?= $this->data['currentAvatar']['type'] === 'facebook' ? 'rn_ChosenAvatar' : '' ?>">
        <? if ($this->data['currentAvatar']['type'] === 'facebook'): ?>
            <span class="rn_ScreenReaderOnly"><?= $this->data['attrs']['label_chosen_facebook'] ?></span>
        <? endif; ?>
        <div id="rn_<?= $this->instanceID ?>_FacebookForm">
            <rn:block id="preFacebookLabel"/>
            <span class="rn_FacebookIcon rn_OptionTitle">
                <?= $this->data['attrs']['label_facebook_account'] ?>
            </span>
            <rn:block id="postFacebookLabel"/>
            <? if ($this->data['js']['facebook']['url']) : ?>
                <div class="rn_CurrentSocialFacebookAvatar">
                    <button id="rn_<?= $this->instanceID ?>_SelectFacebook" class="rn_SelectFacebook" data-service-name="facebook" type="button">
                        <img src="<?= $this->data['js']['facebook']['url'] ?: $this->data['currentAvatar']['url'] ?>" alt="<?= $this->data['attrs']['label_facebook_profile_picture'] ?>" />
                    </button>
                <? if($this->data['displayFacebookSuccessMessage']) : ?>
                    <span class="rn_FacebookSuccessMessage"><?= $this->data['attrs']['label_facebook_success_message'] ?></span>
                <? endif; ?>
                </div>
            <? else: ?>
                <div class="rn_NewSocialFacebookInput">
                    <rn:widget path="login/OpenLogin" controller_endpoint="/ci/openlogin/oauth/authorize/fbDetailsOnly"
                    label_process_explanation='When you click the “Continue” button, the Facebook log-in page will open. After logging in, you will return to this page with your Facebook profile picture. To use this picture as your community profile picture, click “Save Changes” below.'
                    label_service_button="Log into Facebook" label_login_button="Continue" display_in_dialog="false" sub_id="openlogin_facebook" one_click_access_enabled="true"/>
                </div>
            <? if ($this->data['attrs']['label_facebook_hint']): ?>
                <rn:block id="preFacebookHint"/>
                <div class="rn_HintText" id="rn_<?= $this->instanceID ?>_FacebookHint">
                    <?= $this->data['attrs']['label_facebook_hint'] ?>
                </div>
                <rn:block id="postFacebookHint"/>
            <? endif; ?>
            <? endif; ?>
        </div>
    </div>
    <rn:block id="postFacebookOption"/>
<? endif; ?>