<div id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?> rn_Hidden">
    <rn:block id="top"/>

    <? if ($this->data['attrs']['open_login_providers']): ?>
    <rn:block id="openLoginLink">
    <div class="rn_OpenLoginAlternative">
        <span class="rn_OpenLoginIntro">
            <?= $this->data['attrs']['label_open_login_intro'] ?>
        </span>
        <? foreach ($this->data['attrs']['open_login_providers'] as $provider): ?>
        <? if ($provider === 'facebook'): ?>
        <rn:widget path="login/OpenLogin" sub_id='#rn:php:"openlogin_$provider"#' display_in_dialog="false"/> <? /* Attributes Default to Facebook */ ?>
        <? elseif ($provider === 'twitter'): ?>
        <rn:widget path="login/OpenLogin" sub_id='#rn:php:"openlogin_$provider"#' display_in_dialog="false" controller_endpoint="/ci/openlogin/oauth/authorize/twitter" label_service_button="Twitter" label_process_explanation="#rn:msg:CLICK_BTN_TWITTER_LOG_TWITTER_MSG#" label_login_button="#rn:msg:LOG_IN_USING_TWITTER_LBL#"/>
        <? elseif ($provider === 'google'): ?>
        <rn:widget path="login/OpenLogin" sub_id='#rn:php:"openlogin_$provider"#' display_in_dialog="false" controller_endpoint="/ci/openlogin/openid/authorize/google" label_service_button="Google" label_process_explanation="#rn:msg:CLICK_BTN_GOOGLE_LOG_GOOGLE_VERIFY_MSG#" label_login_button="#rn:msg:LOG_IN_USING_GOOGLE_LBL#"/>
        <? elseif ($provider === 'yahoo'): ?>
        <rn:widget path="login/OpenLogin" sub_id='#rn:php:"openlogin_$provider"#' display_in_dialog="false" controller_endpoint="/ci/openlogin/openid/authorize/yahoo" label_service_button="Yahoo" label_process_explanation="#rn:msg:CLICK_BTN_YAHOO_LOG_YAHOO_VERIFY_MSG#" label_login_button="#rn:msg:LOG_IN_USING_YAHOO_LBL#"/>
        <? endif; ?>
        <? endforeach; ?>
    </div>
    </rn:block>
    <? endif; ?>

    <div class="rn_FormContent">
        <? if ($this->data['attrs']['create_account_fields'] && $this->data['attrs']['label_create_account_button']): ?>
            <span class="rn_FormTypeToggle">

                <rn:block id="preFormTypeLabel"/>
                <span class="rn_FormTypeLabel" id="rn_<?= $this->instanceID ?>_FormTypeLabel">
                    <?= $this->data['attrs']['label_login_button'] ?>
                </span>
                <rn:block id="postFormTypeLabel"/>

                <span class="rn_OrLabel">
                <rn:block id="orLabel">
                <?= \RightNow\Utils\Config::getMessage(OR_LC_LBL) ?>
                </rn:block>
                </span>

                <rn:block id="preSignUpLink"/>
                <a href="javascript:void(0);" id="rn_<?= $this->instanceID ?>_FormTypeToggle"><?=$this->data['attrs']['label_create_account_button'];?><span class="rn_ScreenReaderOnly"><?=$this->data['attrs']['label_opens_new_dialog'];?></span></a>
                <rn:block id="postSignUpLink"/>

            </span>
        <? endif; ?>

        <div id="rn_<?=$this->instanceID;?>_LoginContent" class="rn_LoginDialogContent">
            <rn:block id="preLoginErrorMessage"/>
            <div id="rn_<?=$this->instanceID;?>_LoginErrorMessage"></div>
            <rn:block id="postLoginErrorMessage"/>

            <form id="rn_<?=$this->instanceID;?>_Form">
                <rn:block id="preUsername"/>
                <label for="rn_<?=$this->instanceID;?>_Username" class="rn_ScreenReaderOnly"><?=$this->data['attrs']['label_dialog_title'] . ', ' . $this->data['attrs']['label_username'];?></label>
                <input id="rn_<?=$this->instanceID;?>_Username" placeholder="<?=$this->data['attrs']['label_username'];?>" type="text" maxlength="255" name="Contact.Login" autocorrect="off" autocapitalize="off" value="<?=$this->data['username'];?>"/>
                <rn:block id="postUsername"/>
            <? if(!$this->data['attrs']['disable_password']):?>
                <rn:block id="prePassword"/>
                <label for="rn_<?=$this->instanceID;?>_Password" class="rn_ScreenReaderOnly"><?=$this->data['attrs']['label_password'];?></label>
                <input id="rn_<?=$this->instanceID;?>_Password" maxlength="20"  placeholder="<?=$this->data['attrs']['label_password'];?>" type="password" name="Contact.Password" <?=($this->data['attrs']['disable_password_autocomplete']) ? 'autocomplete="off"' : '' ?>/>
                <rn:block id="postPassword"/>
            <? endif;?>
            </form>
        </div>

        <? if ($this->data['attrs']['create_account_fields']): ?>
        <div id="rn_<?= $this->instanceID ?>_SignUpContent" class="rn_SignUpDialogContent rn_Hidden">
            <rn:block id="preSignUpErrorMessage"/>
            <div id="rn_<?=$this->instanceID;?>_SignUpErrorMessage"></div>
            <rn:block id="postSignUpErrorMessage"/>

            <form action="<?= $this->data['attrs']['create_account_ajax'] ?>" id="rn_<?= $this->instanceID ?>_SignUpForm">
            <? foreach ($this->data['create_account_fields'] as $fieldName): ?>
                <rn:widget path="input/FormInput" sub_id='#rn:php:"input_$fieldName"#' name="#rn:php:$fieldName#"/>
            <? endforeach; ?>
            <div class="rn_Hidden">
                <rn:widget path="input/FormSubmit" sub_id="submit" error_location="rn_#rn:php:$this->instanceID#_SignUpErrorMessage" on_success_url="#rn:php:$this->data['currentPage']#"/>
            </div>
            </form>
        </div>
        <? endif; ?>

        <div class="rn_AssistanceLink">
            <rn:block id="assistanceLink">
            <a href="<?=$this->data['attrs']['assistance_url'] . \RightNow\Utils\Url::sessionParameter();?>"><?=$this->data['attrs']['label_assistance'];?></a>
            </rn:block>
        </div>

        <? if ($this->data['attrs']['show_social_warning']): ?>
        <div class="rn_MessageBox rn_WarningMessage rn_Hidden"><?= $this->data['attrs']['label_social_warning'] ?></div>
        <? endif; ?>
    </div>
    <rn:block id="bottom"/>
</div>
