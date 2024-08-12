<div id="rn_<?=$this->instanceID?>" class="<?=$this->classList?> rn_Hidden">
    <rn:block id="top"/>
    <div id="rn_<?=$this->instanceID?>_UserInfoContent">
        <rn:block id="preUserInfoErrorMessage"/>
        <div id="rn_<?=$this->instanceID?>_UserInfoErrorMessage"></div>
        <rn:block id="postUserInfoErrorMessage"/>
        <p id="rn_<?=$this->instanceID?>_UserInfoDescription" class="rn_UserInfoDescription"><?= $this->data['attrs']['show_social_warning'] ? $this->data['attrs']['label_social_form_description'] : $this->data['attrs']['label_form_description'] ?></p>
        <form id="rn_<?=$this->instanceID?>_UserInfoForm" onsubmit="return false;">
            <rn:block id="preDisplayName"/>
            <label for="rn_<?=$this->instanceID?>_DisplayName" id="rn_<?= $this->instanceID ?>_DisplayName_Label" class="rn_Label"><?=$this->data['attrs']['label_display_name']?>
                <?= $this->render('Partials.Forms.RequiredLabel') ?>
            </label>
            <input id="rn_<?=$this->instanceID?>_DisplayName" type="text" maxlength="80" name="CommunityUser.DisplayName" autocorrect="off" autocapitalize="off"/>
            <rn:block id="postDisplayName"/>
        </form>
    </div>
    <rn:block id="bottom"/>
</div>
