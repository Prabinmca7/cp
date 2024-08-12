<div id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <div id="rn_<?=$this->instanceID;?>_Content">
        <rn:block id="preErrorMessage"/>
        <rn:widget path="input/BasicFormStatusDisplay"/>
        <rn:block id="postErrorMessage"/>
        <rn:form action="#rn:php:$this->data['attrs']['form_action']#" post_handler="#rn:php:$this->data['attrs']['login_endpoint']#">
            <rn:block id="preUsername"/>
            <label for="rn_<?=$this->instanceID;?>_Username"><?=$this->data['attrs']['label_username'];?></label>
            <input id="rn_<?=$this->instanceID;?>_Username" type="text" maxlength="255" name="Contact.Login" value="<?=$this->data['username'];?>"/>
            <rn:block id="postUsername"/>
        <? if(!$this->data['attrs']['disable_password']):?>
            <rn:block id="prePassword"/>
            <label for="rn_<?=$this->instanceID;?>_Password"><?=$this->data['attrs']['label_password'];?></label>
            <input id="rn_<?=$this->instanceID;?>_Password" type="password" maxlength="20" name="Contact.Password" />
            <rn:block id="postPassword"/>
        <? endif;?>
            <br/>
            <rn:block id="preSubmit"/>
            <rn:widget path="input/BasicFormSubmit" label_button="#rn:php:$this->data['attrs']['label_login_button']#" on_success_url="#rn:php:$this->data['on_success_url']#" add_params_to_url="#rn:php:$this->data['attrs']['append_to_url']#"/>
            <rn:block id="postSubmit"/>
        </rn:form>
    </div>
    <rn:block id="bottom"/>
</div>
