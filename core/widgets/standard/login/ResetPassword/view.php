<div id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <rn:block id="preError"/>
    <div id="rn_<?=$this->instanceID;?>_ErrorLocation"></div>
    <rn:block id="postError"/>
    <rn:block id="preForm"/>
    <form id="rn_<?=$this->instanceID;?>_ResetPassword" onsubmit="return false;" action="<?=$this->data['attrs']['reset_password_ajax'];?>">
        <rn:block id="formTop"/>
    <?if(strlen($this->data['attrs']['label_heading'])):?>
        <rn:block id="heading">
        <span class="rn_ResetHeading"><?=$this->data['attrs']['label_heading'];?></span>
        </rn:block>
    <?endif;?>
    <?if($this->data['loginRequired']):?>
        <rn:block id="preLogin"/>
        <rn:widget path="input/FormInput" name="Contact.Login" required="true" validate_on_blur="true" initial_focus="true" label_input="#rn:msg:USERNAME_LBL#" sub_id="login"/>
        <rn:block id="postLogin"/>
        <?if(!$this->data['attrs']['disable_password']):?>
            <rn:block id="prePasswordNew"/>
            <rn:widget path="input/FormInput" name="Contact.NewPassword" require_validation="true" label_input="#rn:msg:PASSWORD_LBL#" label_validation="#rn:msg:VERIFY_PASSWD_LBL#" sub_id="newAccountPassword"/>
            <rn:block id="postPasswordNew"/>
        <?endif;?>
    <?else:?>
        <rn:block id="prePasswordNew"/>
        <rn:widget path="input/FormInput" name="Contact.NewPassword" initial_focus="true" require_validation="true" label_input="#rn:msg:PASSWORD_LBL#" label_validation="#rn:msg:VERIFY_PASSWD_LBL#" sub_id="existingAccountPassword"/>
        <rn:block id="postPasswordNew"/>
    <?endif;?>
        <rn:block id="preSubmit"/>
        <rn:widget path="input/FormSubmit" on_success_url="#rn:php:$this->data['attrs']['on_success_url']#" error_location="rn_#rn:php:$this->instanceID#_ErrorLocation" sub_id="submit"/>
        <rn:block id="postSubmit"/>
        <rn:block id="formBottom"/>
    </form>
    <rn:block id="postForm"/>
    <rn:block id="bottom"/>
</div>
