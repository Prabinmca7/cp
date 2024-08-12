<div id="rn_<?=$this->instanceID?>" class="<?=$this->classList?>">
    <rn:block id="preAccountDropdown"/>
    <? if ($this->data['js']['isLoggedIn']): ?>
    <div class="rn_AccountDropdownParent">
        <rn:block id="preDropdownTrigger"/>
        <a class="rn_LoggedInUser rn_AccountDropdownTrigger" href="javascript:void(0);" id="rn_<?=$this->instanceID?>_DropdownButton" role="button" aria-expanded="false" aria-controls="rn_<?=$this->instanceID?>_SubNavigation">
            <? if (isset($this->data['currentSocialUser'])): ?>
                <span class="rn_AvatarHolder">
                    <?= $this->render('Partials.Social.Avatar', $this->helper('Social')->defaultAvatarArgs($this->data['currentSocialUser'], array(
                        'size'          => 'small',
                        'avatarUrl'     => $this->data['currentSocialUser']->AvatarURL,
                        'defaultAvatar' => $this->helper('Social')->getDefaultAvatar($this->data['nameToDisplay']),
                        'profileUrl'    => null,
                        'displayName'   => null,
                    ))) ?>
                </span>
            <? else: ?>
                <span class="rn_NoSocialUserIcon"></span>
            <? endif; ?>
            <span class="rn_DisplayName"><?= $this->data['nameToDisplay'] ?><span class="rn_ScreenReaderOnly"><?=$this->data['attrs']['label_menu_accessibility']?></span></span>
        </a>
        <rn:block id="postDropdownTrigger"/>

        <nav id="rn_<?=$this->instanceID?>_SubNavigationParent" tabindex="-1">
            <ul id="rn_<?=$this->instanceID?>_SubNavigation" class="rn_SubNavigation rn_Hidden">
                <rn:block id="preDropdownLoggedInList"/>
                <? foreach ($this->data['subpages'] as $subpage): ?>
                    <rn:block id="subNavigationLink">
                    <li><a href="/app/<?=$subpage['href']?>#rn:session#"><?=$subpage['title']?></a></li>
                    </rn:block>
                <? endforeach; ?>
                <li class="rn_LogoutLink"><rn:widget path="login/LogoutLink" id="logout"/></li>
                <rn:block id="postDropdownLoggedInList"/>
            </ul>
        </nav>
    </div>
        <? if ((!isset($this->data['js']['socialUserId']) || !$this->data['js']['socialUserId'])): ?>
        <rn:widget path="user/UserInfoDialog" id="userinfo"/>
        <? endif; ?>
    <? elseif (\RightNow\Utils\Config::getConfig(PTA_ENABLED)): ?>
        <? if (\RightNow\Utils\Config::getConfig(PTA_IGNORE_CONTACT_PASSWORD)): ?>
        <div class="rn_AccountDropdownParent">
            <a href="javascript:void(0);" id="rn_LoginLink"><?=$this->data['attrs']['label_login']?></a>
        </div>
        <? elseif (\RightNow\Utils\Config::getConfig(PTA_EXTERNAL_LOGIN_URL) !== ""): ?>
        <div class="rn_AccountDropdownParent">
            <a href="<?=\RightNow\Utils\Url::replaceExternalLoginVariables(0, $_SERVER['REQUEST_URI'])?>" id="rn_DisabledLoginLink"><?=$this->data['attrs']['label_login']?></a>
        </div>
        <? else: ?>
        <div class="rn_AccountDropdownParent">
            <a href="javascript:void(0);" id="rn_DisabledLoginLink"><?=$this->data['attrs']['label_login']?></a>
        </div>
        <? endif; ?>
    <? else: ?>
    <div class="rn_AccountDropdownParent">
        <a href="javascript:void(0);" role="button" id="rn_LoginLink"><?=$this->data['attrs']['label_login']?></a>
    </div>
    <? endif; ?>
    <rn:widget path="login/LoginDialog" trigger_element="rn_LoginLink" sub_id="login"/>
    <rn:block id="postAccountDropdown"/>
</div>
