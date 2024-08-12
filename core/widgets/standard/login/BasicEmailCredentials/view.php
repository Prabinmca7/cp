<div id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <?if($this->data['attrs']['label_heading'] !== ''):?>
        <rn:block id="heading">
        <h2><?=$this->data['attrs']['label_heading']?></h2>
        </rn:block>
    <?endif;?>
    <?if($this->data['attrs']['label_description'] !== ''):?>
        <rn:block id="description">
        <p><?=$this->data['attrs']['label_description']?></p>
        </rn:block>
    <?endif;?>
    <? $selectorPrefix = "rn_{$this->instanceID}_{$this->data['attrs']['credential_type']}"; ?>
    <rn:form post_handler="#rn:php:$this->data['attrs']['post_handler']#">
        <input type="hidden" name="emailCredentials[type]" value="<?=$this->data['attrs']['credential_type']?>" />
        <input type="hidden" name="requestType" value="<?=$this->data['js']['request_type']?>" />
        <rn:block id="label">
        <label for="<?=$selectorPrefix;?>_Input"><?=$this->data['attrs']['label_input'];?></label>
        </rn:block>
        <rn:block id="preInput"/>
        <input id="<?=$selectorPrefix;?>_Input" name="emailCredentials[value]" type="text" maxlength="255" value="<?=$this->data['email'];?>" />
        <rn:block id="postInput"/>
        <rn:block id="preSubmit"/>
        <rn:widget path="input/BasicFormSubmit" label_button="#rn:php:$this->data['attrs']['label_button']#" on_success_url="#rn:php:$this->data['attrs']['on_success_url']#"/>
        <rn:block id="postSubmit"/>
    </rn:form>
    <rn:block id="bottom"/>
</div>
